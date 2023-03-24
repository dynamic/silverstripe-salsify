<?php

namespace Dynamic\Salsify\TypeHandler\Asset;

use Dynamic\Salsify\Model\Fetcher;
use Dynamic\Salsify\ORM\FileDataExtension;
use Dynamic\Salsify\ORM\ImageDataExtension;
use Dynamic\Salsify\ORM\SalsifyIDExtension;
use Dynamic\Salsify\Traits\Yieldable;
use GuzzleHttp\Client;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Config\Config;
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

        $client->setUserAgent(Config::inst()->get(Fetcher::class, 'user-agent'));

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
                $owner = $this->owner;
                $assetGenerator->send($owner::$STOP_GENERATOR);
            }
        }
        return $asset;
    }

    /**
     * @param string $id
     * @param string $type
     * @param string|DataObject $class
     * @return File|SalsifyIDExtension|FileDataExtension
     */
    protected function findOrCreateFile($id, $type, $class = File::class)
    {
        $filter = [
            'SalsifyID' => $id,
            'Type' => $type,
        ];
        /** @var File|SalsifyIDExtension|FileDataExtension $file */
        if ($file = $class::get()->filter($filter)->first()) {
            return $file;
        }

        // TODO - remove at a later date
        $filter = [
            'SalsifyID' => $id,
            'Type' => null,
        ];
        if ($file = $class::get()->filter($filter)->first()) {
            // checks for changes from image to file before trying to update
            if (!($class === File::class && $file->getClassName() !== File::class)) {
                $file->Type = $type;
                return $this->writeFile($file);
            }
        }
        // end of TODO removal

        $file = $class::create();
        $file->SalsifyID = $id;
        $file->Type = $type;
        return $file;
    }

    /**
     * @param int|string $id
     * @param string $updatedAt
     * @param string $url
     * @param string $name
     * @param string $type
     * @param string|DataObject $class
     * @param string $transformation
     *
     * @return File|bool
     * @throws \Exception
     */
    protected function updateFile($id, $updatedAt, $url, $name, $type, $class = File::class, $transformation = '')
    {
        $file = $this->findOrCreateFile($id, $type, $class);
        if ($file->SalsifyUpdatedAt && $file->SalsifyUpdatedAt == $updatedAt) {
            if (!$this->isTransformOutOfDate($file, $class, $transformation)) {
                return $file;
            }
        }

        $file->SalsifyUpdatedAt = $updatedAt;
        if ($file->hasExtension(ImageDataExtension::class)) {
            /** @var ImageDataExtension|Image|File $file */
            $file->Transformation = $transformation;
        }

        $context = stream_context_create([
            'http' => [
                'header' => 'User-Agent: ' . Config::inst()->get(Fetcher::class, 'user-agent'),
            ],
        ]);

        $file->setFromStream(fopen($url, 'r', false, $context), $name);

        return $this->writeFile($file);
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

    /**
     * @param File $file
     * @return File
     */
    private function writeFile($file)
    {
        $published = $file->isPublished();
        $file->write();

        if ($published) {
            $file->publishSingle();
        }

        return $file;
    }
}
