<?php

namespace Dynamic\Salsify\Model;

use Dynamic\Salsify\Task\ImportTask;
use Exception;
use JsonMachine\JsonMachine;
use SilverStripe\ORM\DataObject;

/**
 * Class Mapper
 * @package Dynamic\Salsify\Model
 */
class Mapper extends Service
{

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
    private $currentUniqueFields;

    /**
     * @var int
     */
    private $importCount = 0;

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
            $this->productStream = JsonMachine::fromFile($file, '/4/products');
            $this->resetAssetStream();
        }
    }

    /**
     *
     */
    public function resetAssetStream()
    {
        $this->assetStream = JsonMachine::fromFile($this->file, '/3/digital_assets');
    }

    /**
     * Maps the data
     * @throws \Exception
     */
    public function map()
    {
        foreach ($this->productStream as $name => $data) {
            foreach ($this->config()->get('mapping') as $class => $mappings) {
                $this->mapToObject($class, $mappings, $data);
                $this->currentUniqueFields = [];
            }
        }
        ImportTask::output("Imported and updated $this->importCount products.");
    }

    /**
     * @param string|DataObject $class
     * @param array $mappings The mapping for a specific class
     * @param array $data
     *
     * @return DataObject
     * @throws \Exception
     */
    public function mapToObject($class, $mappings, $data)
    {
        $object = $this->findObjectByUnique($class, $mappings, $data);
        if (!$object) {
            $object = $class::create();
        }

        $firstUniqueKey = array_keys($this->uniqueFields($mappings))[0];
        $firstUniqueValue = $data[$mappings[$firstUniqueKey]['salsifyField']];
        ImportTask::output("Updating $firstUniqueKey $firstUniqueValue");

        foreach ($mappings as $dbField => $salsifyField) {
            $field = $salsifyField;
            $value = null;
            // default to raw
            $type = $this->getFieldType($salsifyField);
            $objectData = $data;

            if (is_array($salsifyField)) {
                if (!array_key_exists('salsifyField', $salsifyField)) {
                    continue;
                }
                $field = $salsifyField['salsifyField'];

                if (array_key_exists('shouldSkip', $salsifyField)) {
                    if ($this->handleShouldSkip($salsifyField['shouldSkip'], $dbField, $salsifyField, $data)) {
                        ImportTask::output("Skipping $firstUniqueKey $firstUniqueValue");
                        return null;
                    };
                }

                if (array_key_exists('modification', $salsifyField)) {
                    $objectData = $this->handleModification(
                        $salsifyField['modification'],
                        $dbField,
                        $salsifyField,
                        $data
                    );
                }
            }

            if (!array_key_exists($field, $objectData)) {
                continue;
            }

            $value = $this->handleType($type, $objectData, $field, $salsifyField, $dbField, $class);
            $this->writeValue($object, $dbField, $value);
        }

        if ($object->isChanged()) {
            $object->write();
            $this->importCount++;
        } else {
            ImportTask::output("$firstUniqueKey $firstUniqueValue was not changed.");
        }
        return $object;
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
        $uniqueFields = $this->uniqueFields($mappings);
        // creates a filter
        $filter = [];
        foreach ($uniqueFields as $dbField => $salsifyField) {
            $modifiedData = $data;
            $fieldMapping = $mappings[$dbField];

            if (array_key_exists('modification', $fieldMapping)) {
                $modifiedData = $this->handleModification(
                    $fieldMapping['modification'],
                    $dbField,
                    $fieldMapping,
                    $modifiedData
                );
            }

            // adds unique fields to filter
            $filter[$dbField] = $modifiedData[$salsifyField];
        }

        return DataObject::get($class)->filter($filter)->first();
    }

    /**
     * Gets a list of all the unique field keys
     *
     * @param array $mappings
     * @return array
     */
    private function uniqueFields($mappings)
    {
        // cached after first map
        if (!empty($this->currentUniqueFields)) {
            return $this->currentUniqueFields;
        }

        $uniqueFields = [];
        foreach ($mappings as $dbField => $salsifyField) {
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

        $this->currentUniqueFields = $uniqueFields;
        return $uniqueFields;
    }

    /**
     * @param string $mod
     * @param string $dbField
     * @param array $config
     * @param array $data
     * @return array
     */
    private function handleModification($mod, $dbField, $config, $data)
    {
        if ($this->hasMethod($mod)) {
            return $this->{$mod}($dbField, $config, $data);
        }
        ImportTask::output("{$mod} is not a valid field modifier. skipping modification for field {$dbField}.");
        return $data;
    }

    /**
     * @param string $skipMethod
     * @param string $dbField
     * @param array $config
     * @param array $data
     * @return boolean
     */
    private function handleShouldSkip($skipMethod, $dbField, $config, $data)
    {
        if ($this->hasMethod($skipMethod)) {
            return $this->{$skipMethod}($dbField, $config, $data);
        }
        ImportTask::output("{$skipMethod} is not a valid skip test method. skipping skip test for field {$dbField}.");
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
        return 'Raw';
    }

    /**
     * @param int $type
     * @param array $salsifyData
     * @param string $salsifyField
     * @param array $dbFieldConfig
     * @param string $dbField
     * @param string $class
     *
     * @return mixed
     */
    private function handleType($type, $salsifyData, $salsifyField, $dbFieldConfig, $dbField, $class)
    {
        if ($this->hasMethod("handle{$type}Type")) {
            return $this->{"handle{$type}Type"}($salsifyData, $salsifyField, $dbFieldConfig, $dbField, $class);
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
            $object->{$dbField}()->addMany($value);
            return;
        }

        $object->{$dbField}()->add($value);
    }

    /**
     * @return \JsonMachine\JsonMachine
     */
    public function getAssetStream()
    {
        return $this->assetStream;
    }

    /**
     * @return bool
     */
    public function hasFile()
    {
        return $this->file !== null;
    }
}
