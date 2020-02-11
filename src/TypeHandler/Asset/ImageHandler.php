<?php

namespace Dynamic\Salsify\TypeHandler\Asset;

use Dynamic\Salsify\Task\ImportTask;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataObject;

/**
 * Class ImageHandler
 * @package Dynamic\Salsify\TypeHandler
 *
 * @property-read \Dynamic\Salsify\Model\Mapper|ImageHandler $owner
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
     * @param string|DataObject $class
     * @param $data
     * @param $dataField
     * @param $config
     * @param $dbField
     * @return string|int
     *
     * @throws \Exception
     */
    public function handleImageType($class, $data, $dataField, $config, $dbField)
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
     * @param string|DataObject $class
     * @param $data
     * @param $dataField
     * @param $config
     * @param $dbField
     * @return array
     *
     * @throws \Exception
     */
    public function handleManyImagesType($class, $data, $dataField, $config, $dbField)
    {
        $files = [];
        $fieldData = $data[$dataField];
        // convert to array to prevent problems
        if (!is_array($fieldData)) {
            $fieldData = [$fieldData];
        }

        foreach ($this->owner->yieldSingle($fieldData) as $fileID) {
            $entryData = array_merge($data, [
                $dataField => $fileID
            ]);
            $files[] = $this->owner->handleImageType($class, $entryData, $dataField, $config, $dbField, $class);
        }
        return $files;
    }
}
