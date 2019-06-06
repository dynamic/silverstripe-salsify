<?php

namespace Dyanmic\Salsify\ORM;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

/**
 * Class FileExtension
 * @property string SalisfyID
 */
class FileExtension extends DataExtension
{

    /**
     * @var array
     */
    private static $db = [
        'SalisfyID' => 'Varchar(255)',
    ];

    /**
     * @param \SilverStripe\Forms\FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        parent::updateCMSFields($fields);
        $fields->removeByName('SalsifyID');
    }
}