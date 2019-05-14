<?php

namespace Dynamic\Salsify\Model;

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

    /* $this->config()->get('mapping')
        \Page => [
            dbFieldA => [
                salsifyField => jsonFieldA
                unique => 1
            ]
            dbFieldB => jsonFieldB
        ]
     */

    private $stream;

    public function __construct($file)
    {
        $this->stream = JsonMachine::fromFile($file, '/4/products');
        print_r($this->config()->get('mapping'));
    }

    /**
     * Maps the data
     */
    public function map()
    {
        foreach ($this->stream as $name => $data) {
            foreach ($this->config()->get('mapping') as $class => $mappings) {
                $this->mapToObject($class, $mappings, $data);
            }
        }
    }

    /**
     * @param string $class
     * @param array $mappings
     * @param array $data
     */
    private function mapToObject($class, $mappings, $data)
    {
        $object = $this->findObjectByUnique($class, $mappings);
        if (!$object) {
            $object = $class::create();
        }

        foreach ($mappings as $dbField => $salsifyField) {
            if (is_array($salsifyField)) {
                if (!array_key_exists('salsifyField', $salsifyField)) {
                    continue;
                }
                $salsifyField = $salsifyField['salsifyField'];
            }

            $object->$dbField = $data[$salsifyField];
        }

        $object->write();
    }

    /**
     * @param string $class
     * @param array $mappings
     *
     * @return \SilverStripe\ORM\DataObject
     */
    private function findObjectByUnique($class, $mappings)
    {
        $uniqueFields = [];
        foreach ($mappings as $dbField => $salsifyField) {
            if (!is_array($salsifyField)) {
                continue;
            }

            if (!array_key_exists('unique', $salsifyField) &&
                !array_key_exists('salsifyField', $salsifyField)) {
                continue;
            }

            if (!$salsifyField['unique'] == true) {
                continue;
            }

            $uniqueFields[] = $salsifyField['salsifyField'];
        }

        return DataObject::get($class)->filter($uniqueFields)->first();
    }
}
