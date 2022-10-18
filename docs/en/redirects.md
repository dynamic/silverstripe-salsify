## Redirects
To automatically create redirects when URLSegments or parent pages change a redirect module is needed.
The following example uses [silverstripe/redirectedurls](https://github.com/silverstripe/silverstripe-redirectedurls)

```yaml
Dynamic\Salsify\Model\Mapper:
  extensions:
    - SalsifyRedirectMapper
  mapping:
    \Product:
        Title: GTIN Name
```

```php
use \SilverStripe\Core\Extension;
use SilverStripe\RedirectedURLs\Model\RedirectedURL;
use Dynamic\Salsify\Task\ImportTask;
use SilverStripe\ORM\DataObject;


/**
 * Class SalsifyParentMapper
 */
class SalsifyRedirectMapper extends Extension
{
    /**
     * @param DataObject $object
     */
    public function beforeObjectWrite($object)
    {
        if (!$object instanceof \Page) {
            return;
        }

        if (!$object->isChanged()) {
            return;
        }

        $changed = $object->getChangedFields(false, DataObject::CHANGE_VALUE);

        // if neither the parent or the url segment got updated, skip
        if (!array_key_exists('ParentID', $changed) && !array_key_exists('URLSegment', $changed)) {
            return;
        }

        // get the old segments. will default to using the current values if unchanged
        $oldParent = $object->ParentID;
        $oldSegment = $object->URLSegment;
        if (array_key_exists('ParentID', $changed)) {
            $parent = $changed['ParentID'];
            $oldParent = $parent['before'];
        }

        if (array_key_exists('URLSegment', $changed)) {
            $segment = $changed['URLSegment'];
            $oldSegment = $segment['before'];
        }

        // create the redirect
        $this->createRedirect($oldParent, $oldSegment, $object->ID);
    }

    /**
     * @param \Page|int $oldParent
     * @param string $oldSegment
     * @param int $objectID
     */
    private function createRedirect($oldParent, $oldSegment, $objectID)
    {
        if (is_int($oldParent)) {
            $oldParent = \Page::get()->byID($oldParent);
        }

        if (!$oldParent) {
            return;
        }

        $redirect = RedirectedURL::create();
        // moved permanantly
        $redirect->RedirectCode = 301;
        // skip any GET parameters
        $redirect->FromBase = preg_replace('/\?.*/', '', $oldParent->Link($oldSegment));
        $redirect->LinkToID = $objectID;
        $redirect->write();
        ImportTask::output("Created redirect for {$redirect->FromBase}");
    }
}
```

```php
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
    private static $extensions = [
        \Dynamic\Salsify\ORM\SalsifyIDExtension::class,
    ];
}
```
