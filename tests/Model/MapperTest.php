<?php

namespace Dynamic\Salsify\Tests\Model\Mapper;

use Dynamic\Salsify\Model\Mapper;
use Dynamic\Salsify\Task\ImportTask;
use Dynamic\Salsify\Tests\TestOnly\MappedObject;
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
     *
     */
    public function setUp()
    {
        Config::modify()->set(Mapper::class, 'mapping', [
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
                'Image' => [
                    'salsifyField' => 'custom-field-image',
                    'type' => 'IMAGE',
                ],
                'Unknown' => [
                    'unique' => true,
                ],
                'Unknown2' => [
                    'salsifyField' => 'XXXXX',
                ],
            ]
        ]);
        Config::modify()->set(ImportTask::class, 'output', false);
        return parent::setUp();
    }

    /**
     *
     */
    public function testMap()
    {
        $this->assertEquals(1, MappedObject::get()->count());

        $mapper = new Mapper(__DIR__ . '/../data.json');
        $mapper->map();

        // check to see if added
        $this->assertEquals(7, MappedObject::get()->count());
        // check to see if existing object was modified
        $this->assertEquals('William McCloundy', MappedObject::get()->find('Unique', '3')->Seller);

        // tests for unchanged
        $mapper = new Mapper(__DIR__ . '/../data.json');
        $mapper->map();
        $this->assertEquals(7, MappedObject::get()->count());
    }
}
