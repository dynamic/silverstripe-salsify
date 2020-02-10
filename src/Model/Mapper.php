<?php

namespace Dynamic\Salsify\Model;

use Dyanmic\Salsify\ORM\SalsifyIDExtension;
use Dynamic\Salsify\Task\ImportTask;
use Exception;
use JsonMachine\JsonMachine;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

/**
 * Class Mapper
 * @package Dynamic\Salsify\Model
 */
class Mapper extends Service
{

    public const STOP_GENERATOR = 'stop';

    /**
     * @var
     */
    private $file = null;

    /**
     * @var JsonMachine
     */
    private $productStream;

    /**
     * @var JsonMachine
     */
    private $assetStream;

    /**
     * @var array
     */
    private $currentUniqueFields = [];

    /**
     * @var int
     */
    private $importCount = 0;

    /**
     * @var bool
     */
    public $skipSiliently = false;

    /**
     * Mapper constructor.
     * @param string $importerKey
     * @param $file
     * @throws \Exception
     */
    public function __construct($importerKey, $file = null)
    {
        parent::__construct($importerKey);
        if (!$this->config()->get('mapping')) {
            throw  new Exception('A Mapper needs a mapping');
        }

        if ($file !== null) {
            $this->file = $file;
            $this->resetProductSteam();
            $this->resetAssetStream();
        }
    }

    /**
     *
     */
    protected function resetAssetStream()
    {
        $this->assetStream = JsonMachine::fromFile($this->file, '/3/digital_assets');
    }

    /**
     *
     */
    protected function resetProductSteam()
    {
        $this->productStream = JsonMachine::fromFile($this->file, '/4/products');
    }

    /**
     * @return \Generator|void
     * @throws Exception
     */
    public function getAssets()
    {
        foreach ($this->assetStream as $name => $data) {
            $injected = (yield $name => $data);
            if ($injected === static::STOP_GENERATOR) break;
        }
        $this->resetAssetStream();
    }

    /**
     * @return \Generator|void
     * @throws Exception
     */
    public function getProducts()
    {
        foreach ($this->productStream as $name => $data) {
            $injected = yield $name => $data;
            if ($injected === static::STOP_GENERATOR) break;
        }
        $this->resetProductSteam();
    }

    /**
     * @return \Generator
     */
    protected function getMappings()
    {
        foreach ($this->config()->get('mapping') as $class => $mappings) {
            yield $class => $mappings;
        }
    }

    /**
     * Maps the data
     * @throws \Exception
     */
    public function map()
    {
        foreach ($this->getProducts() as $name => $data) {
            foreach ($this->getMappings() as $class => $mappings) {
                $this->mapToObject($class, $mappings, $data);
                $this->currentUniqueFields = [];
            }
        }
        ImportTask::output("Imported and updated $this->importCount products.");
    }

    /**
     * @param $mappings
     * @return \Generator
     */
    protected function getFieldMappings($mappings)
    {
        foreach ($mappings as $dbField => $salsifyField) {
            yield $dbField => $salsifyField;
        }
    }

    /**
     * @param string|DataObject $class
     * @param array $mappings The mapping for a specific class
     * @param array $data
     * @param DataObject|null $object
     *
     * @return DataObject
     * @throws \Exception
     */
    public function mapToObject($class, $mappings, $data, $object = null)
    {
        // if object was not passed
        if ($object === null) {
            $object = $this->findObjectByUnique($class, $mappings, $data);
            // if no existing object was found
            if (!$object) {
                $object = $class::create();
            }
        }

        $wasPublished = $object->hasExtension(Versioned::class) ? $object->isPublished() : false;
        $wasWritten = $object->isInDB();

        $firstUniqueKey = array_keys($this->uniqueFields($class, $mappings))[0];
        if (array_key_exists($mappings[$firstUniqueKey]['salsifyField'], $data)) {
            $firstUniqueValue = $data[$mappings[$firstUniqueKey]['salsifyField']];
        } else {
            $firstUniqueValue = 'NULL';
        }
        ImportTask::output("Updating $class $firstUniqueKey $firstUniqueValue");

        if ($this->objectUpToDate($object, $data, $firstUniqueKey, $firstUniqueValue)) {
            return $object;
        }

        foreach ($this->getFieldMappings($mappings) as $dbField => $salsifyField) {
            $field = $this->getField($salsifyField, $data);
            if ($field === false) {
                continue;
            }

            $value = null;
            $type = $this->getFieldType($salsifyField);
            $objectData = $data;

            if (is_array($salsifyField)) {
                if ($this->handleShouldSkip($class, $dbField, $salsifyField, $data)) {
                    if (!$this->skipSiliently) {
                        ImportTask::output("Skipping $class $firstUniqueKey $firstUniqueValue");
                        $this->skipSiliently = false;
                    }
                    return null;
                };

                $objectData = $this->handleModification($class, $dbField, $salsifyField, $data);
            }

            if (!array_key_exists($field, $objectData)) {
                continue;
            }

            $value = $this->handleType($type, $class, $objectData, $field, $salsifyField, $dbField);
            $this->writeValue($object, $dbField, $value);
        }

        if ($object->isChanged()) {
            $object->write();
            $this->importCount++;
            $this->extend('afterObjectWrite', $object, $wasWritten, $wasPublished);
        } else {
            ImportTask::output("$class $firstUniqueKey $firstUniqueValue was not changed.");
        }
        return $object;
    }

    /**
     * @param DataObject $object
     * @param array $data
     * @param string $firstUniqueKey
     * @param string $firstUniqueValue
     * @return bool
     */
    private function objectUpToDate($object, $data, $firstUniqueKey, $firstUniqueValue)
    {
        if (
            $this->config()->get('skipUpToDate') == true &&
            $object->hasField('SalsifyUpdatedAt') &&
            $data['salsify:updated_at'] == $object->getField('SalsifyUpdatedAt')
        ) {
            ImportTask::output("Skipping $firstUniqueKey $firstUniqueValue. It is up to Date.");
            return true;
        }
        return false;
    }

    /**
     * @param array $salsifyField
     * @param array $data
     *
     * @return string|false
     */
    private function getField($salsifyField, $data)
    {
        if (!is_array($salsifyField)) {
            return array_key_exists($salsifyField, $data) ? $salsifyField : false;
        }

        $hasSalsifyField = array_key_exists('salsifyField', $salsifyField);
        $isLiteralField = (
            $this->getFieldType($salsifyField) === 'Literal' &&
            array_key_exists('value', $salsifyField)
        );

        if ($isLiteralField) {
            return $salsifyField['value'];
        }

        if (!$hasSalsifyField) {
            return false;
        }

        if (array_key_exists($salsifyField['salsifyField'], $data)) {
            return $salsifyField['salsifyField'];
        } elseif (array_key_exists('fallback', $salsifyField)) {
            // make fallback an array
            if (!is_array($salsifyField['fallback'])) {
                $salsifyField['fallback'] = [$salsifyField['fallback']];
            }

            foreach ($salsifyField['fallback'] as $fallback) {
                if (array_key_exists($fallback, $data)) {
                    return $fallback;
                }
            }
        } elseif (array_key_exists('modification', $salsifyField)) {
            return $salsifyField['salsifyField'];
        }

        return false;
    }

    /**
     * @param string $class
     * @param array $mappings
     * @param array $data
     *
     * @return \SilverStripe\ORM\DataObject
     */
    private function findObjectByUnique($class, $mappings, $data)
    {
        /** @var DataObject $genericObject */
        $genericObject = Injector::inst()->get($class);
        if (
            $genericObject->hasExtension(SalsifyIDExtension::class) ||
            $genericObject->hasField('SalsifyID')
        ) {
            $modifiedData = $data;
            if (array_key_exists('salsify:id', $mappings)) {
                $modifiedData = $this->handleModification($class, 'salsify:id', $mappings['salsify:id'], $modifiedData);
            }
            $obj = DataObject::get($class)->filter([
                'SalsifyID' => $modifiedData['salsify:id'],
            ])->first();
            if ($obj) {
                return $obj;
            }
        }

        $uniqueFields = $this->uniqueFields($class, $mappings);
        // creates a filter
        $filter = [];
        foreach ($uniqueFields as $dbField => $salsifyField) {
            $modifiedData = $data;
            $fieldMapping = $mappings[$dbField];

            $modifiedData = $this->handleModification($class, $dbField, $fieldMapping, $modifiedData);

            // adds unique fields to filter
            if (array_key_exists($salsifyField, $modifiedData)) {
                $filter[$dbField] = $modifiedData[$salsifyField];
            }
        }

        return DataObject::get($class)->filter($filter)->first();
    }

    /**
     * Gets a list of all the unique field keys
     *
     * @param string class
     * @param array $mappings
     * @return array
     */
    private function uniqueFields($class, $mappings)
    {
        // cached after first map
        if (array_key_exists($class, $this->currentUniqueFields) && !empty($this->currentUniqueFields[$class])) {
            return $this->currentUniqueFields[$class];
        }

        $uniqueFields = [];
        foreach ($this->getFieldMappings($mappings) as $dbField => $salsifyField) {
            if (!is_array($salsifyField)) {
                continue;
            }

            if (
                !array_key_exists('unique', $salsifyField) ||
                !array_key_exists('salsifyField', $salsifyField)
            ) {
                continue;
            }

            if ($salsifyField['unique'] !== true) {
                continue;
            }

            $uniqueFields[$dbField] = $salsifyField['salsifyField'];
        }

        $this->currentUniqueFields[$class] = $uniqueFields;
        return $uniqueFields;
    }

    /**
     * @param string $class
     * @param string $dbField
     * @param array $config
     * @param array $data
     * @return array
     */
    private function handleModification($class, $dbField, $config, $data)
    {
        if (!is_array($config)) {
            return $data;
        }

        if (array_key_exists('modification', $config)) {
            $mod = $config['modification'];
            if ($this->hasMethod($mod)) {
                return $this->{$mod}($class, $dbField, $config, $data);
            }
            ImportTask::output("{$mod} is not a valid field modifier. skipping modification for field {$dbField}.");
        }
        return $data;
    }

    /**
     * @param string $class
     * @param string $dbField
     * @param array $config
     * @param array $data
     * @return boolean
     */
    private function handleShouldSkip($class, $dbField, $config, $data)
    {
        if (!is_array($config)) {
            return false;
        }

        if (array_key_exists('shouldSkip', $config)) {
            $skipMethod = $config['shouldSkip'];
            if ($this->hasMethod($skipMethod)) {
                return $this->{$skipMethod}($class, $dbField, $config, $data);
            }
            ImportTask::output(
                "{$skipMethod} is not a valid skip test method. Skipping skip test for field {$dbField}."
            );
        }
        return false;
    }

    /**
     * @param string|array $field
     * @return string
     */
    public function getFieldType($field)
    {
        $fieldTypes = $this->config()->get('field_types');
        if (is_array($field) && array_key_exists('type', $field)) {
            if (in_array($field['type'], $fieldTypes)) {
                return $field['type'];
            }
        }
        // default to raw
        return 'Raw';
    }

    /**
     * @param int $type
     * @param string|DataObject $class
     * @param array $salsifyData
     * @param string $salsifyField
     * @param array $dbFieldConfig
     * @param string $dbField
     *
     * @return mixed
     */
    private function handleType($type, $class, $salsifyData, $salsifyField, $dbFieldConfig, $dbField)
    {
        if ($this->hasMethod("handle{$type}Type")) {
            return $this->{"handle{$type}Type"}($class, $salsifyData, $salsifyField, $dbFieldConfig, $dbField);
        }
        ImportTask::output("{$type} is not a valid type. skipping field {$dbField}.");
        return '';
    }

    /**
     * @param DataObject $object
     * @param string $dbField
     * @param mixed $value
     *
     * @throws \Exception
     */
    private function writeValue($object, $dbField, $value)
    {
        $isManyRelation = array_key_exists($dbField, $object->config()->get('has_many')) ||
            array_key_exists($dbField, $object->config()->get('many_many')) ||
            array_key_exists($dbField, $object->config()->get('belongs_many_many'));

        if (!$isManyRelation) {
            $object->$dbField = $value;
            return;
        }

        if (!$object->exists()) {
            $object->write();
        }

        if (is_array($value)) {
            $value = array_filter($value);
            if (empty($value)) return;

            $object->{$dbField}()->addMany($value);
            return;
        }

        if ($value) {
            $object->{$dbField}()->add($value);
        }
    }

    /**
     * @return bool
     */
    public function hasFile()
    {
        return $this->file !== null;
    }
}
