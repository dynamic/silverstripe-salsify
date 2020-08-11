<?php

namespace Dynamic\Salsify\TypeHandler\Relation;

use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataObject;

/**
 * Class HasOneRelationHandler
 *
 * @property-read \Dynamic\Salsify\Model\Mapper|HasOneHandler $owner
 */
class HasOneHandler extends Extension
{
    /**
     * @var array
     */
    private static $field_types = [
        'HasOne' => [
            'requiresWrite' => true,
            'requiresSalsifyObjects' => false,
            'allowsModification' => true,
        ],
    ];

    /**
     * @param string|DataObject $class
     * @param $data
     * @param $dataField
     * @param $config
     * @param $dbField
     *
     * @return int|DataObject
     * @throws \Exception
     */
    public function handleHasOneType($class, $data, $dataField, $config, $dbField)
    {
        if (!array_key_exists('relation', $config) || !is_array($config['relation'])) {
            return preg_match('/ID$/', $dbField) ? 0 : null;
        }

        $relationConfig = $config['relation'];
        $relatedClass = array_key_first($relationConfig);

        foreach ($this->owner->yieldKeyVal($relationConfig[$relatedClass]) as $dbField => $salsifyField) {
            if ($this->owner->handleShouldSkip($class, $dbField, $salsifyField, $data)) {
                return false;
            }
        }

        $object = $this->owner->mapToObject($relatedClass, $relationConfig[$relatedClass], $data);
        $objectID = $object ? $object->ID : 0;
        return preg_match('/ID$/', $dbField) ? $objectID : $object;
    }
}
