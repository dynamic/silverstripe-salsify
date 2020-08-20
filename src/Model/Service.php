<?php

namespace Dynamic\Salsify\Model;

use Dynamic\Salsify\Traits\Yieldable;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;

/**
 * Class Service
 * @package Dynamic\Salsify\Model
 *
 * @mixin Configurable
 * @mixin Extensible
 * @mixin Injectable
 * @mixin Yieldable
 */
abstract class Service
{
    use Configurable;
    use Extensible {
        defineMethods as extensibleDefineMethods;
    }
    use Injectable;
    use Yieldable;

    /**
     * @var string
     */
    protected $importerKey;

    /**
     * @var string
     */
    protected $serviceName;

    /**
     * Service constructor.
     * @param stirng $importerKey
     */
    public function __construct($importerKey)
    {
        $this->importerKey = $importerKey;
        $this->serviceName = static::class . '.' . $this->importerKey;

        $serviceConfig = Config::inst()->get($this->serviceName);
        foreach ($this->yieldKeyVal($serviceConfig) as $key => $value) {
            $this->config()->merge($key, $value);

            if ($key === 'extensions') {
                if (!is_array($value)) {
                    throw new \Exception("Extensions for {$this->serviceName} is not an array");
                }
                foreach ($this->yieldSingle($value) as $extension) {
                    static::add_extension($extension);
                }
            }
        }
    }
}
