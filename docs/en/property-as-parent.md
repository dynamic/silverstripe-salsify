# Using a Property To Assign a Parent
```yaml
Dynamic\Salsify\Model\Mapper:
  extensions:
    - SalsifyParentMapper
  mapping:
    \Product:
      ParentID:
        salsifyField: 'Category'
        modification: 'parentMofdifier'
```

```php
use \SilverStripe\Core\Extension;

/**
 * Class SalsifyParentMapper
 */
class SalsifyParentMapper extends Extension
{
    /**
     * @param string|DataObject $class
     * @param string $dbField
     * @param array $config
     * @param array $data
     * @return array
     * @throws \SilverStripe\ORM\ValidationException
     */
    public function parentMofdifier($class, $dbField, $config, $data)
    {
        //Debug::show($data);
        $parent = false;

        // if the current class is not product, do not modify data
        if ($class !== Product::class) {
            return $data;
        }

        // check to see if the data has the right field
        if (isset($data[$config['salsifyField']])) {
            $field = $data[$config['salsifyField']];
            $parent = ProductCategory::get()->filter('Title', $field)->first();

            // If parent category wasn't found, create it
            if (!$parent) {
                $parent = ProductCategory::create();
                $parent->Title = $field;
                $parent->write();
                $parent->publishRecursive();
            }
        } else {
            $parent = ProductCategory::get()->filter('Title', 'No Category')->first();
            if (!$parent) {
                $parent = ProductCategory::create();
                $parent->Title = 'No Category';
                $parent->write();
            }
        }

        // set the field to the parent id
        if ($parent) {
            $data[$config['salsifyField']] = $parent->ID;
        }
        return $data;
    }
}
```

```php
/**
 * Class ProductCategory
 */
class ProductCategory extends \Page
{
    /**
     * @var string
     */
    private static $table_name = 'ProductCategory';
}

/**
 * Class Product
 *
 * @mixin \Dynamic\Salsify\ORM\SalsifyIDExtension
 */
class Product extends \Page
{
    /**
     * @var string
     */
    private static $table_name = 'Product';

    /**
     * @var string[]
     */
    private static $extensions = [
        \Dynamic\Salsify\ORM\SalsifyIDExtension::class,
    ];
}
```
