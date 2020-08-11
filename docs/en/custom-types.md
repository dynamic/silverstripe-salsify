## Custom Field Types
Custom field types can be used in mappers.
It is recommended to apply field type extensions to the base `Dynamic\Salsify\Model\Mapper` config.
```yaml
Dynamic\Salsify\Model\Mapper:
  extensions:
    - CustomHandler
```

All custom field types must be added to the `field_types` configuration.
Every new type must have an array for a config.
Possible configuration keys are `requiresWrite`, `requiresSalsifyObjects`, `allowsModification`, and `fallback`.
 - `requiresWrite` will force the object to write before saving to a field if true. Defualts to `false`.
 - `requiresSalsifyObjects` will write to a field during the second mapping loop if true. This is useful for salsify relations, or other types that would require all mapped objects to be written. Defualts to `false`.
 - `allowsModification` will skip any field modifications if false. Defaults to `true`.
 - `fallback` will allow a field type to fallback to another field type if a handler is not found. The original type's config will be used instead of the fallback's. Good for types that require a slightly different config, but don't need different processing from an existing type.

To handle a field type a method must be used that is named in a specific pattern.
The pattern is `handle{TYPE_HERE}Type` where `{TYPE_HERE}` is replaced with the key added to the `field_types`.
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
        'Custom' => [
            'requiresWrite' => false,
            'requiresSalsifyObjects' => false,
            'allowsModification' => true,
        ],
        // this would fallback to Custom, but would not run field modifications
        'CustomNoModification' => [
            'requiresWrite' => false,
            'requiresSalsifyObjects' => false,
            'allowsModification' => false,
            'fallback' => 'Custom',
        ],
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
