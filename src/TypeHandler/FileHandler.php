<?php

namespace Dynamic\Salsify\TypeHandler;

use JsonMachine\JsonMachine;
use SilverStripe\Assets\File;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataObject;

/**
 * Class FileHandler
 * @package Dynamic\Salsify\TypeHandler
 *
 * @property-read \Dynamic\Salsify\Model\Mapper|\Dynamic\Salsify\TypeHandler\FileHandler $owner
 */
class FileHandler extends Extension
{
    /**
     * @var array
     */
    private static $field_types = [
        'File'
    ];

    /**
     * @var JsonMachine
     */
    protected $assetStream;

    /**
     * @param $value
     * @param $field
     * @param string |DataObject $class
     * @return string
     *
     * @throws \Exception
     */
    public function handleFileType($value, $field, $class)
    {
        $data = $this->getAssetBySalsifyID($value);
        if (!$data) {
            return '';
        }

        $asset = $this->updateFile(
            $data['salsify:id'],
            $data['salsify:updated_at'],
            $data['salsify:url'],
            $data['salsify:name']
        );
        return preg_match('/ID$/', $field) ? $asset->ID : $asset;
    }

    /**
     * @param $id
     * @return array|bool
     */
    protected function getAssetBySalsifyID($id)
    {
        if (is_array($id)) {
            $id = $id[count($id) - 1];
        }

        $asset = false;
        foreach ($this->owner->getAssetStream() as $name => $data) {
            if ($data['salsify:id'] == $id) {
                $asset = $data;
            }
        }
        $this->owner->resetAssetStream();
        return $asset;
    }

    /**
     * @param string $id
     * @param string|DataObject $class
     * @return File|\Dyanmic\Salsify\ORM\FileExtension
     */
    protected function findOrCreateFile($id, $class = File::class)
    {
        /** @var File|\Dyanmic\Salsify\ORM\FileExtension $file */
        if ($file = $class::get()->find('SalisfyID', $id)) {
            return $file;
        }

        $file = $class::create();
        $file->SalisfyID = $id;
        return $file;
    }

    /**
     * @param int|string $id
     * @param string $updatedAt
     * @param string $url
     * @param string $name
     * @param string|DataObject $class
     *
     * @return File|bool
     * @throws \Exception
     */
    protected function updateFile($id, $updatedAt, $url, $name, $class = File::class)
    {
        $file = $this->findOrCreateFile($id, $class);
        if ($file->SalsifyUpdatedAt && $file->SalsifyUpdatedAt == $updatedAt) {
            return $file;
        }

        $file->SalsifyUpdatedAt = $updatedAt;
        $file->setFromStream(fopen($url, 'r'), $name);

        $file->write();
        return $file;
    }
}
