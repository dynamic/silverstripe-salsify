<?php

namespace Dynamic\Salsify\ORM;

use Dynamic\Salsify\Model\Fetcher;
use Dynamic\Salsify\Model\Mapper;
use Dynamic\Salsify\Task\ImportTask;
use Dynamic\Salsify\Traits\InstanceCreator;
use GuzzleHttp\Client;
use SilverStripe\Admin\LeftAndMainExtension;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;

/**
 * Class LeftAndMainExtension
 * @package Dynamic\Salsify\ORM
 * @property-read \SilverStripe\Admin\LeftAndMain|\Dynamic\Salsify\ORM\SalsifyFetchExtension $owner
 */
class SalsifyFetchExtension extends LeftAndMainExtension
{
    use InstanceCreator;

    /**
     * @var bool
     */
    private $noChannel = true;

    /**
     * @var array
     */
    private static $allowed_actions = [
        'salsifyFetch',
    ];

    /**
     * @return string
     */
    protected function getImporterKey()
    {
        return 'single';
    }

    /**
     *
     */
    public function onBeforeInit()
    {
        $this->createServices();
    }

    /**
     * @return boolean
     * @throws \Exception
     */
    public function canFetchSalsify()
    {
        $className = $this->owner->currentPage()->getClassName();

        // Only allow when product has a salsify id and has a single mapping config
        if (
            $this->owner->currentPage()->SalsifyID &&
            $this->hasService(Mapper::class) &&
            $this->configContainsMapping($className) &&
            $this->getFetcher()->config()->get('organizationID')
        ) {
            return true;
        }
        return false;
    }

    /**
     * @param string $className
     *
     * @return boolean
     * @throws \Exception
     */
    private function configContainsMapping($className)
    {
        if (!$this->getMapper()->config()->get('mapping')) {
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
     * @throws \Exception
     */
    private function getClassMapping($className)
    {
        $mapping = $this->getMapper()->config()->get('mapping');
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
        /** @var DataObject|\Dynamic\Salsify\ORM\SalsifyIDExtension $record */
        $record = DataObject::get_by_id($className, $id);
        if ($record && !$record->canEdit()) {
            return Security::permissionFailure();
        }

        if (!$record || !$record->SalsifyID) {
            $this->owner->httpError(404, "Bad salsify ID: $id");
        }

        ImportTask::config()->remove('output');
        $data = $this->fetchProduct($record->SalsifyID);

        $this->changeToSalsifyUser();
        $this->mapData($record, $data);
        $this->changeToPreviousUser();

        $this->owner->getResponse()->addHeader(
            'X-Status',
            rawurlencode(_t(__CLASS__ . '.UPDATED', 'Updated.'))
        );
        return $this->owner->getResponseNegotiator()->respond($this->owner->getRequest());
    }

    /**
     * @param string $salsifyID
     * @return array|NULL
     * @throws \Exception
     */
    private function fetchProduct($salsifyID)
    {

        $apiKey = $this->getFetcher()->config()->get('apiKey');
        $timeout = $this->getFetcher()->config()->get('timeout');
        $orgID = $this->getFetcher()->config()->get('organizationID');

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

        if ($response->getStatusCode() == 404) {
            $this->owner->httpError(404, "Bad salsify ID: $salsifyID");
        }

        return json_decode($response->getBody(), true);
    }

    /**
     * @param DataObject $record
     * @param array $data
     * @throws \Exception
     */
    private function mapData($record, $data)
    {
        $forceUpdate = Config::inst()->get(
            $this->owner->currentPage()->getClassName(),
            'refetch_force_update'
        );
        $this->getMapper()->mapToObject(
            $record->getClassName(),
            $this->getClassMapping($record->getClassName()),
            $data,
            $record,
            false,
            $forceUpdate
        );

        $forceUpdateRelations = Config::inst()->get(
            $this->owner->currentPage()->getClassName(),
            'refetch_force_update_relations'
        );
        $this->getMapper()->mapToObject(
            $record->getClassName(),
            $this->getClassMapping($record->getClassName()),
            $data,
            $record,
            true,
            $forceUpdateRelations
        );
    }
}
