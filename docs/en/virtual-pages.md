# Virtual Pages, Creating and Removing

Sometimes it is useful to automatically create a virtual page when a page is created.

```yaml
Dynamic\Salsify\Model\Mapper:
  extensions:
    - SalsifyVirtualPageMapper
  mapping:
    \Product:
      SecondaryCategory: 'SecondaryCategory'
```

```php
use \SilverStripe\Core\Extension;
use SilverStripe\CMS\Model\VirtualPage;


/**
 * Class SalsifyParentMapper
 */
class SalsifyParentMapper extends Extension
{
    /**
     * @param DataObject|\SilverStripe\Versioned\Versioned $object
     * @param bool $wasWritten
     * @param bool $wasPublished
     */
    public function afterObjectWrite($object, $wasWritten, $wasPublished)
    {
        if (!$object->hasExtension(Versioned::class)) {
            return;
        }

        if (!$wasWritten || $wasPublished) {
            $object->publishRecursive();
        }

        if ($object instanceof Product) {
            if ($secondary = $object->SecondaryCategory) {
                $secondary_parent = ProductCategory::get()->filter('Title', $secondary)->first();
                // If the secondary category doesn't exist, create it
                if (!$secondary_parent) {
                    $landing = $this->getProductLanding();
                    $secondary_parent = ProductCategory::create();
                    $secondary_parent->Title = $secondary;
                    $secondary_parent->write();
                    $secondary_parent->publishRecursive();
                }

                // find an existing virtual page
                $virtual_object = VirtualPage::get()->filter([
                    'CopyContentFromID' => $object->ID,
                    'ParentID' => $secondary_parent->ID,
                ])->first();

                // If virtual page does not exist, create it
                if (!$virtual_object) {
                    $virtual_object = VirtualPage::create();
                    $virtual_object->CopyContentFromID = $object->ID;
                }

                $virtual_object->ParentID = $secondary_parent->ID;

                if ($virtual_object->isChanged()) {
                    $virtual_object->write();
                    if ($object->latestPublished()) {
                        $virtual_object->publishRecursive();
                    }
                }
            }
        }
    }
}
```

Some basic example pages used in the above example. The `ParentID` is built into pages.
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
 * @property string $SecondaryCategory
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
    private static $db = [
        'SecondaryCategory' => 'Varchar(255)',
    ];

    /**
     * @var string[]
     */
    private static $extensions = [
        \Dynamic\Salsify\ORM\SalsifyIDExtension::class,
    ];
}
```
