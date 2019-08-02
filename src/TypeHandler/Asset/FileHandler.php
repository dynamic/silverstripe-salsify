<?php

namespace Dynamic\Salsify\TypeHandler\Asset;

use SilverStripe\ORM\DataObject;

/**
 * Class FileHandler
 * @package Dynamic\Salsify\TypeHandler
 *
 * @property-read \Dynamic\Salsify\Model\Mapper|\Dynamic\Salsify\TypeHandler\Asset\FileHandler $owner
 */
class FileHandler extends AssetHandler
{
    /**
     * @var array
     */
    private static $field_types = [
        'File'
    ];

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
    public function handleFileType($data, $dataField, $config, $dbField, $class)
    {
        $data = $this->getAssetBySalsifyID($data[$dataField]);
        if (!$data) {
            return '';
        }

        $asset = $this->updateFile(
            $data['salsify:id'],
            $data['salsify:updated_at'],
            $data['salsify:url'],
            $data['salsify:name']
        );
        return preg_match('/ID$/', $dbField) ? $asset->ID : $asset;
    }
}
