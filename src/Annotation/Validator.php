<?php


namespace Fairy\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * 控制器方法前自动校验
 * @Annotation
 * @Target("METHOD")
 */
class Validator
{
    /**
     * 验证器类名
     * @Required()
     * @var string
     */
    public $class;

    /**
     * 验证场景
     * @var string
     */
    public $scene;

    /**
     * 是否批量验证
     * @var bool
     */
    public $batch;

    /**
     * 验证失败是否抛出异常
     * @var bool
     */
    public $throw;
}
