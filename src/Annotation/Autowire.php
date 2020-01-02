<?php

namespace Fairy\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * 属性自动注入对象
 * @Annotation
 * @Target("PROPERTY")
 */
class Autowire
{
    /**
     * 要注入的类名
     * @Required()
     * @var string
     */
    public $class;
}
