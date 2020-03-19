<?php

namespace Dynamic\Salsify\TypeHandler\Relation;

use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataObject;

/**
 * Class SalsifyRelation
 * @package Dynamic\Salsify\TypeHandler\Relation
 */
class SalsifyRelationHandler extends Extension
{
    /**
     * @var array
     */
    private static $field_types = [
        'SalsifyRelation'
    ];

    /**
     * @param string|DataObject $class
     * @param array $data
     * @param string $dataField
     * @param array $config
     * @param string $dbField
     *
     * @return int|DataObject|array
     * @throws \Exception
     */
    public function handleSalsifyRelationType($class, $data, $dataField, $config, $dbField)
    {
        if (!array_key_exists('salsify:relations', $data)) {
            return [];
        }

        $related = [];
        $fieldData = $data['salsify:relations'];
        foreach ($this->owner->yieldSingle($fieldData) as $relation) {
            if (array_key_exists('relation_type', $relation) && $relation['relation_type'] == $dataField) {
                $related[] = DataObject::get($class)->find('SalsifyID', $relation['salsify:target_product_id']);
            }
        }

        return $related;
    }
}
