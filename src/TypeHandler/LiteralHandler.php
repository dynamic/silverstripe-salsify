<?php

namespace Dynamic\Salsify\TypeHandler;

use SilverStripe\Core\Extension;

/**
 * Class LiteralHandler
 * @package Dynamic\Salsify\TypeHandler
 *
 * @property-read \Dynamic\Salsify\Model\Mapper|LiteralHandler $owner
 */
class LiteralHandler extends Extension
{
    /**
     * @var array
     */
    private static $field_types = [
        'Literal',
    ];

    /**
     * @param string|SilverStripe\ORM\DataObject $class
     * @param $data
     * @param $dataField
     * @param $config
     * @param $dbField
     *
     * @return string|boolean|int|double
     */
    public function handleLiteralType($class, $data, $dataField, $config, $dbField)
    {
        return $dataField;
    }
}
