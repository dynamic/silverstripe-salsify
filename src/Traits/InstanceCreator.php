<?php

namespace Dynamic\Salsify\Traits;

use Dynamic\Salsify\Model\Fetcher;
use Dynamic\Salsify\Model\Mapper;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

/**
 * Trait InstanceCreator
 */
trait InstanceCreator
{
    /**
     * @var Fetcher
     */
    private $fetcher;

    /**
     * @var Mapper
     */
    private $mapper;

    /**
     * @var Member
     */
    private $previousUser;

    /**
     * @return string
     */
    abstract protected function getImporterKey();

    /**
     * @return string
     */
    protected function getMapperInstanceString()
    {
        return Mapper::class . '.' . $this->getImporterKey();
    }

    /**
     * @return string
     */
    protected function getFetcherInstanceString()
    {
        return Fetcher::class . '.' . $this->getImporterKey();
    }

    /**
     * @return \SilverStripe\ORM\DataObject|Member
     * @throws \SilverStripe\ORM\ValidationException
     */
    protected function findOrCreateSalsifyUser()
    {
        if ($member = Member::get()->filter('Email', 'salsify')->first()) {
            return $member;
        }

        $member = Member::create();
        $member->FirstName = 'Salsify';
        $member->Surname = 'Integration';
        $member->Email = 'salsify';
        $member->write();

        return $member;
    }

    /**
     *
     */
    protected function changeToSalsifyUser()
    {
        $this->previousUser = Security::getCurrentUser();
        return Security::setCurrentUser($this->findOrCreateSalsifyUser());
    }

    /**
     *
     */
    protected function changeToPreviousUser()
    {
        Security::setCurrentUser($this->previousUser);
        $this->previousUser = null;
    }

    /**
     * @param $className
     * @return bool
     */
    protected function hasService($className)
    {
        return Injector::inst()->has($className . '.' . $this->getImporterKey());
    }

    public function createServices()
    {
        if (!Injector::inst()->has($this->getMapperInstanceString())) {
            Injector::inst()->load([
                $this->getMapperInstanceString() => [
                    'class' => Mapper::class,
                ],
            ]);
        }
        if (!Injector::inst()->has($this->getFetcherInstanceString())) {
            Injector::inst()->load([
                $this->getFetcherInstanceString() => [
                    'class' => Fetcher::class,
                ],
            ]);
        }
    }

    /**
     * @return Fetcher
     * @throws \Exception
     */
    public function getFetcher()
    {
        if (!$this->fetcher) {
            $this->setFetcher();
        }
        return $this->fetcher;
    }

    /**
     * @throws \Exception
     */
    protected function setFetcher()
    {
        $this->fetcher = Injector::inst()->createWithArgs($this->getFetcherInstanceString(), [
            'importerKey' => $this->getImporterKey(),
            'noChannel' => property_exists($this, 'noChannel') ? $this->noChannel : false,
        ]);
    }

    /**
     * @return Mapper
     * @throws \Exception
     */
    public function getMapper()
    {
        if (!$this->mapper) {
            $this->setMapper();
        }
        return $this->mapper;
    }

    /**
     * @throws \Exception
     */
    protected function setMapper()
    {
        $this->mapper = Injector::inst()->createWithArgs($this->getMapperInstanceString(), [
            'importerKey' => $this->getImporterKey(),
            'file' => property_exists($this, 'file') ? $this->file : null,
        ]);

        $configKeys = [
            'apiKey',
            'timeout',
            'organizationID',
        ];
        for ($i = 0; $i < count($configKeys); $i++) {
            $currentKey = $configKeys[$i];
            $fetcherConfig = $this->getFetcher()->config()->get($currentKey);
            $this->mapper->config()->set($currentKey, $fetcherConfig);
        }
    }
}
