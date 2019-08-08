<?php

namespace Dynamic\Salsify\TypeHandler\Asset;

use Dynamic\Salsify\Model\Fetcher;
use GuzzleHttp\Client;
use SilverStripe\Assets\File;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataObject;

/**
 * Class AssetHandler
 * @package Dynamic\Salsify\TypeHandler\Asset
 *
 * @property-read \Dynamic\Salsify\TypeHandler\Asset\AssetHandler|\Dynamic\Salsify\Model\Mapper $owner
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
        $apiKey = Config::inst()->get(Fetcher::class, 'apiKey');
        $timeout = Config::inst()->get(Fetcher::class, 'timeout');
        $orgID = Config::inst()->get(Fetcher::class, 'organizationID');

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
            $id = $id[count($id) - 1];
        }

        if ($this->owner->hasFile() === false) {
           return $this->fetchAsset($id);
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
     * @return File|\Dyanmic\Salsify\ORM\SalsifyIDExtension
     */
    protected function findOrCreateFile($id, $class = File::class)
    {
        /** @var File|\Dyanmic\Salsify\ORM\SalsifyIDExtension $file */
        if ($file = $class::get()->find('SalisfyID', $id)) {
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
