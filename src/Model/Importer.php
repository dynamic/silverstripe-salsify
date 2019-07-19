<?php

namespace Dynamic\Salsify\Model;

use Dynamic\Salsify\Task\ImportTask;
use InvalidArgumentException;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;

/**
 * Class Importer
 * @package Dynamic\Salsify\Model
 */
class Importer
{
    use Injectable;
    use Extensible;
    use Configurable;

    /**
     * @var string
     */
    protected $importerKey;

    /**
     * @var \Dynamic\Salsify\Model\Fetcher
     */
    protected $fetcher;

    /**
     * Importer constructor.
     * @param string $importerKey
     * @param \Dynamic\Salsify\Model\Fetcher $fetcher
     */
    public function __construct($importerKey, $fetcher = null)
    {
        if ($importerKey) {
            $this->setImporterKey($importerKey);
        }

        $this->fetcher = $fetcher;
    }

    /**
     * @return string
     */
    public function getImporterKey()
    {
        return $this->importerKey;
    }

    /**
     * @param string $importerKey
     * @return $this
     */
    public function setImporterKey($importerKey)
    {
        if (!is_string($importerKey)) {
            throw new InvalidArgumentException(sprintf(
                '%s importerKey must be a string',
                __CLASS__
            ));
        }
        if (empty($importerKey)) {
            throw new InvalidArgumentException(sprintf(
                '%s importerKey must cannot be empty',
                __CLASS__
            ));
        }
        if (preg_match('/[^A-Za-z0-9_-]/', $importerKey)) {
            throw new InvalidArgumentException(sprintf(
                '%s importerKey may only contain alphanumeric characters, dashes, and underscores',
                __CLASS__
            ));
        }
        $this->importerKey = $importerKey;
        return $this;
    }

    public function createServices()
    {
        /** @var string|Configurable $mapperService */
        $fetcherService = Fetcher::class . '.' . $this->getImporterKey();
        /** @var string|Configurable $mapperService */
        $mapperService = Mapper::class . '.' . $this->getImporterKey();

        if (!Injector::inst()->has($fetcherService)) {
            Injector::inst()->load([
                $fetcherService => [
                    'class' => Fetcher::class,
                ],
            ]);
        }

        if (!Injector::inst()->has($mapperService)) {
            Injector::inst()->load([
                $mapperService => [
                    'class' => Mapper::class,
                ],
            ]);
        }
    }

    /**
     * @throws \Exception
     */
    public function run()
    {
        $this->createServices();

        /** @var string|Configurable $mapperService */
        $fetcherService = Fetcher::class . '.' . $this->getImporterKey();
        /** @var string|Configurable $mapperService */
        $mapperService = Mapper::class . '.' . $this->getImporterKey();

        ImportTask::echo('-------------------');
        ImportTask::echo('Now running import for ' . $this->getImporterKey());

        if (
            !Config::forClass($fetcherService)->get('apiKey') &&
            !Config::forClass(Fetcher::class)->get('apiKey')
        ) {
            ImportTask::echo('No api key found');
            return;
        }

        if (!Config::forClass($fetcherService)->get('channel')) {
            ImportTask::echo('No channel found');
            return;
        }

        if (!Config::forClass($mapperService)->get('mapping')) {
            ImportTask::echo('No mappings found');
            return;
        }

        $fetcher = Injector::inst()->createWithArgs($fetcherService, [
            'importerKey' => $this->getImporterKey(),
        ]);

        $fetcher->startExportRun();
        ImportTask::echo('Started Salsify export.');
        $fetcher->waitForExportRunToComplete();
        ImportTask::echo('Salsify export complete');

        ImportTask::echo('Staring data import');
        ImportTask::echo('-------------------');
        $mapper = Injector::inst()->createWithArgs($mapperService, [
            'importerKey' => $this->getImporterKey(),
            'file' => $fetcher->getExportUrl(),
        ]);
        $mapper->map();
    }
}
