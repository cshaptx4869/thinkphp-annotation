<?php


namespace Fairy;


class Proxy
{
    private $bean;

    public function __construct($bean)
    {
        $this->bean = $bean;
    }

    public function __call($methodName, $arguments)
    {
        /**@var $annotationScaner AnnotationScaner */
        $annotationScaner = app(AnnotationScaner::class);
        $interceptorAnnotationObjs = $annotationScaner->readMethodAnnotation($this->bean, $methodName);

        return $this->callMethod($methodName, $arguments, $interceptorAnnotationObjs);
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
        $data = null;

        foreach ($annotations as $annotation) {
            $beforeActionData = call_user_func_array([$annotation, 'beforeAction'], [$methodName, $arguments, $this]);
            if ($beforeActionData !== null && $beforeActionData instanceof Blocker) {
                if (isset($beforeActionData->data)) {
                    $data = $beforeActionData->data;
                }
                if ($beforeActionData->block) {
                    $isBlocked = true;
                    break;
                }
            }
        }

        if ($isBlocked) {
            $result = $data;
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

    public function getBean()
    {
        return $this->bean;
    }

    public function getClass()
    {
        return get_class($this->bean);
    }
}
