# SilverStripe Salsify
[![codecov](https://codecov.io/gh/dynamic/silverstripe-salsify/branch/master/graph/badge.svg)](https://codecov.io/gh/dynamic/silverstripe-salsify)
[![Build Status](https://travis-ci.com/dynamic/silverstripe-salsify.svg?branch=master)](https://travis-ci.com/dynamic/silverstripe-salsify)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/dynamic/silverstripe-salsify/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/dynamic/silverstripe-salsify/?branch=master)

Salsify integration for SilverStripe websites.

## Requirements

* SilverStripe ^4.0

## Installation

```
composer require dynamic/silverstripe-salsify
```

## License
See [License](license.md)

## Example configuration
### Fetcher
To set up a fetcher an api key and a channel id need to be provided.
The channel id can be found by visiting the channel in Salsify and copying the last section of the url.
`https://app.salsify.com/app/orgs/<org_id>/channels/<channel_id>`
To find the api key follow [this](https://developers.salsify.com/reference#token-based-authentication)
```yaml
Dynamic\Salsify\Model\Fetcher:
  apiKey: 'api key here'
  channel: 'channel id'
```

The fetcher can also have the timout changed for http requests.
This is not a timout for Salsify to generate an export.
Timeout is in milliseconds and defaults to 2000 ms or 2 seconds.
```yaml
Dynamic\Salsify\Model\Fetcher:
  timeout: 2000
```

### Mapper
To set up a mapper, which will map fields from Salsify to SilverStripe, some configuration is needed.

```yaml
Dynamic\Salsify\Model\Mapper:
  mapping:
    \Page:
      SKU:
        salsifyField: SKU
        unique: true
      Title: Product Title
```

Under the `mapping` config one or more classes can be specified for import.
Each class can have one or more fields to map, and must have at least one that is unique.
All fields have the key of the SilverStripe field to map to.
`Title: Product Title` will map `Product Title` from Salsify to `Title` in SilverStripe.

#### Unique Fields
Like non-unique fields, the key is the SilverStripe field to map to.
`salsifyField` is the field from Salsify to map.
`unique` is either true or false and will be used as a filter to check against existing records.
```yaml
Dynamic\Salsify\Model\Mapper:
  mapping:
    \Page:
      SKU:
        salsifyField: SKU
        unique: true
```
The unique fields will be added to an array, with the values for each product and will be used as a filter to find existing.
This allows for multiple compound unique fields.

#### Files and Images
To get an image or file from salsify to map to an object a type needs to be specified.
Types can be `RAW`, `FILE`, and `IMAGE`.

```yaml
Dynamic\Salsify\Model\Mapper:
  mapping:
    \Page:
      FrontImage:
        salsifyField: Front Image
        type: IMAGE
```

Images and files can also be mapped by ID.
```yaml
Dynamic\Salsify\Model\Mapper:
  mapping:
    \Page:
      FrontImageID:
        salsifyField: Front Image
        type: IMAGE
```

If the mapping is specified as an image and it is not a valid image extension, 
salsify will be used to try and convert the file into a png.

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
