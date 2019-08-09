<?php

namespace Dynamic\Salsify\Traits;

use Dynamic\Salsify\Model\Fetcher;
use Dynamic\Salsify\Model\Mapper;
use SilverStripe\Core\Injector\Injector;

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
     * @return string
     */
    protected abstract function getImporterKey();

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
     * @param $className
     * @return bool
     */
    protected function hasService($className)
    {
        return Injector::inst()->has($className . '.' . $this->getImporterKey());
    }

    protected function createServices()
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
    }
}
