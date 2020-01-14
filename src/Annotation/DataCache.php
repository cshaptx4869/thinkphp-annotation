<?php


namespace Fairy\Annotation;


use Doctrine\Common\Annotations\Annotation\Target;
use Fairy\AnnotationInterceptor;
use Fairy\Proxy;
use Fairy\Blocker;
use think\facade\Cache;

/**
 * 方法数据缓存
 * @Annotation
 * @Target("METHOD")
 */
class DataCache implements AnnotationInterceptor
{
    /**
     * 键名 支持变量模板的形式
     * 如 ${0} 表示方法的第一个参数
     * @var string
     */
    public $name;

    /**
     * 生存周期(s,m,h,d) 默认0
     * @var string
     */
    public $expire;

    public function beforeAction($methodName, $arguments, Proxy $proxy)
    {
        $result = Cache::get($this->getKeyName($proxy->getClass(), $methodName, $arguments));

        return new Blocker($result ? true : false, $result);
    }

    public function afterAction($result, $methodName, $arguments, Proxy $proxy)
    {
        Cache::set($this->getKeyName($proxy->getClass(), $methodName, $arguments), $result, $this->transExpire());
    }

    protected function getKeyName($className, $methodName, $arguments)
    {
        $key = $this->name ? preg_replace_callback('/\${(\d)}/', function ($matches) use ($arguments) {
            return isset($arguments[$matches[1]]) ? $arguments[$matches[1]] : $matches[0];
        }, $this->name) : $methodName;

        return $className . ':' . $methodName . ':' . $key;
    }

    protected function transExpire()
    {
        $pattern = '/^(\d+)([a-zA-Z]*)$/';
        if (!preg_match($pattern, $this->expire, $matches)) {
            return 0;
        }

        $result = $matches[1];
        if (!$matches[2]) {
            return $result;
        }

        switch ($matches[2]) {
            case 'm':
                $result *= 60;
                break;
            case 'h':
                $result *= 60 * 60;
                break;
            case 'd':
                $result *= 24 * 60 * 60;
                break;
        }

        return $result;
    }
}
