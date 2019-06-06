<?php

namespace Dynamic\Salsify\Model;

use Dynamic\Salsify\Task\ImportTask;
use JsonMachine\JsonMachine;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataObject;

/**
 * Class Mapper
 * @package Dynamic\Salsify\Model
 */
class Mapper
{
    use Configurable;
    use Extensible;
    use Injectable;

    /**
     * @var array
     */
    private static $field_types = [
        'RAW' => '0',
        'FILE' => '1',
        'IMAGE' => '2',
    ];

    /**
     * @var
     */
    private $file;

    /**
     * @var JsonMachine
     */
    private $productStream;

    /**
     * @var JsonMachine
     */
    private $assetStream;

    /**
     * @var array
     */
    private $currentUniqueFields;

    /**
     * @var int
     */
    private $importCount = 0;

    /**
     * Mapper constructor.
     * @param $file
     */
    public function __construct($file)
    {
        $this->file = $file;
        $this->productStream = JsonMachine::fromFile($file, '/4/products');
        $this->resetAssetStream();
    }

    /**
     * Maps the data
     */
    public function map()
    {
        foreach ($this->productStream as $name => $data) {
            foreach ($this->config()->get('mapping') as $class => $mappings) {
                $this->mapToObject($class, $mappings, $data);
                $this->currentUniqueFields = [];
            }
        }
        ImportTask::echo("Imported and updated $this->importCount products.");
    }

    /**
     * @param string $class
     * @param array $mappings
     * @param array $data
     */
    private function mapToObject($class, $mappings, $data)
    {
        $object = $this->findObjectByUnique($class, $mappings, $data);
        if (!$object) {
            $object = $class::create();
        }

        $fieldTypes = $this->config()->get('field_types');

        $firstUniqueKey = array_keys($this->uniqueFields($class, $mappings))[0];
        $firstUniqueValue = $data[$mappings[$firstUniqueKey]['salsifyField']];
        ImportTask::echo("Updating $firstUniqueKey $firstUniqueValue");

        foreach ($mappings as $dbField => $salsifyField) {
            $type = $this->config()->get('field_types')['RAW'];
            if (is_array($salsifyField)) {
                if (!array_key_exists('salsifyField', $salsifyField)) {
                    continue;
                }

                if (array_key_exists('type', $salsifyField)) {
                    if (array_key_exists($salsifyField['type'], $fieldTypes)) {
                        $type = $fieldTypes[$salsifyField['type']];
                    }
                }

                $salsifyField = $salsifyField['salsifyField'];
            }

            if (!array_key_exists($salsifyField, $data)) {
                ImportTask::echo("Skipping mapping for field $salsifyField for $firstUniqueKey $firstUniqueValue");
                continue;
            }

            $object->$dbField = $this->handleType($type, $data[$salsifyField]);
        }

        if ($object->isChanged()) {
            $object->write();
            $this->importCount++;
        } else {
            ImportTask::echo("$firstUniqueKey $firstUniqueValue was not changed.");
        }
    }

    /**
     * @param string $class
     * @param array $mappings
     * @param array $data
     *
     * @return \SilverStripe\ORM\DataObject
     */
    private function findObjectByUnique($class, $mappings, $data)
    {
        $uniqueFields = $this->uniqueFields($class, $mappings);
        $filter = [];
        foreach ($uniqueFields as $dbField => $salsifyField) {
            $filter[$dbField] = $data[$salsifyField];
        }

        return DataObject::get($class)->filter($filter)->first();
    }

    /**
     * @param string $class
     * @param array $mappings
     * @return array
     */
    private function uniqueFields($class, $mappings)
    {
        if (!empty($this->currentUniqueFields)) {
            return $this->currentUniqueFields;
        }

        $uniqueFields = [];
        foreach ($mappings as $dbField => $salsifyField) {
            if (!is_array($salsifyField)) {
                continue;
            }

            if (!array_key_exists('unique', $salsifyField) ||
                !array_key_exists('salsifyField', $salsifyField)) {
                continue;
            }

            if (!$salsifyField['unique'] == true) {
                continue;
            }

            $uniqueFields[$dbField] = $salsifyField['salsifyField'];
        }

        $this->currentUniqueFields = $uniqueFields;
        return $uniqueFields;
    }

    /**
     * @param int $type
     * @param string|int $value
     * @return mixed
     */
    private function handleType($type, $value)
    {
        $fieldTypes = $this->config()->get('field_types');
        switch ($type) {
            case $fieldTypes['RAW']:
                return $value;

            case $fieldTypes['FILE']:
                if ($asset = $this->createFile($this->getAssetBySalsifyID($value))) {
                    return $asset;
                }
            case $fieldTypes['IMAGE']:
                $asset = $this->getAssetBySalsifyID($value);
                $url = $asset['salsify:url'];
                $name = $asset['salsify:name'];
                if (!in_array($asset['salsify:format'], Image::getAllowedExtensions())) {
                    $url = str_replace('.' . $asset['salsify:format'], '.png', $asset['salsify:url']);
                    $name = str_replace('.' . $asset['salsify:format'], '.png', $asset['salsify:name']);
                }
                $file = $this->findOrCreateFile($asset['salsify:id'], Image::class);
                $file->setFromStream(fopen($url, 'r'), $name);
                $file->write();
        }
        return '';
    }

    /**
     * @param $id
     * @return array|bool
     */
    private function getAssetBySalsifyID($id)
    {
        if (is_array($id)) {
            $id = $id[count($id) - 1];
        }

        $asset = false;
        foreach ($this->assetStream as $name => $data) {
            if ($data['salsify:id'] == $id) {
                $asset = $data;
            }
        }
        $this->resetAssetStream();
        return $asset;
    }

    /**
     * @param array|bool $assetData
     * @param string $class
     * @return File|bool
     */
    private function createFile($assetData)
    {
        if (!$assetData) {
            return false;
        }

        /** @var File|\Dyanmic\Salsify\ORM\FileExtension $file */
        $file = $this->findOrCreateFile($assetData['salsify:id']);
        $file->setFromStream(fopen($assetData['salsify:url'], 'r'), $assetData['salsify:name']);

        $file->write();
        return $file;
    }

    /**
     * @param string $id
     * @param string $class
     * @return File
     */
    private function findOrCreateFile($id, $class = File::class)
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
     *
     */
    private function resetAssetStream()
    {
        $this->assetStream = JsonMachine::fromFile($this->file, '/3/digital_assets');
    }
}
