<?php

namespace Dynamic\Salsify\Model;

use Dynamic\Salsify\ORM\SalsifyIDExtension;
use Dynamic\Salsify\Task\ImportTask;
use Exception;
use JsonMachine\JsonMachine;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Versioned\Versioned;

/**
 * Class Mapper
 * @package Dynamic\Salsify\Model
 */
class Mapper extends Service
{

    /**
     * @var bool
     */
    public static $SINGLE = false;

    /**
     * @var bool
     */
    public static $MULTIPLE = true;

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
    public $skipSilently = false;

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
            $this->resetProductStream();
            $this->resetAssetStream();
        }
    }

    /**
     *
     */
    public function resetProductStream()
    {
        $this->productStream = JsonMachine::fromFile($this->file, '/4/products');
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
        $this->extend('onBeforeMap', $this->file, Mapper::$MULTIPLE);

        foreach ($this->yieldKeyVal($this->productStream) as $name => $data) {
            foreach ($this->yieldKeyVal($this->config()->get('mapping')) as $class => $mappings) {
                $this->mapToObject($class, $mappings, $data);
                $this->currentUniqueFields = [];
            }
        }

        if ($this->mappingHasSalsifyRelation()) {
            ImportTask::output("----------------");
            ImportTask::output("Setting up salsify relations");
            ImportTask::output("----------------");
            $this->resetProductStream();

            foreach ($this->yieldKeyVal($this->productStream) as $name => $data) {
                foreach ($this->yieldKeyVal($this->config()->get('mapping')) as $class => $mappings) {
                    $this->mapToObject($class, $mappings, $data, null, true);
                    $this->currentUniqueFields = [];
                }
            }
        }

        ImportTask::output("Imported and updated $this->importCount products.");
        $this->extend('onAfterMap', $this->file, Mapper::$MULTIPLE);
    }

    /**
     * @param string|DataObject $class
     * @param array $mappings The mapping for a specific class
     * @param array $data
     * @param DataObject|null $object
     * @param bool $salsifyRelations
     * @param bool $forceUpdate
     *
     * @return DataObject|null
     * @throws \Exception
     */
    public function mapToObject(
        $class,
        $mappings,
        $data,
        $object = null,
        $salsifyRelations = false,
        $forceUpdate = false
    )
    {
        if ($salsifyRelations) {
            if (!$this->classConfigHasSalsifyRelation($mappings)) {
                return null;
            }
        }

        // if object was not passed
        if ($object === null) {
            $object = $this->findObjectByUnique($class, $mappings, $data);

            $filter = $this->getUniqueFilter($class, $mappings, $data);
            if (count(array_filter($filter)) == 0) {
                return null;
            }

            // if no existing object was found but a unique filter is valid (not empty)
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

        if (
            !$forceUpdate &&
            $this->objectUpToDate($object, $data, $firstUniqueKey, $firstUniqueValue, $salsifyRelations)
        ) {
            return $object;
        }

        foreach ($this->yieldKeyVal($mappings) as $dbField => $salsifyField) {
            $field = $this->getField($salsifyField, $data);
            if ($field === false) {
                $this->clearValue($object, $dbField, $salsifyField);
                continue;
            }

            $type = $this->getFieldType($salsifyField);
            // skip all but salsify relations types if not doing relations
            if ($salsifyRelations && ($type != 'SalsifyRelation' && $type != 'SalsifyRelationTimeStamp')) {
                continue;
            }

            // skip salsify relations types if not doing relations
            if (!$salsifyRelations && ($type == 'SalsifyRelation' || $type == 'SalsifyRelationTimeStamp')) {
                continue;
            }

            $value = null;
            $objectData = $data;

            if ($this->handleShouldSkip($class, $dbField, $salsifyField, $data)) {
                if (!$this->skipSilently) {
                    ImportTask::output("Skipping $class $firstUniqueKey $firstUniqueValue");
                    $this->skipSilently = false;
                }
                return false;
            };

            $objectData = $this->handleModification($class, $dbField, $salsifyField, $data);
            $sortColumn = $this->getSortColumn($salsifyField);

            if ($salsifyRelations == false && !array_key_exists($field, $objectData)) {
                continue;
            }

            $value = $this->handleType($type, $class, $objectData, $field, $salsifyField, $dbField);
            $this->writeValue($object, $dbField, $value, $sortColumn);
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
     * @param bool $salsifyRelations
     * @return bool
     */
    private function objectUpToDate($object, $data, $firstUniqueKey, $firstUniqueValue, $salsifyRelations = false)
    {
        if ($this->config()->get('skipUpToDate') == false) {
            return false;
        }

        if ($salsifyRelations == false) {
            if ($this->objectDataUpToDate($object, $data, $firstUniqueKey, $firstUniqueValue)) {
                ImportTask::output("Skipping $firstUniqueKey $firstUniqueValue. It is up to Date.");
                return true;
            }

        } else {
            if ($this->objectRelationsUpToDate($object, $data, $firstUniqueKey, $firstUniqueValue)) {
                ImportTask::output("Skipping $firstUniqueKey $firstUniqueValue relations. It is up to Date.");
                return true;
            }
        }

        return false;
    }

    /**
     * @param DataObject $object
     * @param array $data
     * @param string $firstUniqueKey
     * @param string $firstUniqueValue
     * @return bool
     */
    public function objectDataUpToDate($object, $data, $firstUniqueKey, $firstUniqueValue)
    {
        // assume not up to date if field does not exist on object
        if (!$object->hasField('SalsifyUpdatedAt')) {
            return false;
        }

        if ($data['salsify:updated_at'] != $object->getField('SalsifyUpdatedAt')) {
            return false;
        }

        return true;
    }

    /**
     * @param DataObject $object
     * @param array $data
     * @param string $firstUniqueKey
     * @param string $firstUniqueValue
     * @return bool
     */
    public function objectRelationsUpToDate($object, $data, $firstUniqueKey, $firstUniqueValue)
    {
        // assume not up to date if field does not exist on object
        if (!$object->hasField('SalsifyRelationsUpdatedAt')) {
            return false;
        }

        // relations were never updated, so its up to date
        if (!isset($data['salsify:relations_updated_at'])) {
            return true;
        }

        if ($data['salsify:relations_updated_at'] != $object->getField('SalsifyRelationsUpdatedAt')) {
            return false;
        }

        return true;
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
        $isSalsifyRelationField = (
            $this->getFieldType($salsifyField) === 'SalsifyRelation' &&
            $hasSalsifyField
        );

        if ($isLiteralField) {
            return $salsifyField['value'];
        }

        if ($isSalsifyRelationField) {
            return $salsifyField['salsifyField'];
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

            foreach ($this->yieldSingle($salsifyField['fallback']) as $fallback) {
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
     * @return array
     */
    private function getUniqueFilter($class, $mappings, $data)
    {
        $uniqueFields = $this->uniqueFields($class, $mappings);
        // creates a filter
        $filter = [];
        foreach ($this->yieldKeyVal($uniqueFields) as $dbField => $salsifyField) {
            $modifiedData = $data;
            $fieldMapping = $mappings[$dbField];

            $modifiedData = $this->handleModification($class, $dbField, $fieldMapping, $modifiedData);

            // adds unique fields to filter
            if (array_key_exists($salsifyField, $modifiedData)) {
                $filter[$dbField] = $modifiedData[$salsifyField];
            }
        }

        return $filter;
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
        if ($obj = $this->findBySalsifyID($class, $mappings, $data)) {
            return $obj;
        }

        $filter = $this->getUniqueFilter($class, $mappings, $data);
        return DataObject::get($class)->filter($filter)->first();
    }

    /**
     * @param string $class
     * @param array $mappings
     * @param array $data
     *
     * @return \SilverStripe\ORM\DataObject|bool
     */
    private function findBySalsifyID($class, $mappings, $data)
    {
        /** @var DataObject $genericObject */
        $genericObject = Injector::inst()->get($class);
        if (
            !$genericObject->hasExtension(SalsifyIDExtension::class) &&
            !$genericObject->hasField('SalsifyID')
        ) {
            return false;
        }

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

        return false;
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
        foreach ($this->yieldKeyVal($mappings) as $dbField => $salsifyField) {
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
     * @param array|string $salsifyField
     * @return bool|mixed
     */
    private function getSortColumn($salsifyField)
    {
        if (!is_array($salsifyField)) {
            return false;
        }

        if (array_key_exists('sortColumn', $salsifyField)) {
            return $salsifyField['sortColumn'];
        }

        return false;
    }

    /**
     * @return bool
     */
    private function mappingHasSalsifyRelation()
    {
        foreach ($this->yieldKeyVal($this->config()->get('mapping')) as $class => $mappings) {
            if ($this->classConfigHasSalsifyRelation($mappings)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array $classConfig
     * @return bool
     */
    private function classConfigHasSalsifyRelation($classConfig)
    {
        foreach ($this->yieldKeyVal($classConfig) as $field => $config) {
            if (!is_array($config)) {
                continue;
            }

            if (!array_key_exists('salsifyField', $config)) {
                continue;
            }

            if (!array_key_exists('type', $config)) {
                continue;
            }

            if ($config['type'] === 'SalsifyRelation') {
                return true;
            }
        }
        return false;
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
    public function handleShouldSkip($class, $dbField, $config, $data)
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

        if ($fallbacks = $this->config()->get('typeFallbacks')) {
            foreach ($fallbacks as $original => $fallback) {
                if ($type == $original) {
                    if ($this->hasMethod("handle{$fallback}Type")) {
                        return $this->{"handle{$fallback}Type"}($class, $salsifyData, $salsifyField, $dbFieldConfig, $dbField);
                    }
                }
            }
        }
        ImportTask::output("{$type} is not a valid type. skipping field {$dbField}.");
        return '';
    }

    /**
     * @param DataObject $object
     * @param string $dbField
     * @param mixed $value
     * @param string|bool $sortColumn
     *
     * @throws \Exception
     */
    private function writeValue($object, $dbField, $value, $sortColumn)
    {
        $isManyRelation = array_key_exists($dbField, $object->config()->get('has_many')) ||
            array_key_exists($dbField, $object->config()->get('many_many')) ||
            array_key_exists($dbField, $object->config()->get('belongs_many_many'));

        $isSingleRelation = array_key_exists(rtrim($dbField, 'ID'), $object->config()->get('has_one'));

        if (!$isManyRelation) {
            if (!$isSingleRelation || ($isSingleRelation && $value !== false)) {
                $object->$dbField = $value;
            }
            return;
        }

        // change to an array and filter out empty values
        if (!is_array($value)) {
            $value = [$value];
        }
        $value = array_filter($value);

        // don't try to write an empty set
        if (!count($value)) {
            return;
        }

        // write the object so relations can be written
        if (!$object->exists()) {
            $object->write();
        }

        $this->removeUnrelated($object, $dbField, $value);
        $this->writeManyRelation($object, $dbField, $value, $sortColumn);
    }

    /**
     * @param DataObject $object
     * @param string $dbField
     * @param array $value
     * @param string|bool $sortColumn
     *
     * @throws \Exception
     */
    private function writeManyRelation($object, $dbField, $value, $sortColumn)
    {
        /** @var DataList|HasManyList|ManyManyList $relation */
        $relation = $object->{$dbField}();

        if ($sortColumn && $relation instanceof ManyManyList) {
            for ($i = 0; $i < count($value); $i++) {
                $relation->add($value[$i], [$sortColumn => $i]);
            }
            return;
        }

        // HasManyList, so it exists on the value
        if ($sortColumn) {
            for ($i = 0; $i < count($value); $i++) {
                $value[$i]->{$sortColumn} = $i;
                $relation->add($value[$i]);
            }
            return;
        }

        $relation->addMany($value);
    }

    /**
     * Removes unrelated objects in the relation that were previously related
     * @param DataObject $object
     * @param string $dbField
     * @param array $value
     */
    private function removeUnrelated($object, $dbField, $value)
    {
        $ids = [];
        foreach ($value as $v) {
            $ids[] = $v->ID;
        }

        /** @var DataList $relation */
        $relation = $object->{$dbField}();
        // remove all unrelated - removeAll had an odd side effect (relations only got added back half the time)
        if (!empty($ids)) {
            $relation->removeMany(
                $relation->exclude([
                    'ID' => $ids,
                ])->column('ID')
            );
        }
    }

    /**
     * @param string|array $salsifyField
     * @throws Exception
     */
    private function clearValue($object, $dbField, $salsifyField)
    {
        if (
            is_array($salsifyField) &&
            array_key_exists('keepExistingValue', $salsifyField) &&
            $salsifyField['keepExistingValue']
        ) {
            return;
        }

        // clear any existing value
        $this->writeValue($object, $dbField, null, null);
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
