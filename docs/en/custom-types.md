## Custom Field Types
Custom field types can be used in mappers. 
It is recommended to apply field type extensions to the base `Dynamic\Salsify\Model\Mapper` config.
```yaml
Dynamic\Salsify\Model\Mapper:
  extensions:
    - CustomHandler
```

All custom field types must be added to the `field_types` configuration.
To handle a field type a method must be used that is named in a specific pattern.
The pattern is `handle{TYPE_HERE}Type` where `{TYPE_HERE}` is replaced with the value added to the `field_types`.
So a handler method for `Image` would be `hadleImageType`.
The method is passed the object data, data field to map to, configuration for the field, the database field name, and the class the field is applied to.

```php
<?php

use \SilverStripe\Core\Extension;

/**
 * Class CustomHandler
 * Slightly modified from the RawHandler
 */
class CustomHandler extends Extension
{
    /**
     * @var array
     */
    private static $field_types = [
        'Custom'
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
    public function handleCustomType($data, $dataField, $config, $dbField, $class)
    {
        $value = $data[$dataField];
        if (!is_array($value)) {
            return $value;
        }

        $db = $class::config()->get('db');
        foreach ($db as $fieldTitle => $fieldType) {
            if ($dbField === $fieldTitle && $fieldType === 'HTMLText') {
                return '<p>' . implode('<p></p>', $value) . '</p>';
            }
        }

        return implode('\r\n', $value);
    }
}

```
