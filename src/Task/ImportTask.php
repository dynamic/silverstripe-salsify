<?php

namespace Dynamic\Salsify\Task;

use Dynamic\Salsify\Model\Fetcher;
use Dynamic\Salsify\Model\Mapper;
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
    private static $lineEnding;

    /**
     * @var bool
     */
    private static $output = true;

    /**
     * @param \SilverStripe\Control\HTTPRequest $request
     */
    public function run($request)
    {
        static::$lineEnding = Director::is_cli() ? PHP_EOL : '<br />';

        $channelID = Config::inst()->get(Fetcher::class, 'channel');
        $fetcher = new Fetcher($channelID, true);

        $fetcher->startExportRun();
        static::echo('Started Salsify export.');

        $fetcher->waitForExportRunToComplete();
        static::echo('Salsify export complete');
        static::echo($fetcher->getExportUrl());

        static::echo('Staring data import');
        static::echo('-------------------');

        $mapper = new Mapper($fetcher->getExportUrl());
        $mapper->map();
    }

    /**
     * @param $string
     */
    public static function echo($string)
    {
        if (static::config()->get('output')) {
            echo $string . static::$lineEnding;
        }
    }
}
