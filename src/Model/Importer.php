<?php

namespace Dynamic\Salsify\Model;

use \InvalidArgumentException;
use Dynamic\Salsify\Task\ImportTask;
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

    /**
     * @var string
     */
    protected $importerKey;

    /**
     * Importer constructor.
     * @param string $importerKey
     */
    public function __construct($importerKey = null)
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
     * @param string $class
     * @return mixed
     */
    public function getConfig($class)
    {
        return $class::config()->get($this->getImporterKey());
    }

    public function run()
    {
        ImportTask::echo('-------------------');
        ImportTask::echo('Now running import for ' . $this->getImporterKey());
        $mapperConfig = $this->getConfig(Mapper::class);
        if (!$mapperConfig || !$mapperConfig['mapping']) {
            ImportTask::echo('No mappings found');
            return;
        }

        $fetcher = new Fetcher($this->getConfig(Fetcher::class));
        $fetcher->startExportRun();
        ImportTask::echo('Started Salsify export.');
        $fetcher->waitForExportRunToComplete();
        ImportTask::echo('Salsify export complete');

        ImportTask::echo('Staring data import');
        ImportTask::echo('-------------------');
        $mapper = new Mapper($fetcher->getExportUrl(), $mapperConfig['mapping']);
        $mapper->map();
    }
}
