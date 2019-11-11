<?php

namespace Dynamic\Salsify\TypeHandler;

use SilverStripe\Core\Extension;

/**
 * Class LiteralHandler
 * @package Dynamic\Salsify\TypeHandler
 *
 * @property-read \Dynamic\Salsify\TypeHandler\LiteralHandler|\Dynamic\Salsify\Model\Mapper $owner
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
     * @param $data
     * @param $dataField
     * @param $config
     * @param $dbField
     * @param string |\SilverStripe\ORM\DataObject $class
     * @return string|int
     *
     * @return string|boolean|int|double
     */
    public function handleLiteralType($data, $dataField, $config, $dbField, $class)
    {
        return $dataField;
    }
}
