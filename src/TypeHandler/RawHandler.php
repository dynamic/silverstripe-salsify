<?php

namespace Dynamic\Salsify\TypeHandler;

/**
 * Class RawHandler
 */
class RawHandler extends \SilverStripe\Core\Extension
{
    /**
     * @var array
     */
    private static $field_types = [
        'Raw'
    ];

    /**
     * @param $value
     * @param string $field
     * @param string|\SilverStripe\ORM\DataObject $class
     * @return string|boolean|int|double
     */
    public function handleRawType($value, $field, $class)
    {
        if (!is_array($value)) {
            return $value;
        }

        $db = $class::config()->get('db');
        foreach ($db as $fieldTitle => $fieldType) {
            if ($field === $fieldTitle && $fieldType === 'HTMLText') {
                return '<p>' . implode('<p></p>', $value) . '</p>';
            }
        }

        return implode('\r\n', $value);
    }
}
