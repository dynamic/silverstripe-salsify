<?php

namespace Dynamic\Salsify\Task;

use Dynamic\Salsify\Model\Fetcher;
use Dynamic\Salsify\Model\Mapper;
use JsonStreamingParser\Parser;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;

/**
 * Class ImportTask
 * @package Dynamic\Salsify\Task
 */
class ImportTask extends BuildTask
{
    /**
     * @var string
     */
    private static $segment = 'SalsifyImportTask';

    /**
     * @var string
     */
    protected $title = 'Import products from salsify';

    /**
     * @var string
     */
    protected $description = 'Imports products from salsify into silverstripe';

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var
     */
    private $lineEnding;

    /**
     * @param \SilverStripe\Control\HTTPRequest $request
     */
    public function run($request)
    {
        $this->lineEnding = Director::is_cli() ? PHP_EOL : '<br />';

        $channelID = Config::inst()->get(Fetcher::class, 'channel');
        $fetcher = new Fetcher($channelID, true);

        $fetcher->startExportRun();
        echo 'Started Salsify export.' . $this->lineEnding;

        $fetcher->waitForExportRunToComplete();
        echo 'Salsify export complete' . $this->lineEnding;

        echo $fetcher->getExportUrl() . $this->lineEnding;
        $mapper = new Mapper($fetcher->getExportUrl());
        $mapper->map();
        
        // TODO
    }
}
