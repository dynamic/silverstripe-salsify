<?php

namespace Dynamic\Salsify\TypeHandler;

use SilverStripe\Core\Extension;

/**
 * Class BooleanHandler
 * @package Dynamic\Salsify\TypeHandler
 */
class BooleanHandler extends Extension
{
    /**
     * @var array
     */
    private static $field_types = [
        'Boolean' => [
            'requiresWrite' => false,
            'requiresSalsifyObjects' => false,
            'allowsModification' => true,
        ],
    ];

    /**
     * @param string|SilverStripe\ORM\DataObject $class
     * @param $data
     * @param $dataField
     * @param $config
     * @param $dbField
     * @return string|int
     *
     * @return string|boolean|int|double
     */
    public function handleBooleanType($class, $data, $dataField, $config, $dbField)
    {
        return $this->isTrue($data[$dataField]);
    }

    /**
     * @param $val
     * @param bool $return_null
     * @return bool|mixed|null
     *
     * FROM https://www.php.net/manual/en/function.boolval.php#116547
     */
    public function isTrue($val, $return_null = false)
    {
        $boolval = (bool)$val;

        if (is_string($val)) {
            $boolval = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        if ($boolval === null && !$return_null) {
            return false;
        }

        return $boolval;
    }
}
