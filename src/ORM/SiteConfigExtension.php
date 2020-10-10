<?php

namespace Dynamic\Salsify\ORM;

use Dynamic\Salsify\Model\MapperHash;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\HasManyList;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Class SiteConfigExtension
 * @package Dynamic\Salsify\ORM
 *
 * @method HasManyList MapperHashes()
 *
 * @property-read SiteConfig|SiteConfigExtension $owner
 */
class SiteConfigExtension extends DataExtension
{
    /**
     * @var array
     */
    private static $has_many = [
        'MapperHashes' => MapperHash::class,
    ];
}
