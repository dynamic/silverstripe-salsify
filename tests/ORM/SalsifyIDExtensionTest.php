<?php

namespace Dynamic\Salsify\Tests\Model\Mapper;

use Dyanmic\Salsify\ORM\SalsifyIDExtension;
use Dynamic\Salsify\Tests\TestOnly\MappedObject;
use Dynamic\Salsify\Tests\TestOnly\TestController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\FormField;

/**
 * Class MapperTest
 * @package Dynamic\Salsify\Tests\Model\Mapper
 */
class SalsifyIDExtensionTest extends SapphireTest
{
    /**
     * @var array
     */
    protected static $extra_dataobjects = [
        MappedObject::class,
    ];

    /**
     * @var array
     */
    protected static $extra_controllers = [
        TestController::class,
    ];

    /**
     * @var array
     */
    protected static $required_extensions = [
        MappedObject::class => [
            SalsifyIDExtension::class,
        ],
    ];

    /**
     *
     */
    public function testUpdateCMSFieldsWithID()
    {
        /** @var MappedObject|SalsifyIDExtension $object */
        $object = MappedObject::create();
        $object->SalsifyID = '001';
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
        $this->assertInstanceOf(FormField::class, $fields->fieldByName('SalsifyID'));
        $this->assertTrue($fields->fieldByName('SalsifyID')->isReadonly());
    }

    /**
     *
     */
    public function testUpdateCMSFieldsWithoutID()
    {
        /** @var MappedObject|SalsifyIDExtension $object */
        $object = MappedObject::create();
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
        $this->assertInstanceOf(FormField::class, $fields->fieldByName('SalsifyID'));
        $this->assertFalse($fields->fieldByName('SalsifyID')->isReadonly());
    }

    /**
     *
     */
    public function testUpdateCMSActionsWithoutID()
    {
        /** @var MappedObject|SalsifyIDExtension $object */
        $object = MappedObject::create();
        $actions = $object->getCMSActions();
        $this->assertInstanceOf(FieldList::class, $actions);
        $this->assertNull($actions->fieldByName('action_salsifyFetch'));
    }

    /**
     *
     */
    public function testUpdateCMSActionsWithID()
    {
        $controller = TestController::create();
        $request = new HTTPRequest('GET', '/');
        $session = new Session([]);
        $request->setSession($session);
        $controller->setRequest($request);
        $controller->doInit();
        $controller->pushCurrent();


        /** @var MappedObject|SalsifyIDExtension $object */
        $object = MappedObject::create();
        $object->SalsifyID = '001';
        $actions = $object->getCMSActions();
        $this->assertInstanceOf(FieldList::class, $actions);
        $this->assertInstanceOf(FormAction::class, $actions->fieldByName('action_salsifyFetch'));
    }
}
