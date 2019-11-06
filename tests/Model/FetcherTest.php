<?php

namespace Dynamic\Salsify\Tests\Model\Mapper;

use Exception;
use Dynamic\Salsify\Model\Fetcher;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;

/**
 * Class FetcherTest
 * @package Dynamic\Salsify\Tests\Model\Mapper
 */
class FetcherTest extends SapphireTest
{

    /**
     * @var string
     */
    private $importerKey = 'test';

    /**
     *
     */
    public function setUp()
    {
        Config::modify()->remove(Fetcher::class, 'apiKey');
        return parent::setUp();
    }

    /**
     * @throws Exception
     */
    public function testConstructorFailsWithoutAPIKey()
    {
        $this->expectException(Exception::class);
        new Fetcher($this->importerKey);
    }

    /**
     * @throws Exception
     */
    public function testConstructorFailsWithoutChannel()
    {
        Config::modify()->set(Fetcher::class, 'apiKey', 'API_KEY');
        $this->expectException(Exception::class);
        new Fetcher($this->importerKey);
    }

    /**
     * @throws Exception
     */
    public function testConstructor()
    {
        Config::modify()->set(Fetcher::class . '.' . $this->importerKey, 'apiKey', 'API_KEY');
        Config::modify()->set(Fetcher::class . '.' . $this->importerKey, 'channel', 'CHANNEL');

        $fetcher = new Fetcher($this->importerKey);
        $this->assertInstanceOf(Fetcher::class, $fetcher);
    }
}
