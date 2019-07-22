<?php

namespace Dynamic\Salsify\Tests\Model\Mapper;

use Dynamic\Salsify\Model\Fetcher;
use Dynamic\Salsify\Model\Mapper;
use \InvalidArgumentException;
use Dynamic\Salsify\Model\Importer;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;

/**
 * Class ImporterTest
 * @package Dynamic\Salsify\Tests\Model\Mapper
 */
class ImporterTest extends SapphireTest
{

    /**
     *
     */
    public function testCanConstruct() {
        $importer = new Importer('test');
        $this->assertInstanceOf(Importer::class, $importer);
    }

    /**
     *
     */
    public function testGetImporterKey() {
        $importer = new Importer('test');
        $this->assertEquals('test', $importer->getImporterKey());
    }

    /**
     *
     */
    public function testImportKeyNotString() {
        $importer = new Importer('test');
        $this->expectException(InvalidArgumentException::class);
        /** @noinspection PhpParamsInspection */
        $importer->setImporterKey(array());
    }

    /**
     *
     */
    public function testImportKeyNotEmpty() {
        $importer = new Importer('test');
        $this->expectException(InvalidArgumentException::class);
        $importer->setImporterKey('');
    }

    /**
     *
     */
    public function testImportKeyContainsInvalidCharacters() {
        $importer = new Importer('test');
        $this->expectException(InvalidArgumentException::class);
        $importer->setImporterKey('test@');
    }

    /**
     *
     */
    public function testCreateServicesFetcher() {
        $this->assertFalse(Injector::inst()->has(Fetcher::class . '.test'));

        $importer = new Importer('test');
        $importer->createServices();
        $this->assertTrue(Injector::inst()->has(Fetcher::class . '.test'));
    }

    /**
     *
     */
    public function testCreateServicesMapper() {
        $this->assertFalse(Injector::inst()->has(Mapper::class . '.test'));

        $importer = new Importer('test');
        $importer->createServices();
        $this->assertTrue(Injector::inst()->has(Mapper::class . '.test'));
    }
}
