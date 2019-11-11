# Modify Field Data
## Configuration
To test if an object should be skipped a `shouldSkip` can be applied to a field configuration.
`shouldSkip` should reference a method in the Mapper class or an extension applied to the Mapper class.
```yaml
Dynamic\Salsify\Model\Mapper.example:
  extensions:
    - ExampleSkipExtension
  mapping:
    \Page:
      FrontImageID:
        salsifyField: Front Image
        type: Image
      Title:
          salsifyField: GTIN Name
          shouldSkip: shouldSkip
```

## Should Skip Method
The skip function is passed the database field, filed configuration, and the object data.
The skip function should return false if the object should not be skipped, true if it should.

```php
<?php

use SilverStripe\Core\Extension;
/**
 * Class TestModification
 */
class ExampleSkipExtension extends Extension
{
    /**
     * @param string $dbField
     * @param array $config
     * @param array $data
     * @return boolean
     */
    public function shouldSkip($dbField, $config, $data) {
        if ($dbField === 'Title') {
            // the salsify field in the config for te field
            $salsifyField = $config['salsifyField'];
            return $data[$salsifyField] === 'TEST';
        }
        return false;
    }
}
```

All objects with title fields that use this mapping for `\Page` will skip when equal to `TEST`.

## Prevent Skipping for Up To Date Objects
To prevent automatically skipping up to date objects `skipUpToDate` needs to be set to false in the mapper's config.

```yaml
Dynamic\Salsify\Model\Mapper.example:
  extensions:
    - ExampleSkipExtension
  skipUpToDate: false
  mapping:
    \Page:
      FrontImageID:
        salsifyField: Front Image
        type: Image
      Title:  GTIN Name
```
