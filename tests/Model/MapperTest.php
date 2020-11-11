<?php

namespace Dynamic\Salsify\Tests\Model\Mapper;

use Dynamic\Salsify\ORM\SalsifyIDExtension;
use Dynamic\Salsify\Model\Mapper;
use Dynamic\Salsify\Task\ImportTask;
use Dynamic\Salsify\Tests\TestOnly\MappedObject;
use Dynamic\Salsify\Tests\TestOnly\MapperModification;
use Dynamic\Salsify\Tests\TestOnly\MapperSkipper;
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
    protected static $required_extensions = [
        Mapper::class => [
            MapperModification::class,
            MapperSkipper::class,
        ],
        MappedObject::class => [
            SalsifyIDExtension::class,
        ]
    ];

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
                    'SalsifyID' => [
                        'salsifyField' => 'salsify:id',
                    ],
                    'SalsifyUpdatedAt' => 'salsify:updated_at',
                    'Unique' => [
                        'salsifyField' => 'custom-field-unique',
                        'unique' => true,
                        'shouldSkip' => 'testSkip',
                    ],
                    'Title' => 'custom-field-title',
                    'Seller' => [
                        'salsifyField' => 'custom-field-seller',
                        'unique' => false,
                    ],
                    'Modified' => [
                        'salsifyField' => 'custom-field-modified',
                        'modification' => 'testModification'
                    ],
                    'MainImage' => [
                        'salsifyField' => 'custom-field-front-image',
                        'type' => 'Image',
                    ],
                    'Images' => [
                        'salsifyField' => 'custom-field-images',
                        'type' => 'ManyImages',
                        'sortColumn' => 'SortOrder',
                    ],
                    'FallbackString' => [
                        'salsifyField' => 'YYYYY',
                        'fallback' => 'custom-field-title',
                    ],
                    'FallbackArray' => [
                        'salsifyField' => 'YYYYY',
                        'fallback' => [
                            'XXXXX',
                            'custom-field-seller',
                        ],
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
        new Mapper($this->importerKey, __DIR__ . '/../data.json');
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
        $mapper = new Mapper($this->importerKey, __DIR__ . '/../data.json');
        $mapper->map();

        // check to see if existing added
        $this->assertEquals(7, MappedObject::get()->count());
        $this->subTestExisting();

        // tests for unchanged
        $mapper = new Mapper($this->importerKey, __DIR__ . '/../data.json');
        $mapper->map();

        $this->subTestImages();

        $this->assertEquals(7, MappedObject::get()->count());
        $this->assertEquals('modified TEST_MOD', MappedObject::get()->find('Unique', '2')->Modified);

        // test fallbacks
        $this->assertEquals('Brooklyn Bridge', MappedObject::get()->find('Unique', '3')->FallbackString);
        $this->assertEquals('William McCloundy', MappedObject::get()->find('Unique', '3')->FallbackArray);
    }

    /**
     * Checks existing records to see if they got updated
     */
    private function subTestExisting()
    {
        // check to see if existing object with unique was modified
        $this->assertEquals('William McCloundy', MappedObject::get()->find('Unique', '3')->Seller);
        $this->assertEquals('00000000000002', MappedObject::get()->find('Unique', '3')->SalsifyID);

        // check to see if existing object with salsify id was modified
        $this->assertEquals('Victor Lustig', MappedObject::get()->find('Unique', '2')->Seller);
        $this->assertEquals('00000000000001', MappedObject::get()->find('Unique', '2')->SalsifyID);
    }

    /**
     * Tests images and sorting
     */
    private function subTestImages()
    {
        // test images
        $this->assertEquals(9, Image::get()->count());
        $this->assertEquals(2, MappedObject::get()->find('Unique', '3')->Images()->count());
        // first in image array is actually first with sort column specified
        $this->assertEquals(
            'DA-002-002',
            MappedObject::get()->find('Unique', '3')->Images()->sort('SortOrder')->first()->SalsifyID
        );

        $this->assertTrue(MappedObject::get()->first()->MainImageID > 0);
        $this->assertTrue(MappedObject::get()->last()->MainImageID > 0);
    }
}
