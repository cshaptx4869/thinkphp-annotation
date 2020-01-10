<?php


namespace Fairy;


class Proxy
{
    public $bean;

    public function __construct($bean)
    {
        $this->bean = $bean;
    }

    public function __call($methodName, $arguments)
    {
        $annotations = $this->getAnnotationInterceptors($methodName);

        return $this->callMethod($methodName, $arguments, $annotations);
    }

    /**
     * 获取当前方法的所有拦截器注解对象
     * @param $methodName
     * @return array
     * @throws \ReflectionException
     */
    private function getAnnotationInterceptors($methodName)
    {
        if (config('?system.annotation.interceptor.enable') && !config('system.annotation.interceptor.enable')) {
            return [];
        }
        $annotationObjs = [];
        /**@var $annotationScaner AnnotationScaner */
        $annotationScaner = app(AnnotationScaner::class);
        $annotationObjs[] = $annotationScaner->readMethodAnnotation($this->bean, $methodName);
        $whitelist = config('?annotation.interceptor.whitelist') ? config('annotation.interceptor.whitelist') : [];
        foreach ($annotationObjs as $k => $annotationObj) {
            if (!$annotationObj instanceof AnnotationInterceptor) {
                unset($annotationObjs[$k]);
            } elseif (in_array(get_class($annotationObj), $whitelist)) {
                unset($annotationObjs[$k]);
            }
        }

        return $annotationObjs;
    }

    /**
     * 执行拦截器并返回方法执行的结果
     * @param $methodName
     * @param $arguments
     * @param array $annotations
     * @return bool|mixed
     */
    private function callMethod($methodName, $arguments, $annotations = [])
    {
        $isBlocked = false;
        foreach ($annotations as $annotation) {
            $beforeActionData = call_user_func_array([$annotation, 'beforeAction'], [$methodName, $arguments, $this]);
            if ($beforeActionData === false) {
                $isBlocked = true;
                break;
            }
        }

        if ($isBlocked) {
            $result = $beforeActionData;
        } else {
            try {
                $result = call_user_func_array([$this->bean, $methodName], $arguments);
            } catch (\Exception $e) {
                $result = false;
            }
            foreach (array_reverse($annotations) as $annotation) {
                $afterActionData = call_user_func_array([$annotation, 'afterAction'], [$result, $methodName, $arguments, $this]);
                if ($afterActionData) {
                    $result = $afterActionData;
                }
            }
        }

        return $result;
    }
}
