<?php

namespace Dynamic\Salsify\Tests\TestOnly;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

/**
 * Class MapperModification
 * @package Dynamic\Salsify\Tests\TestOnly
 */
class MapperModification extends Extension implements TestOnly
{
    /**
     * @param string $dbField
     * @param array $config
     * @param array $data
     * @return array
     */
    public function testModification($dbField, $config, $data)
    {
        $salsifyField = $config['salsifyField'];
        if (array_key_exists($salsifyField, $data)) {
            $data[$salsifyField] = $data[$salsifyField] . ' TEST_MOD';
        }
        return $data;
    }
}
