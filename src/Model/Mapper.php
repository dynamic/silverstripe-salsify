<?php

namespace Dynamic\Salsify\Model;

use Dynamic\Salsify\Task\ImportTask;
use JsonMachine\JsonMachine;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataObject;

/**
 * Class Mapper
 * @package Dynamic\Salsify\Model
 */
class Mapper
{
    use Configurable;
    use Extensible;
    use Injectable;

    /**
     * @var \JsonMachine\JsonMachine
     */
    private $stream;

    /**
     * @var array
     */
    private $currentUniqueFields;

    /**
     * @var int
     */
    private $importCount = 0;

    public function __construct($file)
    {
        $this->stream = JsonMachine::fromFile($file, '/4/products');
    }

    /**
     * Maps the data
     */
    public function map()
    {
        foreach ($this->stream as $name => $data) {
            foreach ($this->config()->get('mapping') as $class => $mappings) {
                $this->mapToObject($class, $mappings, $data);
                $this->currentUniqueFields = [];
            }
        }
        ImportTask::echo("Imported and updated $this->importCount products.");
    }

    /**
     * @param string $class
     * @param array $mappings
     * @param array $data
     */
    private function mapToObject($class, $mappings, $data)
    {
        $object = $this->findObjectByUnique($class, $mappings, $data);
        if (!$object) {
            $object = $class::create();
        }

        $firstUniqueKey = array_keys($this->uniqueFields($class, $mappings))[0];
        $firstUniqueValue = $data[$mappings[$firstUniqueKey]['salsifyField']];
        ImportTask::echo("Updating $firstUniqueKey $firstUniqueValue");

        foreach ($mappings as $dbField => $salsifyField) {
            if (is_array($salsifyField)) {
                if (!array_key_exists('salsifyField', $salsifyField)) {
                    continue;
                }
                $salsifyField = $salsifyField['salsifyField'];
            }

            if (!array_key_exists($salsifyField, $data)) {
                ImportTask::echo("Skipping mapping for field $salsifyField for $firstUniqueKey $firstUniqueValue");
                continue;
            }

            $object->$dbField = $data[$salsifyField];
        }

        if ($object->isChanged()) {
            $object->write();
            $this->importCount++;
        } else {
            ImportTask::echo("$firstUniqueKey $firstUniqueValue was not changed.");
        }
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
        $uniqueFields = $this->uniqueFields($class, $mappings);
        $filter = [];
        foreach ($uniqueFields as $dbField => $salsifyField) {
            $filter[$dbField] = $data[$salsifyField];
        }

        return DataObject::get($class)->filter($filter)->first();
    }

    /**
     * @param string $class
     * @param array $mappings
     * @return array
     */
    private function uniqueFields($class, $mappings)
    {
        if (!empty($this->currentUniqueFields)) {
            return $this->currentUniqueFields;
        }

        $uniqueFields = [];
        foreach ($mappings as $dbField => $salsifyField) {
            if (!is_array($salsifyField)) {
                continue;
            }

            if (!array_key_exists('unique', $salsifyField) ||
                !array_key_exists('salsifyField', $salsifyField)) {
                continue;
            }

            if (!$salsifyField['unique'] == true) {
                continue;
            }

            $uniqueFields[$dbField] = $salsifyField['salsifyField'];
        }

        $this->currentUniqueFields = $uniqueFields;
        return $uniqueFields;
    }
}
