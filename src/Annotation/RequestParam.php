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
     * 格式化json数据
     *
     * 使用示例：
     * json:{field1,field2,...fieldn}
     * 表示格式化field1,field2,...字段的json数据
     *
     * 支持json一维和二维字段的涮选
     * json:{field1:{childField1,childField2...}}
     * 表示格式化field1字段的json数据，并只获取field1字段下的childField1和childField2下标的值
     *
     * @var array
     */
    public $json;

    /**
     * 获取参数的方法 不填默认是param形式获取
     * @Enum({"get","post","put","delete"})
     * @var string
     */
    public $method;
}
