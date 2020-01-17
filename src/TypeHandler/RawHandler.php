<?php

namespace Dynamic\Salsify\TypeHandler;

use Dynamic\Salsify\Traits\Yieldable;
use SilverStripe\Core\Extension;

/**
 * Class RawHandler
 */
class RawHandler extends Extension
{
    use Yieldable;

    /**
     * @var array
     */
    private static $field_types = [
        'Raw'
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
    public function handleRawType($class, $data, $dataField, $config, $dbField)
    {
        $value = $data[$dataField];
        if (!is_array($value)) {
            return $value;
        }

        $db = $class::config()->get('db');
        foreach ($this->yieldKeyVal($db) as $fieldTitle => $fieldType) {
            if ($dbField === $fieldTitle && $fieldType === 'HTMLText') {
                return '<p>' . implode('<p></p>', $value) . '</p>';
            }
        }

        return implode('\r\n', $value);
    }
}
