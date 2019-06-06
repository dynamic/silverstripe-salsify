<?php

namespace Dyanmic\Salsify\ORM;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ReadonlyField;
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
}