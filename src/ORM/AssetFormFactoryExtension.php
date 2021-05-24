<?php

namespace Dynamic\Salsify\ORM;

use SilverStripe\Assets\Image;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ReadonlyField;

/**
 * Class AssetFormFactoryExtension
 * @package Dynamic\Salsify\ORM
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
        if ($record && $record->SalsifyID) {
            $fields->insertAfter('LastEdited', ReadonlyField::create('SalsifyID', 'Salsify ID'));
            if ($record instanceof Image) {
                $fields->insertAfter('SalsifyID', ReadonlyField::create('Transformation', 'Salsify Transformation'));
            }
        }
    }
}
