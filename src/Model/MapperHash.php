<?php

namespace Dynamic\Salsify\Model;

use Dynamic\Salsify\ORM\SalsifyIDExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Class MapperHash
 * @package Dynamic\Salsify\Model
 *
 * @property string MapperHash
 * @property string MapperService
 * @property bool ForRelations
 *
 * @property int MappedObjectID
 * @method DataObject|SalsifyIDExtension MappedObject()
 *
 * @property string MappedObjectClass
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
        'ForRelations' => 'Boolean',
    ];

    /**
     * @var array
     */
    private static $has_one = [
        'MappedObject' => DataObject::class,
    ];
}
