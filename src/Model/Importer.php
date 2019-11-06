<?php

namespace Dynamic\Salsify\Model;

use Dynamic\Salsify\Task\ImportTask;
use Dynamic\Salsify\Traits\InstanceCreator;
use InvalidArgumentException;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;

/**
 * Class Importer
 * @package Dynamic\Salsify\Model
 */
class Importer
{
    use Injectable;
    use Extensible;
    use Configurable;
    use InstanceCreator;

    /**
     * @var string
     */
    protected $importerKey;

    /**
     * @var
     */
    protected $file;

    /**
     * Importer constructor.
     * @param string $importerKey
     */
    public function __construct($importerKey)
    {
        if ($importerKey) {
            $this->setImporterKey($importerKey);
        }
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

    /**
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function run()
    {
        $this->createServices();

        /** @var string|Configurable $mapperService */
        $fetcherService = Fetcher::class . '.' . $this->getImporterKey();
        /** @var string|Configurable $mapperService */
        $mapperService = Mapper::class . '.' . $this->getImporterKey();

        ImportTask::output('-------------------');
        ImportTask::output('Now running import for ' . $this->getImporterKey());

        if (
            !Config::forClass($fetcherService)->get('apiKey') &&
            !Config::forClass(Fetcher::class)->get('apiKey')
        ) {
            ImportTask::output('No api key found');
            return;
        }

        if (!Config::forClass($fetcherService)->get('channel')) {
            ImportTask::output('No channel found');
            return;
        }

        if (!Config::forClass($mapperService)->get('mapping')) {
            ImportTask::output('No mappings found');
            return;
        }

        $fetcher = $this->getFetcher();

        $fetcher->startExportRun();
        ImportTask::output('Started Salsify export.');
        $fetcher->waitForExportRunToComplete();
        ImportTask::output('Salsify export complete');

        $this->file = $fetcher->getExportUrl();

        ImportTask::output('Staring data import');
        ImportTask::output('-------------------');
        $this->getMapper()->map();
    }
}
