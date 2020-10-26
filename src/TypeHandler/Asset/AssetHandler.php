<?php

namespace Dynamic\Salsify\TypeHandler\Asset;

use Dynamic\Salsify\Model\Fetcher;
use Dynamic\Salsify\ORM\ImageDataExtension;
use Dynamic\Salsify\Traits\Yieldable;
use GuzzleHttp\Client;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataObject;

/**
 * Class AssetHandler
 * @package Dynamic\Salsify\TypeHandler\Asset
 *
 * @property-read \Dynamic\Salsify\Model\Mapper|AssetHandler $owner
 */
class AssetHandler extends Extension
{

    /**
     * @param $id
     * @return array
     * @throws \Exception
     */
    protected function fetchAsset($id)
    {
        $apiKey = $this->owner->config()->get('apiKey');//Config::inst()->get(Fetcher::class, 'apiKey');
        $timeout = $this->owner->config()->get('timeout');
        $orgID = $this->owner->config()->get('organizationID');

        $url = "v1/orgs/{$orgID}/digital_assets/{$id}";

        $client = new Client([
            'base_uri' => Fetcher::API_BASE_URL,
            'timeout' => $timeout,
            'http_errors' => false,
            'verify' => true,
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);

        $response = $client->get($url);
        return json_decode($response->getBody(), true);
    }

    /**
     * @param $id
     * @return array|bool
     * @throws \Exception
     */
    protected function getAssetBySalsifyID($id)
    {
        if (is_array($id)) {
            $id = $id[0];
        }

        if ($this->owner->hasFile() === false) {
            return $this->fetchAsset($id);
        }

        $asset = false;
        $assetGenerator = $this->owner->yieldKeyVal($this->owner->getAssetStream(), $this->owner->resetAssetStream());
        foreach ($assetGenerator as $name => $data) {
            if ($data['salsify:id'] == $id) {
                $asset = $data;
                $assetGenerator->send(Yieldable::$STOP_GENERATOR);
            }
        }
        return $asset;
    }

    /**
     * @param string $id
     * @param string|DataObject $class
     * @return File|\Dynamic\Salsify\ORM\SalsifyIDExtension
     */
    protected function findOrCreateFile($id, $class = File::class)
    {
        /** @var File|\Dynamic\Salsify\ORM\SalsifyIDExtension $file */
        if ($file = $class::get()->find('SalsifyID', $id)) {
            return $file;
        }

        $file = $class::create();
        $file->SalsifyID = $id;
        return $file;
    }

    /**
     * @param int|string $id
     * @param string $updatedAt
     * @param string $url
     * @param string $name
     * @param string|DataObject $class
     * @param string $transformation
     *
     * @return File|bool
     * @throws \Exception
     */
    protected function updateFile($id, $updatedAt, $url, $name, $class = File::class, $transformation = '')
    {
        $file = $this->findOrCreateFile($id, $class);
        if ($file->SalsifyUpdatedAt && $file->SalsifyUpdatedAt == $updatedAt) {
            if (!$this->isTransformOutOfDate($file, $class, $transformation)) {
                return $file;
            }
        }

        $file->SalsifyUpdatedAt = $updatedAt;
        if ($file->hasField('Transformation')) {
            $file->Transformation = $transformation;
        }
        $file->setFromStream(fopen($url, 'r'), $name);

        $published = $file->isPublished();
        $file->write();

        if ($published) {
            $file->publishSingle();
        }
        return $file;
    }

    /**
     * @param DataObject $file
     * @param string $class
     * @param string $transformation
     *
     * @return bool
     */
    private function isTransformOutOfDate($file, $class, $transformation)
    {
        if (!$file instanceof Image) {
            return false;
        }

        /** @var Image|ImageDataExtension $file */
        return $file->Transformation != $transformation;
    }
}
