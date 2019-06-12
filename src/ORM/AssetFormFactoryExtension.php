<?php

namespace Dyanmic\Salsify\ORM;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ReadonlyField;

/**
 * Class AssetFormFactoryExtension
 * @package Dyanmic\Salsify\ORM
 *
 * @property-read \SilverStripe\AssetAdmin\Forms\FileFormFactory $owner
 */
class AssetFormFactoryExtension extends Extension
{
    /**
     * @param \SilverStripe\Forms\FieldList $fields
     */
    public function updateFormFields(FieldList $fields, $controller, $formName, $context)
    {
        $record = isset($context['Record']) ? $context['Record'] : null;
        if ($record && $record->SalisfyID) {
            $fields->insertAfter('LastEdited', ReadonlyField::create('SalisfyID', 'Salsify ID'));
        }
    }
}
