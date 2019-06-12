<?php

namespace Dynamic\Salsify\Tests\TestOnly;

use SilverStripe\Assets\Image;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * Class MappedObject
 * @package Dynamic\Salsify\Tests\TestOnly
 *
 * @property string $Unique
 * @property string $Title
 * @property string $Seller
 */
class MappedObject extends DataObject implements TestOnly
{
    /**
     * @var string
     */
    private static $table_name = 'MappedObject';

    /**
     * @var array
     */
    private static $db = [
        'Unique' => 'Varchar',
        'Title' => 'Varchar',
        'Seller' => 'Varchar',
    ];

    /**
     * @var array
     */
    private static $has_one = [
        'Image' => Image::class,
    ];
}
