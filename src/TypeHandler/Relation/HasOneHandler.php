<?php

namespace Dynamic\Salsify\TypeHandler\Relation;

use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataObject;

/**
 * Class HasOneRelationHandler
 *
 * @property-read \Dynamic\Salsify\TypeHandler\Relation\HasOneHandler|\Dynamic\Salsify\Model\Mapper $owner
 */
class HasOneHandler extends Extension
{
    /**
     * @var array
     */
    private static $field_types = [
        'HasOne',
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
    public function handleHasOneHandlerType($class, $data, $dataField, $config, $dbField)
    {
        if (!array_key_exists('relation', $config) || !is_array($config['relation'])) {
            return preg_match('/ID$/', $dbField) ? 0 : null;
        }

        $relationConfig = $config['relation'];
        $relatedClass = array_key_first($relationConfig);
        $object = $this->owner->mapToObject($relatedClass, $relationConfig[$relatedClass], $data);
        return preg_match('/ID$/', $dbField) ? $object->ID : $object;
    }
}
