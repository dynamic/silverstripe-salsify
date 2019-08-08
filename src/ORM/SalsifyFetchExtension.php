<?php

namespace Dyanmic\Salsify\ORM;

use Dynamic\Salsify\Model\Fetcher;
use Dynamic\Salsify\Model\Mapper;
use Dynamic\Salsify\Task\ImportTask;
use GuzzleHttp\Client;
use SilverStripe\Admin\LeftAndMainExtension;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;

/**
 * Class LeftAndMainExtension
 * @package Dyanmic\Salsify\ORM
 * @property-read \SilverStripe\Admin\LeftAndMain|\Dyanmic\Salsify\ORM\SalsifyFetchExtension $owner
 */
class SalsifyFetchExtension extends LeftAndMainExtension
{

    /**
     * @var string
     */
    const MAPPER_INSTANCE = Mapper::class . '.single';

    /**
     * @var array
     */
    private static $allowed_actions = [
        'salsifyFetch',
    ];

    public function onBeforeInit()
    {
        if (!Injector::inst()->has($this::MAPPER_INSTANCE)) {
            Injector::inst()->load([
                $this::MAPPER_INSTANCE => [
                    'class' => Mapper::class,
                ],
            ]);
        }
    }

    /**
     * @return boolean
     */
    public function canFetchSalsify()
    {
        $className = $this->owner->currentPage()->getClassName();

        if (Injector::inst()->has($this::MAPPER_INSTANCE) && $this->configContainsMapping($className)) {
            return true;
        }
        return false;
    }

    /**
     * @param string $className
     *
     * @return boolean
     */
    private function configContainsMapping($className)
    {
        if (!Config::forClass($this::MAPPER_INSTANCE)->get('mapping')) {
            return false;
        }

        if (!$this->getClassMapping($className)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $className
     * @return bool|array
     */
    private function getClassMapping($className)
    {
        $mapping = Config::forClass($this::MAPPER_INSTANCE)->get('mapping');
        if (array_key_exists($className, $mapping)) {
            return $mapping[$className];
        }
        if (array_key_exists('\\' . $className, $mapping)) {
            return $mapping['\\' . $className];
        }
        return false;
    }

    /**
     * @param array $data
     * @param Form $form
     * @return \SilverStripe\Control\HTTPResponse
     * @throws \Exception
     */
    public function salsifyFetch($data, $form)
    {
        $className = $this->owner->currentPage()->getClassName();

        $id = $data['ID'];
        /** @var DataObject|\Dyanmic\Salsify\ORM\SalsifyIDExtension $record */
        $record = DataObject::get_by_id($className, $id);
        if ($record && !$record->canEdit()) {
            return Security::permissionFailure();
        }

        if (!$record || !$record->SalsifyID) {
            $this->owner->httpError(404, "Bad salsify ID: " . (int)$id);
        }

        ImportTask::config()->remove('output');
        $data = $this->fetchProduct($record->SalsifyID);
        $this->mapData($record, $data);

        $this->owner->getResponse()->addHeader(
            'X-Status',
            rawurlencode(_t(__CLASS__ . '.UPDATED', 'Updated.'))
        );
        return $this->owner->getResponseNegotiator()->respond($this->owner->getRequest());
    }

    /**
     * @param string $salsifyID
     * @return array|NULL
     */
    private function fetchProduct($salsifyID)
    {
        $apiKey = Config::inst()->get(Fetcher::class, 'apiKey');
        $timeout = Config::inst()->get(Fetcher::class, 'timeout');
        $orgID = Config::inst()->get(Fetcher::class, 'organizationID');

        $url = "v1/orgs/{$orgID}/products/{$salsifyID}";

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
     * @param DataObject $record
     * @param array $data
     * @throws \Exception
     */
    private function mapData($record, $data)
    {
        /** @var Mapper $mapper */
        $mapper = Injector::inst()->createWithArgs($this::MAPPER_INSTANCE, [
            'importerKey' => 'single',
        ]);

        $mapper->mapToObject($record->getClassName(), $this->getClassMapping($record->getClassName()), $data);
    }
}