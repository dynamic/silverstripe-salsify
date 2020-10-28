<?php

namespace Dynamic\Salsify\ORM;

use SilverStripe\Assets\File;
use SilverStripe\ORM\DataExtension;

/**
 * Class FileDataExtension
 * @package Dynamic\Salsify\ORM
 *
 * @property string Type
 *
 * @property-read File|FileDataExtension $owner
 */
class FileDataExtension extends DataExtension
{

    /**
     * @var array
     */
    private static $db = [
        'Type' => 'Varchar(255)',
    ];
}
