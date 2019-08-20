<?php

namespace Dynamic\Salsify\Tests\TestOnly;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

/**
 * Class MapperModification
 * @package Dynamic\Salsify\Tests\TestOnly
 */
class MapperSkipper extends Extension implements TestOnly
{
    /**
     * @param string $dbField
     * @param array $config
     * @param array $data
     * @return boolean
     */
    public function testSkip($dbField, $config, $data)
    {
        $salsifyField = $config['salsifyField'];
        if (array_key_exists($salsifyField, $data)) {
            return $data[$salsifyField] === '0';
        }
        return false;
    }
}