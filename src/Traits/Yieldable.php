<?php

namespace Dynamic\Salsify\Traits;

/**
 * Trait Yieldable
 * @package Dynamic\Salsify\Traits
 */
trait Yieldable
{
    /**
     * @param $list
     * @return \Generator
     */
    public function yieldSingle($list)
    {
        foreach ($list as $item) {
            yield $item;
        }
    }

    /**
     * @param $list
     * @return \Generator
     */
    public function yieldKeyVal($list)
    {
        foreach ($list as $key => $val) {
            yield $key => $val;
        }
    }
}
