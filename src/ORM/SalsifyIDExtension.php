<?php

namespace Dynamic\Salsify\ORM;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\CMS\Forms\SiteTreeURLSegmentField;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;

/**
 * Class FileExtension
 *
 * @property string SalsifyID
 * @property string SalsifyUpdatedAt
 * @property string SalsifyRelationsUpdatedAt
 *
 * @property-read \SilverStripe\ORM\DataObject|\Dynamic\Salsify\ORM\SalsifyIDExtension $owner
 */
class SalsifyIDExtension extends DataExtension
{

    /**
     * @var array
     */
    private static $db = [
        'SalsifyID' => 'Varchar(255)',
        'SalsifyUpdatedAt' => 'Varchar(255)',
        'SalsifyRelationsUpdatedAt' => 'Varchar(255)',
    ];

    /**
     * @var array
     */
    private static $indexes = [
        'SalsifyID' => true,
    ];

    /**
     * @param \SilverStripe\Forms\FieldList $fields
     */
    protected function updateFields(FieldList $fields)
    {
        $salsifyID = $fields->fieldByName('SalsifyID');
        if (!$salsifyID) {
            $fields->push($salsifyID = TextField::create('SalsifyID'));
        }

        $salsifyUpdatedAt = $fields->fieldByName('SalsifyUpdatedAt');
        if (!$salsifyUpdatedAt) {
            $fields->push($salsifyUpdatedAt = DatetimeField::create('SalsifyUpdatedAt'));
        }

        $salsifyRelationsUpdatedAt = $fields->fieldByName('SalsifyRelationsUpdatedAt');
        if (!$salsifyRelationsUpdatedAt) {
            $fields->push($salsifyRelationsUpdatedAt = DatetimeField::create('SalsifyRelationsUpdatedAt'));
        }

        if ($this->owner->SalsifyID) {
            $salsifyID->setTemplate(SiteTreeURLSegmentField::class)->addExtraClass('urlsegment');
        }
        $salsifyUpdatedAt->setReadonly(true);
        $salsifyRelationsUpdatedAt->setReadonly(true);
    }

    /**
     * @param \SilverStripe\Forms\FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        if ($this->owner instanceof SiteTree) {
            return parent::updateCMSFields($fields);
        }

        $this->updateFields($fields);
        return parent::updateCMSFields($fields);
    }

    /**
     * @param \SilverStripe\Forms\FieldList $fields
     */
    public function updateSettingsFields(FieldList $fields)
    {
        $this->updateFields($fields);
    }

    /**
     * @param \SilverStripe\Forms\FieldList $actions
     */
    public function updateCMSActions(FieldList $actions)
    {
        parent::updateCMSActions($actions);

        if (!$this->owner->SalsifyID) {
            return;
        }

        $controller = Controller::curr();
        if ($controller instanceof LeftAndMain && $controller->canFetchSalsify()) {
            /** @var FormAction $action */
            $action = FormAction::create('salsifyFetch', 'Re-fetch Salsify')
                ->addExtraClass('btn-primary font-icon-sync')
                ->setUseButtonTag(true);

            $actions->push($action);
        }
    }

    /**
     *
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $this->owner->SalsifyID = trim($this->owner->SalsifyID);
    }
}
