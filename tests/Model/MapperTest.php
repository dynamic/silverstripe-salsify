<?php

namespace Dynamic\Salsify\Tests\Model\Mapper;

use Dynamic\Salsify\Model\Mapper;
use Dynamic\Salsify\Task\ImportTask;
use Dynamic\Salsify\Tests\TestOnly\ImageExtension;
use Dynamic\Salsify\Tests\TestOnly\MappedObject;
use Exception;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;

/**
 * Class MapperTest
 * @package Dynamic\Salsify\Tests\Model\Mapper
 */
class MapperTest extends SapphireTest
{

    /**
     * @var string
     */
    protected static $fixture_file = '../fixtures.yml';

    /**
     * @var array
     */
    protected static $extra_dataobjects = [
        MappedObject::class,
    ];

    /**
     * @var string
     */
    private $importerKey = 'test';

    /**
     *
     */
    public function setUp()
    {
        Config::modify()->set(
            Mapper::class . '.' . $this->importerKey,
            'mapping',
            [
                MappedObject::class => [
                    'Unique' => [
                        'salsifyField' => 'custom-field-unique',
                        'unique' => true,
                    ],
                    'Title' => 'custom-field-title',
                    'Seller' => [
                        'salsifyField' => 'custom-field-seller',
                        'unique' => false,
                    ],
                    'MainImage' => [
                        'salsifyField' => 'custom-field-front-image',
                        'type' => 'Image',
                    ],
                    'Images' => [
                        'salsifyField' => 'custom-field-images',
                        'type' => 'ManyImages',
                    ],
                    'Unknown' => [
                        'unique' => true,
                    ],
                    'Unknown2' => [
                        'salsifyField' => 'XXXXX',
                    ],
                ],
            ]
        );
        Config::modify()->set(ImportTask::class, 'output', false);
        return parent::setUp();
    }

    /**
     * @throws Exception
     */
    public function testConstructorFailsWithoutMapping()
    {
        Config::modify()->remove(Mapper::class . '.' . $this->importerKey, 'mapping');
        $this->expectException(Exception::class);
        new Mapper($this->importerKey , __DIR__ . '/../data.json');
    }

    /**
     * @throws Exception
     */
    public function testConstructor()
    {
        $mapper = new Mapper($this->importerKey, __DIR__ . '/../data.json');
        $this->assertInstanceOf(Mapper::class, $mapper);
    }

    /**
     * @throws \Exception
     */
    public function testMap()
    {
        $this->assertEquals(1, MappedObject::get()->count());

        $mapper = new Mapper($this->importerKey, __DIR__ . '/../data.json');
        $mapper->map();

        // check to see if added
        $this->assertEquals(7, MappedObject::get()->count());
        // check to see if existing object was modified
        $this->assertEquals('William McCloundy', MappedObject::get()->find('Unique', '3')->Seller);

        // tests for unchanged
        $mapper = new Mapper($this->importerKey, __DIR__ . '/../data.json');
        $mapper->map();
        $this->assertEquals(7, MappedObject::get()->count());
        $this->assertEquals(9, Image::get()->count());
        $this->assertEquals(2, MappedObject::get()->find('Unique', '3')->Images()->count());
        $this->assertTrue(MappedObject::get()->first()->MainImageID > 0);
    }
}
