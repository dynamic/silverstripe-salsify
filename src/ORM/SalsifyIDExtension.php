<?php

namespace Dyanmic\Salsify\ORM;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;

/**
 * Class FileExtension
 *
 * @property string SalsifyID
 * @property string SalsifyUpdatedAt
 *
 * @property-read \SilverStripe\ORM\DataObject|\Dyanmic\Salsify\ORM\SalsifyIDExtension $owner
 */
class SalsifyIDExtension extends DataExtension
{

    /**
     * @var array
     */
    private static $db = [
        'SalsifyID' => 'Varchar(255)',
        'SalsifyUpdatedAt' => 'Varchar(255)'
    ];

    /**
     * @param \SilverStripe\Forms\FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $salsifyID = $fields->dataFieldByName('SalsifyID');
        if (!$salsifyID) {
            $fields->push($salsifyID = TextField::create('SalsifyID'));
        }

        $salsifyUpdatedAt = $fields->dataFieldByName('SalsifyUpdatedAt');
        if (!$salsifyUpdatedAt) {
            $fields->push($salsifyUpdatedAt = TextField::create('SalsifyUpdatedAt'));
        }

        if ($this->owner->SalsifyID) {
            $salsifyID->setReadonly(true);
        }
        $salsifyUpdatedAt->setReadonly(true);

        return parent::updateCMSFields($fields);
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
}
