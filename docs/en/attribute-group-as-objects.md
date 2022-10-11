## Attributes in Group as DataObjects
It can be useful to have a full attribute group create individual DataObjects or pages.
The following code will map attributes in the group `Product Features` to `Feature` DataObjects.

The following configuration will add all the product features as DataObjects and will properly relate a feature to a product.

```yaml
Dynamic\Salsify\Model\Mapper:
  extensions:
    - SalsifyAttributeMapper
  mapping:
    \Product:
      Feature:
        salsifyField: 'Feature'
        type: 'HasOne'
        relation:
          \Feature:
            Title:
              salsifyField: 'Feature'
              unique: true
```

```php
use \SilverStripe\Core\Extension;
use JsonMachine\JsonMachine;

/**
 * Class SalsifyAttributeMapper
 */
class SalsifyAttributeMapper extends Extension
{
    /**
     * @param $file
     */
    public function onBeforeMap($file)
    {
        // load the attributes section of the export
        $attributeStream = JsonMachine::fromFile($file, '/1/attributes');
        foreach ($this->owner->yieldSingle($attributeStream) as $attribute) {
            // if attribute is not in a group, skip it
            if (!array_key_exists('salsify:attribute_group', $attribute)) {
                continue;
            }

            // if an attribute is not in the correct group, skip it
            if ($attribute['salsify:attribute_group'] != 'Product Features') {
                continue;
            }

            // if found, modify otherwise create
            if ($feature = Feature::get()->find('SalsifyID', $attribute['salsify:id'])) {
                $feature->Title = $attribute['salsify:name'];
            } else {
                $feature = Feature::create();
                $feature->SalsifyID = $attribute['salsify:id'];
                $feature->Title = $attribute['salsify:name'];
            }

            // if changed, write
            if ($feature->isChanged()) {
                $feature->write();
            }
        }
    }
}
```

```php
use SilverStripe\ORM\DataObject;

/**
 * Class Feature
 *
 * @property string $Title
 * @property string $SalsifyID
 */
class Feature extends DataObject
{
    /**
     * @var string
     */
    private static $table_name = 'Feature';

    /**
     * @var string[]
     */
    private static $db = [
        'Title' => 'Varchar(100)',
        'SalsifyID' => 'Varchar(100)',
    ];

    /**
     * @var string[]
     */
    private static $has_many = [
        'Products' => \Product::class,
    ];
}
```

```php
/**
 * Class Product
 *
 * @property int $FeatureID
 * @method \Feature Feature()
 */
class Product extends \Page
{
    /**
     * @var array
     */
    private static $has_one = [
        'Feature' => \Feature::class;
    ];
}
```
