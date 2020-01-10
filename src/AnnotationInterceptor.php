<?php


namespace Fairy;


interface AnnotationInterceptor
{
    public function beforeAction($methodName, $arguments, Proxy $proxy);

    public function afterAction($result, $methodName, $arguments, Proxy $proxy);
}
