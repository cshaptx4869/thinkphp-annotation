<?php


namespace Fairy\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * 与IgnoreAutowire注解互斥
 * @Annotation
 * @Target("METHOD")
 */
class DynamicAutowire
{
    /**
     * 允许属性对象注入的类名
     * @var array
     */
    public $class;
}
