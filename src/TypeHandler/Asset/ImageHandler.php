<?php

namespace Dynamic\Salsify\TypeHandler\Asset;

use Dynamic\Salsify\Task\ImportTask;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataObject;

/**
 * Class ImageHandler
 * @package Dynamic\Salsify\TypeHandler
 *
 * @property-read \Dynamic\Salsify\Model\Mapper|\Dynamic\Salsify\TypeHandler\Asset\ImageHandler $owner
 */
class ImageHandler extends AssetHandler
{
    /**
     * @var array
     */
    private static $field_types = [
        'Image',
        'ManyImages',
    ];

    /**
     * @var string
     */
    private static $defaultImageType = 'png';

    /**
     * @param $data
     * @param $dataField
     * @param $config
     * @param $dbField
     * @param string |DataObject $class
     * @return string|int
     *
     * @throws \Exception
     */
    public function handleImageType($data, $dataField, $config, $dbField, $class)
    {
        $assetData = $this->getAssetBySalsifyID($data[$dataField]);
        if (!$assetData) {
            return '';
        }

        $url = $this->fixExtension($assetData['salsify:url']);
        $name = $this->fixExtension($assetData['salsify:name']);

        $asset = $this->updateFile(
            $assetData['salsify:id'],
            $assetData['salsify:updated_at'],
            $url,
            $name,
            Image::class
        );
        return preg_match('/ID$/', $dbField) ? $asset->ID : $asset;
    }

    protected function fixExtension($string)
    {
        $supportedImageExtensions = Image::get_category_extensions(
            Image::singleton()->File->getAllowedCategories()
        );
        $extension = pathinfo($string)['extension'];

        if (!in_array($extension, $supportedImageExtensions)) {
            return str_replace(
                '.' . $extension,
                '.' . $this->owner->config()->get('defaultImageType'),
                $string
            );
        }

        return $string;
    }

    /**
     * @param $data
     * @param $dataField
     * @param $config
     * @param $dbField
     * @param string |DataObject $class
     * @return array
     *
     * @throws \Exception
     */
    public function handleManyImagesType($data, $dataField, $config, $dbField, $class)
    {
        $files = [];
        $fieldData = $data[$dataField];
        foreach ($fieldData as $fileID) {
            $entryData = array_merge($data, [
                $dataField => $fileID
            ]);
            $files[] = $this->owner->handleImageType($entryData, $dataField, $config, $dbField, $class);
        }
        return $files;
    }
}
