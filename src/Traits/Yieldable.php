<?php

namespace Dynamic\Salsify\Traits;

/**
 * Trait Yieldable
 * @package Dynamic\Salsify\Traits
 */
trait Yieldable
{

    /**
     * @var string
     */
    public static $STOP_GENERATOR = 'stop';

    /**
     * @param array|iterable $list
     * @param callable|null $callback
     * @return \Generator
     */
    public function yieldSingle($list, $callback = null)
    {
        if (!is_array($list) && !$list instanceof Traversable) {
            $list = [$list];
        }

        foreach ($list as $item) {
            $injected = (yield $item);

            if ($injected === static::$STOP_GENERATOR) {
                break;
            }
        }

        if (is_callable($callback)) {
            $this->callback();
        }
    }

    /**
     * @param array|iterable $list
     * @param callable|null $callback
     * @return \Generator
     */
    public function yieldKeyVal($list, $callback = null)
    {
        if (!is_array($list) && !$list instanceof Traversable) {
            $list = [$list];
        }

        foreach ($list as $key => $val) {
            $injected = (yield $key => $val);

            if ($injected === static::$STOP_GENERATOR) {
                break;
            }
        }

        if (is_callable($callback)) {
            $this->callback();
        }
    }
}
