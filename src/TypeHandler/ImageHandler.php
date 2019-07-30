<?php

namespace Dynamic\Salsify\TypeHandler;


use SilverStripe\ORM\DataObject;

/**
 * Class ImageHandler
 * @package Dynamic\Salsify\TypeHandler
 *
 * @property-read \Dynamic\Salsify\Model\Mapper|\Dynamic\Salsify\TypeHandler\ImageHandler $owner
 */
class ImageHandler extends FileHandler
{
    /**
     * @var array
     */
    private static $field_types = [
        'Image'
    ];

    private static $defualtImageType = 'png';

    /**
     * @param $value
     * @param $dbField
     * @param string |DataObject $class
     * @return string
     */
    public function handleImageType($value, $field, $class)
    {
        $data = $this->getAssetBySalsifyID($value);
        if (!$data) {
            return '';
        }

        $url = $this->fixExtension($data['salsify:url']);
        $name = $this->fixExtension($data['salsify:name']);

        $asset = $this->updateFile(
            $data['salsify:id'],
            $data['salsify:updated_at'],
            $url,
            $name
        );
        return preg_match('/ID$/', $field) ? $asset->ID : $asset;
    }

    protected function fixExtension($string)
    {
        $supportedImageExtensions = Image::get_category_extensions(
            Image::singleton()->File->getAllowedCategories()
        );
        $extension = pathinfo($string)['extension'];

        if (in_array($extension, $supportedImageExtensions)) {
            return str_replace(
                '.' . $extension,
                '.' . $this->owner->config()->get('defualtImageType'),
                $string
            );
        }

        return $string;
    }
}
