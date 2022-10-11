# SilverStripe Salsify
![CI](https://github.com/dynamic/silverstripe-salsify/workflows/CI/badge.svg)
[![Build Status](https://travis-ci.com/dynamic/silverstripe-salsify.svg?branch=master)](https://travis-ci.com/dynamic/silverstripe-salsify)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/dynamic/silverstripe-salsify/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/dynamic/silverstripe-salsify/?branch=master)
[![codecov](https://codecov.io/gh/dynamic/silverstripe-salsify/branch/master/graph/badge.svg)](https://codecov.io/gh/dynamic/silverstripe-salsify)

[![Latest Stable Version](https://poser.pugx.org/dynamic/silverstripe-shopify/v/stable)](https://packagist.org/packages/dynamic/silverstripe-shopify)
[![Total Downloads](https://poser.pugx.org/dynamic/silverstripe-shopify/downloads)](https://packagist.org/packages/dynamic/silverstripe-shopify)
[![Latest Unstable Version](https://poser.pugx.org/dynamic/silverstripe-shopify/v/unstable)](https://packagist.org/packages/dynamic/silverstripe-shopify)
[![License](https://poser.pugx.org/dynamic/silverstripe-shopify/license)](https://packagist.org/packages/dynamic/silverstripe-shopify)

Salsify integration for SilverStripe websites.

## Requirements

* SilverStripe ^4.0

## Installation

```
composer require dynamic/silverstripe-salsify
```

## License
See [License](license.md)

## Table of Contents
- [Running the task](#running-the-task)
- [Example configuration](#example-configuration)
    - [Extensions](#extensions)
      - [SalsifyIDExtension](#salsifyidextension)
      - [SalsifyFetchExtension](#salsifyfetchextension)
    - [Importer](#importer)
    - [Fetcher](#fetcher)
    - [Mapper](#mapper)
      - [Unique Fields](#unique-fields)
      - [Field Types](#field-types)
        - [Raw](#raw)
        - [SalsifyID, SalsifyUpdatedAt, and SalsifyRelationsUpdatedAt](#salsifyid-salsifyupdatedat-and-salsifyrelationsupdatedat)
        - [Boolean](#boolean)
          - [isTrue](#isTrue)
        - [Literal](#literal)
        - [Files and Images](#files-and-images)
          - [Image Transformation](#image-transformation)
        - [HasOne and HasMany](#hasone-and-hasmany)
          - [HasOne Example](#hasone-example)
          - [ManyRelation Example](#manyrelation-example)
        - [Salsify Relations](#salsify-relations)
      - [Field Fallback](#field-fallback)
      - [Keeping Field Values Without a Salsify Field](#keeping-field-values-without-a-salsify-field)
      - [Extending onBeforeMap](#extending-onbeforemap)
      - [Extending onAfterMap](#extending-onaftermap)
      - [Extending beforeObjectWrite](#extending-beforeobjectwrite)
      - [Extending afterObjectWrite](#extending-afterobjectwrite)
      - [Advanced](#advanced)
         - [Custom Field Types](#custom-field-types)
         - [Skipping Objects](#skipping-objects)
         - [Modify Field Data](#modify-field-data)
         - [Attributes in Group as DataObjects](#attributes-in-group-as-dataobjects)
  - [Single Object Import](#single-object-import)
- [Troubleshooting](#troubleshooting)
- [Maintainers](#maintainers)
- [Bugtracker](#bugtracker)
- [Development and contribution](#development-and-contribution)

## Running the task
The task can be run from a browser window or the command line.
To run the task in the browser go to `dev/tasks` and find `Import products from salsify` or visit `dev/tasks/SalsifyImportTask`.

It is recommended to use the command line, because the task can easily time out in a browser.
To run the task in the command line sake must be installed and the command `sake dev/tasks/SalsifyImportTask` must be run.

## Example configuration
### Extensions
#### SalsifyIDExtension
It is recommended to add `Dynamic\Salsify\ORM\SalsifyIDExtension` as an extension of any object being mapped to.
It will add a `SalsifyID` and `SalsifyUpdatedAt` field that can be mapped to.
The `SalsifyID` field is used in single object updates.

```yaml
MyObject:
  extensions:
    - Dynamic\Salsify\ORM\SalsifyIDExtension
```

The `SalsifyID` and `SalsifyUpdatedAt` fields will still need to be explicitly mapped in the mapper config.

```yaml
Dynamic\Salsify\Model\Mapper.example:
  mapping:
    \Page:
      SalsifyID:
        salsifyField: 'salsify:id'
        unique: true
      SalsifyUpdatedAt: 'salsify:updated_at'
```

#### SalsifyFetchExtension
The `SalsifyFetchExtension` is automatically added to left and main.
It will provide a button on data objects that have a salsify mapping to refetch.

To have the button force update `refetch_force_update` and `refetch_force_update_relations` can be set to true on the data object being mapped to.
When set to true `refetch_force_update` will force update any non-relation field in salsify.
When set to true `refetch_force_update_relations` will force update any relation field in salsify.

```yaml
\Page:
  refetch_force_update: true
  refetch_force_update_relations: true
```

See the [Single Object Import](#single-object-import) section for more setup information.

### Importer
Importers will run fetchers and mappers. Each importer needs to be passed an importerKey to its constructor.
For the rest of the readme `example` will be used for the services.
```yaml
SilverStripe\Core\Injector\Injector:
  Dynamic\Salsify\Model\Importer.example:
    class: Dynamic\Salsify\Model\Importer
    constructor:
      importerKey: example
```

### Fetcher
To set up a fetcher an api key and a channel id need to be provided.
The `apiKey` can be in the root Fetcher config, but can also be overridden in a specific service config.
The channel id can be found by visiting the channel in Salsify and copying the last section of the url.
`https://app.salsify.com/app/orgs/<org_id>/channels/<channel_id>`
To find the api key follow [this](https://developers.salsify.com/reference#token-based-authentication)
```yaml
Dynamic\Salsify\Model\Fetcher:
  apiKey: 'api key here'

Dynamic\Salsify\Model\Fetcher.example:
    channel: 'channel id'
```
or
```yaml
Dynamic\Salsify\Model\Fetcher.example:
  apiKey: 'api key here'
  channel: 'channel id'
```

An organization ID can also be included to avoid an account having access to multiple organizations.
```yaml
Dynamic\Salsify\Model\Fetcher:
  organizationID: 'org id'
```
or
```yaml
Dynamic\Salsify\Model\Fetcher.example:
  organizationID: 'org id'
```

https://developers.salsify.com/docs/organization-id

The fetcher can also have the timeout changed for http requests.
This is not a timeout for Salsify to generate an export.
Timeout is in milliseconds and defaults to 2000 ms or 2 seconds.
Like an `apiKey` the timeout can be set in the root fetcher config and be overridden by a service config.
```yaml
Dynamic\Salsify\Model\Fetcher:
  timeout: 4000
```
or
```yaml
Dynamic\Salsify\Model\Fetcher.example:
  timeout: 4000
```

### Mapper
To set up a mapper, which will map fields from Salsify to SilverStripe, some configuration is needed.

```yaml
Dynamic\Salsify\Model\Mapper.example:
  mapping:
    \Page:
       SKU:
        salsifyField: SKU
        unique: true
       Title: Product Title
```

Each mapper instance will need a service config.
Under the `mapping` config one or more classes can be specified for import.
Each class can have one or more fields to map, and must have at least one that is unique.
All fields have the key of the SilverStripe field to map to.
`Title: Product Title` will map `Product Title` from Salsify to `Title` in SilverStripe.

#### Unique Fields
Like non-unique fields, the key is the SilverStripe field to map to.
`salsifyField` is the field from Salsify to map.
`unique` is either true or false and will be used as a filter to check against existing records.
```yaml
Dynamic\Salsify\Model\Mapper.example:
  mapping:
    \Page:
      SKU:
        salsifyField: SKU
        unique: true
```
The unique fields will be added to an array, with the values for each product and will be used as a filter to find existing.
This allows for multiple compound unique fields.

#### Field Types
The built in field types are `Raw`, `Literal`, `File`, `Image`, `HasOne`, `HasMany`.
There are some specialized field types that are `SalsifyID`, `SalsifyUpdatedAt`, `SalsifyRelationsUpdatedAt` that are meant for mapping to specific fields without modifications.
More types can also be added.

##### Raw
By default the `Raw` type is used. It will write the salsify property value into the object field.
```yaml
Dynamic\Salsify\Model\Mapper.example:
  example:
    mapping:
      \Page:
        Title: 'Web Title'
```

#### SalsifyID, SalsifyUpdatedAt, and SalsifyRelationsUpdatedAt
Fields that have these types cannot be modified, but will be handled like a raw type.
```yaml
Dynamic\Salsify\Model\Mapper.example:
  example:
    mapping:
      \Page:
        SalsifyID:
          salsifyField: 'salsify:id'
          type: SalsifyID
        SalsifyUpdatedAt:
          salsifyField: 'salsify:updated_at'
          type: SalsifyUpdatedAt
        SalsifyRelationsUpdatedAt:
          salsifyField: 'salsify:relations_updated_at'
          type: SalsifyRelationsUpdatedAt
```

##### Boolean
Useful for mapping Yes/No value fields from salsify to the boolean database type.
```yaml
Dynamic\Salsify\Model\Mapper.example:
  example:
    mapping:
      \Page:
        Obsolete:
          salsifyField: 'obsolete'
          type: Boolean
```

###### isTrue
The boolean handler also comes with a handy `isTrue` helper method.
This is helpful when modifications or skipping has to be done on a Yes/No formatted property.

##### Literal
To set a field that doesn't have a salsify field a literal field can be used.
```yaml
Dynamic\Salsify\Model\Mapper.example:
  example:
    mapping:
      \Page:
        Author:
          value: 'Chris P. Bacon'
          type: Literal
```
The above example will set the Author field to `Chris P. Bacon` for all mapped pages.

##### Files and Images
To get an image or file from salsify to map to an object a type needs to be specified.

```yaml
Dynamic\Salsify\Model\Mapper.example:
  example:
    mapping:
      \Page:
        FrontImage:
          salsifyField: Front Image
          type: Image
```

Images and files can also be mapped by ID.
```yaml
Dynamic\Salsify\Model\Mapper.example:
  mapping:
    \Page:
      FrontImageID:
        salsifyField: Front Image
        type: Image
```

If the mapping is specified as an image and it is not a valid image extension,
salsify will be used to try and convert the file into a png.

###### Image Transformation
To cut down on 500 errors caused by trying to resize images when visiting a page images can be transformed by salsify.
When an image transformation is updated in the config it will also re-download the image with the new transformations.

```yaml
Dynamic\Salsify\Model\Mapper.example:
  mapping:
    \Page:
      FrontImageID:
        salsifyField: Front Image
        type: Image
        transform:
          - 'c_fit'
          - 'w_1000'
          - 'h_1000'
          - 'dn_300'
          - 'cs_srgb'
```

The above will download `http://a1.images.salsify.com/image/upload/c_fit,w_1000,h_1000,dn_300,cs_srgb/sample.jpg` instead of `http://a1.images.salsify.com/image/upload/sample.jpg`.

To see what transformations salsify supports please visit https://getstarted.salsify.com/help/transforming-image-files.

It is recommended to do this to all large files.

##### HasOne and HasMany
has_one and has_many relations can be done just about the same.
The `HasOne`'s `salsifyField` doesn't matter.
The `ManyRelation` type requires a salsify field that is an array.
`ManyRelation` can also have a sort column specified.
All modifications to the data will be passed through to the mapping relation.

###### HasOne example:
```yaml
Dynamic\Salsify\Model\Mapper.example:
  mapping:
    \Page:
      Document:
        salsifyField: 'salsify:id'
        type: 'HasOne'
        relation:
          \Documentobject:
            Title: Document Title
```

###### ManyRelation example:
```php
namespace {
    use SilverStripe\CMS\Model\SiteTree;

    class Page extends SiteTree
    {

        /**
         * @var array
         */
        private static $many_many = [
            'Features' => Feature::class,
        ];

        private static $many_many_extraFields = [
            'Features' => [
                'SortOrder'=> 'Int',
            ],
        ];
    }
}
```

```php
<?php

namespace {
    use \Page;
    use SilverStripe\ORM\DataObject;

    /**
     * Class Feature
     *
     * @property string Name
     */
    class Feature extends DataObject
    {
        /**
         * @var string
         */
        private static $table_name = 'Feature';

        /**
         * @var array
         */
        private static $db = [
            'Name' => 'Varchar(100)',
        ];

        /**
         * @var array
         */
        private static $belongs_many_many = [
            'Pages' => Page::class,
        ];

        /**
         * @var array
         */
        private static $indexes = [
            'Name' => [
                'type' => 'unique',
                'columns' => ['Name'],
            ],
        ];
    }
}
```

```php
<?php

namespace {

    use SilverStripe\Core\Extension;

    /**
     * Class SalsifyExtension
     */
    class SalsifyExtension extends Extension
    {
        /**
         * @param string|\SilverStripe\ORM\DataObject $class
         * @param string $dbField
         * @param array $config
         * @param array $data
         *
         * @return array
         */
        public function featureModifier($class, $dbField, $config, $data)
        {
            $features = [];
            foreach ($this->owner->config()->get('featureFields') as $featuredField) {
                if (array_key_exists($featuredField, $data) && $this->is_true($data[$featuredField])) {
                    $features[] = [
                        'FeatureName' => $featuredField,
                    ];
                }
            }
            $data['Features'] = $features;
            return $data;
        }

        /**
         * @param $val
         * @param bool $return_null
         * @return bool|mixed|null
         *
         * FROM https://www.php.net/manual/en/function.boolval.php#116547
         */
        private function is_true($val, $return_null = false)
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
}
```

```yaml
Dynamic\Salsify\Model\Mapper.example:
  extensions:
    - SalsifyExtension
  featureFields:
      - "Feature One"
      - "Feature Two"
  mapping:
    \Page:
      Features:
        salsifyField: 'Features'
        type: 'ManyRelation'
        modification: 'featureModifier'
        sortColumn: 'SortOrder'
        relation:
          Feature:
            Name:
              salsifyField: 'FeatureName'
              unique: true
```

###### Salsify Relations
Relationships between products can also be created in salsify.
By default it will map to a `has_many` and `many_many` relation.
The `salsifyField` is the name of the relation type.
```yaml
Dynamic\Salsify\Model\Mapper.example:
  mapping:
    \Page:
      RelatedProducts:
        salsifyField: 'You May Also Like'
        type: 'SalsifyRelation'
```

To map to a `has_one` relation a single object can be returned.
```yaml
Dynamic\Salsify\Model\Mapper.example:
  mapping:
    \Page:
      AlternateProduct:
        salsifyField: 'Alternate'
        type: 'SalsifyRelation'
        single: true
```

#### Field Fallback
A fallback field can be specified for salsify.
The fallback will be used when the normal `salsifyField` is not in the data from salsify for the object being mapped.
The fallback can be a string or array.
```yaml
Dynamic\Salsify\Model\Mapper.example:
  mapping:
    \Page:
      Title:
        salsifyField: 'Product Web Title'
        fallback: 'Product Title'
```
or
```yaml
Dynamic\Salsify\Model\Mapper.example:
  mapping:
    \Page:
      Title:
        salsifyField: 'Product Web Title'
        fallback:
          - 'Product Title'
          - 'SKU'
```

#### Keeping Field Values Without a Salsify Field
By default all values that are to be mapped will wipe values if there is no salsify field in the data for an object.
This can be changed so it keeps values for a field.
```yaml
Dynamic\Salsify\Model\Mapper.example:
  mapping:
    \Page:
      Title:
        salsifyField: 'Product Web Title'
        keepExistingValue: true
```

This will keep the previous value of the title, even if the field is no longer in the data.

#### Extending onBeforeMap
`onBeforeMap` is run after the fetcher runs, but before the mapper starts to map.
It is passed the file url from the channel export and if the map is of multiple or a single product.

```yaml
Dynamic\Salsify\Model\Mapper.example:
  extensions:
    - ExampleFeatureExtension
```

```php
<?php
namespace {
    use SilverStripe\Core\Extension;
    use JsonMachine\JsonMachine;

    /**
     * Class ExampleFeatureExtension
     */
    class ExampleFeatureExtension extends Extension
    {
        /**
         * Gets all the attributes that are in a field group and sets them in the mapper's config
         * @param $file
         * @param bool $multiple
         */
        public function onBeforeMap($file, $multiple)
        {
            $attributes = [];
            $attributeStream = JsonMachine::fromFile($file, '/1/attributes');
            foreach ($this->owner->yieldSingle($attributeStream) as $attribute) {
                if (array_key_exists('salsify:attribute_group', $attribute)) {
                    if ($attribute['salsify:attribute_group'] == 'Product Features') {
                        $attributes[] = $attribute['salsify:id'];
                    }
                }
            }

            $this->owner->config()->set('featureFields', $attributes);
        }
    }
}
```

#### Extending onAfterMap
`onAfterMap` is run after the fetcher runs, but before the mapper starts to map.
It is passed the file url from the channel export and if the map is of multiple or a single product.
This extension point is good for cleaning up products that are not in the channel export.

```yaml
Dynamic\Salsify\Model\Mapper.example:
  extensions:
    - ExampleCleanUpExtension
```

```php
<?php
namespace {
    use SilverStripe\Core\Extension;
    use JsonMachine\JsonMachine;
    use Dynamic\Salsify\Task\ImportTask;
    use Dynamic\Salsify\Model\Mapper;

    /**
     * Class ExampleCleanUpExtension
     */
    class ExampleCleanUpExtension extends Extension
    {
        /**
         * @param $file
         * @param bool $multiple
         */
        public function onAfterMap($file, $multiple)
        {
            // don't clean up on a single product import
            if ($multiple == Mapper::$SINGLE) {
                return;
            }

            $productStream = JsonMachine::fromFile($file, '/4/products');
            $productCodes = [];

            foreach ($this->owner->yieldKeyVal($productStream) as $name => $data) {
                $productCodes[] = $data['salsify:id'];
            }

            $invalidProducts = Product::get()->exclude([
                'SalsifyID' => $productCodes,
            ]);

            $count = 0;
            foreach ($this->owner->yieldSingle($invalidProducts) as $invalidProduct) {
                /** @var Product $invalidProduct */
                $invalidProduct->doArchive();
                $count++;
            }
            ImportTask::output("Archived {$count} products");
        }
    }
}
```

#### Extending beforeObjectWrite
This extension point is good for detecting which fields were changed.
This is helpful to create redirects for pages if a parent has changed during mapping.

```yaml
Dynamic\Salsify\Model\Mapper.example:
  extensions:
    - ExampleRedirectExtension
```

```php
<?php
namespace {
    use SilverStripe\Core\Extension;
    use SilverStripe\ORM\DataObject;
    use SilverStripe\RedirectedURLs\Model\RedirectedURL;
    use Dynamic\Salsify\Task\ImportTask;

    /**
     * Class ExampleRedirectExtension
     */
    class ExampleRedirectExtension extends Extension
    {
        /**
         * This will create a redirect if a page's parent changes
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

            if (!array_key_exists('ParentID', $changed) && !array_key_exists('URLSegment', $changed)) {
                return;
            }

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

            $redirect = RedirectedURL::create();
            $redirect->RedirectCode = 301;
            $redirect->FromBase = preg_replace('/\?.*/', '', $oldParent->Link($oldSegment));
            $redirect->LinkToID = $objectID;
            $redirect->write();
            ImportTask::output("Created redirect from {$redirect->FromBase} to {$redirect->LinkTo()->Link()}");
        }
    }
}
```

#### Extending afterObjectWrite
To publish an object after mapping the `afterObjectWrite` method can be extended.
It is passed the DataObject that was written, if the object was in the database, and if the object was published.
If the object does not have the versioned extension applied `$wasPublished` will be false.

```yaml
Dynamic\Salsify\Model\Mapper.example:
  extensions:
    - ExamplePublishExtension
```

```php
<?php
namespace {
    use SilverStripe\Core\Extension;
    use SilverStripe\Versioned\Versioned;

    /**
     * Class ExamplePublishExtension
     */
    class ExamplePublishExtension extends Extension
    {
        /**
         * This will publish all new mapped objects and mapped objects that are already published.
         * @param DataObject|Versioned $object
         * @param bool $wasWritten
         * @param bool $wasPublished
         */
        public function afterObjectWrite($object, $wasWritten, $wasPublished)
        {
            if ($object->hasExtension(Versioned::class)) {
                if (!$wasWritten || $wasPublished) {
                    $object->publishRecursive();
                }
            }
        }
    }
}
```

#### Advanced
##### [Custom Field Types](docs/en/custom-types.md)
##### [Skipping Objects](docs/en/mapper-skip-object.md)
##### [Modify Field Data](docs/en/mapper-field-modifier.md)
##### [Attributes in Group as DataObjects](docs/en/attribute-group-as-objects.md)

### Single Object Import
Adding a re-fetch button in the cms requires some configuration.
An organization is required to fetch a single product.
A `SalsifyID` field is also required for single object imports.
Only a mapping service with the name of `single` is required and will act just like a normal mapper config; however, a fetcher service config can also be defined to specify the organization.

```yaml
Dynamic\Salsify\Model\Fetcher.single:
  organizationID: 'org id'

Dynamic\Salsify\Model\Mapper.single:
  mapping:
    \Page:
      SalsifyID:
        salsifyField: 'salsify:id'
        unique: true
      SKU: SKU
      Title: GTIN Name
      FrontImage:
        salsifyField: Front Image
        type: Image
```

To use the single object mapper as a normal importer, when running the task, an `Importer` service.
```yaml
SilverStripe\Core\Injector\Injector:
  Dynamic\Salsify\Model\Importer.single:
    class: Dynamic\Salsify\Model\Importer
    constructor:
      importerKey: single
```

For more configuration options see [SalsifyFetchExtension](#salsifyfetchextension)

## Troubleshooting
### Some fields are not importing
If some fields are not importing please make sure they show up in the data.
Occasionally properties will have a different id and name, in this case the data will show up under the property id.

## Maintainers
 * Dynamic <dev@dynamicagency.com>

## Bugtracker
Bugs are tracked in the issues section of this repository. Before submitting an issue please read over
existing issues to ensure yours is unique.

If the issue does look like a new bug:

 - Create a new issue
 - Describe the steps required to reproduce your issue, and the expected outcome. Unit tests, screenshots
 and screencasts can help here.
 - Describe your environment as detailed as possible: SilverStripe version, Browser, PHP version,
 Operating System, any installed SilverStripe modules.

Please report security issues to the module maintainers directly. Please don't file security issues in the bugtracker.

## Development and contribution
If you would like to make contributions to the module please ensure you raise a pull request and discuss with the module maintainers.
