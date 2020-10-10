<?php

namespace Dynamic\Salsify\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Class MapperHash
 * @package Dynamic\Salsify\Model
 *
 * @property string MapperHash
 * @property string MapperService
 *
 * @property int SiteConfigID
 * @method SiteConfig SiteConfig()
 */
class MapperHash extends DataObject
{

    /**
     * @var string
     */
    private static $table_name = 'SalsifyMapperHash';

    /**
     * @var array
     */
    private static $db = [
        'MapperHash' => 'Varchar',
        'MapperService' => 'Varchar',
    ];

    /**
     * @var array
     */
    private static $has_one = [
        'SiteConfig' => SiteConfig::class,
    ];
}
