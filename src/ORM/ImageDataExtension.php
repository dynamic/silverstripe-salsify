<?php

namespace Dynamic\Salsify\ORM;

use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataExtension;

/**
 * Class ImageDataExtension
 * @package Dynamic\Salsify\ORM
 *
 * @property string Transformation
 *
 * @property-read ImageDataExtension|Image
 */
class ImageDataExtension extends DataExtension
{

    /**
     * @var array
     */
    private static $db = [
        'Transformation' => 'Text',
    ];
}
