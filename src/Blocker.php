<?php


namespace Fairy;


class Blocker
{
    /**
     * 是否需要阻断
     * @var bool
     */
    public $block;

    /**
     * 处理好的数据
     * @var mixed
     */
    public $data;

    public function __construct($block = false, $data = null)
    {
        $this->block = $block;
        $this->data = $data;
    }
}
