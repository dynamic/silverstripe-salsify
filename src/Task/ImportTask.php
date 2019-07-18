<?php

namespace Dynamic\Salsify\Task;

use Dynamic\Salsify\Model\Importer;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
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

        // gets all importers
        $injectorConfig = array_keys(Config::inst()->get(Injector::class));
        $importers = array_filter(
            $injectorConfig,
            function ($element) {
                return strncmp($element, Importer::class, strlen(Importer::class)) === 0;
            }
        );

        foreach ($importers as $importerClass) {
            $importer = Injector::inst()->create($importerClass);
            $importer->run();
        }
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
