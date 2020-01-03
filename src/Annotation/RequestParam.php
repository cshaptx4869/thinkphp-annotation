<?php


namespace Fairy\Annotation;

use Doctrine\Common\Annotations\Annotation\Enum;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("METHOD")
 */
class RequestParam
{
    /**
     * 要获取的字段名
     * @var array
     */
    public $fields;

    /**
     * 字段映射关系
     * @var array
     */
    public $mapping;

    /**
     * 获取参数的方法 不填默认是param形式获取
     * @Enum({"get","post","put","delete"})
     * @var string
     */
    public $method;
}
