<?php

namespace Dynamic\Salsify\Tests\TestOnly;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Dev\TestOnly;

/**
 * Class TestController
 * @package Dynamic\Salsify\Tests\TestOnly
 */
class TestController extends LeftAndMain implements TestOnly
{
    /**
     * @return bool
     */
    public function canFetchSalsify()
    {
        return true;
    }
}
