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
        'Image' => [
            'requiresWrite' => true,
            'requiresSalsifyObjects' => false,
            'allowsModification' => true,
        ],
        'ManyImages' => [
            'requiresWrite' => true,
            'requiresSalsifyObjects' => false,
            'allowsModification' => true,
        ],
    ];

    /**
     * @var string
     */
    private static $defaultImageType = 'png';

    /**
     * @param string $url
     * @param array|string $transformations
     */
    public function getImageTransformation($url, $transformations)
    {
        if (!is_array($transformations)) {
            $transformations = [$transformations];
        }

        return implode(',', $transformations);
    }

    /**
     * @param string $url
     * @param string $transform
     */
    public function getImageTransformationURL($url, $transform)
    {
        $filePath = preg_split('|^(.*[\\\/])|', $url, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY)[0];
        $fileName = basename($url);

        return "{$filePath}{$transform}/{$fileName}";
    }

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
        $transformation = '';

        if (array_key_exists('transform', $config)) {
            $transformation = $this->getImageTransformation($url, $config['transform']);
            $url = $this->getImageTransformationURL($url, $transformation);
        }

        $asset = $this->updateFile(
            $assetData['salsify:id'],
            $assetData['salsify:updated_at'],
            $url,
            $name,
            Image::class,
            $transformation
        );

        return preg_match('/ID$/', $dbField) ? $asset->ID : $asset;
    }

    protected function fixExtension($string)
    {
        $supportedImageExtensions = Image::get_category_extensions(
            Image::singleton()->File->getAllowedCategories()
        );

        $pathinfo = pathinfo($string);
        $defualtExtension = $this->owner->config()->get('defaultImageType');
        if (!array_key_exists('extension', $pathinfo)) {
            return "{$string}.{$defualtExtension}";
        }

        $extension = pathinfo($string)['extension'];

        if (!in_array($extension, $supportedImageExtensions)) {
            return str_replace(
                ".{$extension}",
                ".{$defualtExtension}",
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
