<?php

namespace Dynamic\Salsify\Tests\Model\Mapper;

use Dynamic\Salsify\Model\Fetcher;
use SilverStripe\Dev\SapphireTest;

/**
 * Class FetcherTest
 * @package Dynamic\Salsify\Tests\Model\Mapper
 */
class FetcherTest extends SapphireTest
{

    /**
     * @var array
     */
    private $fetcherConfig = [
        'channel' => 'CHANNEL_ID'
    ];

    /**
     *
     */
    public function testSetChannelID()
    {
        $fetcher = new Fetcher($this->fetcherConfig);
        $this->assertEquals($fetcher, $fetcher->setChannelID('XXXX'));
    }

    /**
     *
     */
    public function testSetUseLatest()
    {
        $fetcher = new Fetcher($this->fetcherConfig);
        $this->assertEquals($fetcher, $fetcher->setUseLatest(true));
    }
}
