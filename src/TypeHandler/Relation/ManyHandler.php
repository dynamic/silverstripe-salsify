<?php

namespace Dynamic\Salsify\TypeHandler\Relation;

use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataObject;

/**
 * Class RelationHandler
 * @package Dynamic\Salsify\TypeHandler
 *
 * @property-read \Dynamic\Salsify\TypeHandler\Relation\ManyHandler|\Dynamic\Salsify\Model\Mapper $owner
 */
class ManyHandler extends Extension
{
    /**
     * @var array
     */
    private static $field_types = [
        'ManyRelation'
    ];

    /**
     * @param string|DataObject $class
     * @param $data
     * @param $dataField
     * @param $config
     * @param $dbField
     *
     * @return int|DataObject|array
     * @throws \Exception
     */
    public function handleManyRelationType($class, $data, $dataField, $config, $dbField)
    {
        if (!array_key_exists('relation', $config) || !is_array($config['relation'])) {
            return [];
        }

        $fieldData = $data[$dataField];
        $related = [];

        foreach ($fieldData as $entry) {
            $entryData = array_merge($data, [
                $dataField => $entry
            ]);
            $relationConfig = $config['relation'];
            $relatedClass = array_key_first($relationConfig);
            $related[] = $this->owner->mapToObject($relatedClass, $relationConfig[$relatedClass], $entryData);
        }
        return $related;
    }
}
