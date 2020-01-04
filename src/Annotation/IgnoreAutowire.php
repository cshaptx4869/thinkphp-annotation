<?php


namespace Fairy\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * 与DynamicAutowire注解互斥
 * @Annotation
 * @Target("METHOD")
 */
class IgnoreAutowire
{
    /**
     * 忽略属性对象注入的类名
     * @var array
     */
    public $class;
}
