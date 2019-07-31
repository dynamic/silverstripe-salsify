<?php

namespace Dynamic\Salsify\TypeHandler;

use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataObject;

/**
 * Class RelationHandler
 * @package Dynamic\Salsify\TypeHandler
 */
class RelationHandler extends Extension
{
    /**
     * @var array
     */
    private static $field_types = [
        'Relation'
    ];

    /**
     * @param $data
     * @param $dataField
     * @param $config
     * @param $dbField
     * @param string |\SilverStripe\ORM\DataObject $class
     * @return string|int
     *
     * @return int|DataObject
     */
    public function handleRelationType($data, $dataField, $config, $dbField, $class)
    {

    }


}
