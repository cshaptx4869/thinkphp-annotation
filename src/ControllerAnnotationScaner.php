<?php

namespace Fairy;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\FileCacheReader;
use Fairy\Annotation\Autowire;
use Fairy\Annotation\RequestParam;
use Fairy\Annotation\Validator;
use think\exception\ValidateException;
use think\Validate;

class ControllerAnnotationScaner
{
    /**
     * 注解读取白名单
     * @var array
     */
    protected $whitelist = [
        "author", "var", "after", "afterClass", "backupGlobals", "backupStaticAttributes", "before", "beforeClass", "codeCoverageIgnore*",
        "covers", "coversDefaultClass", "coversNothing", "dataProvider", "depends", "doesNotPerformAssertions",
        "expectedException", "expectedExceptionCode", "expectedExceptionMessage", "expectedExceptionMessageRegExp", "group",
        "large", "medium", "preserveGlobalState", "requires", "runTestsInSeparateProcesses", "runInSeparateProcess", "small",
        "test", "testdox", "testWith", "ticket", "uses"
    ];

    public function run(array $call)
    {
        list($instance, $action) = $call;
        AnnotationRegistry::registerLoader('class_exists');
//        AnnotationRegistry::registerFile(__DIR__ . '/Annotation/Route.php'); //注册文件
//        AnnotationRegistry::registerAutoloadNamespace('Faily\\Annotation'); //注册命名空间
//        AnnotationRegistry::registerAutoloadNamespaces(['Faily\\Annotation' => null]); //注册多个命名空间

        $this->setWhiteList();
        foreach ($this->whitelist as $v) {
            AnnotationReader::addGlobalIgnoredName($v);
        }

        // 注解读取器
        $annotationReader = config('system.annotation.cache') ?
            new FileCacheReader(new AnnotationReader(), env('runtime_path') . DIRECTORY_SEPARATOR . "annotation", true) :
            new AnnotationReader();

        // 读取请求控制器下的所有属性的注解
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperties = $reflectionClass->getProperties();
        foreach ($reflectionProperties as $reflectionProperty) {
            $propertyAnnotations = $annotationReader->getPropertyAnnotations($reflectionProperty);
            foreach ($propertyAnnotations as $propertyAnnotation) {
                if ($propertyAnnotation instanceof Autowire) {
                    if ($reflectionProperty->isPublic() && !$reflectionProperty->isStatic()) {
                        $reflectionProperty->setValue($instance, app($propertyAnnotation->class));
                    }
                }
            }
        }

        // 读取请求的控制器下的方法的所有注解
        $reflectionMethod = new \ReflectionMethod($instance, $action);
        $methodAnnotations = $annotationReader->getMethodAnnotations($reflectionMethod);
        foreach ($methodAnnotations as $methodAnnotation) {
            //验证
            if ($methodAnnotation instanceof Validator) {
                /**@var $validate \think\validate */
                $validate = app($methodAnnotation->class);
                if (!$validate instanceof Validate) {
                    throw new \Exception('class ' . $methodAnnotation->class . ' is not a thinkphp validate class');
                }
                if ($methodAnnotation->batch) {
                    $validate->batch();
                }
                if ($methodAnnotation->scene) {
                    $validate->scene($methodAnnotation->scene);
                }
                if (!$validate->check(app('request')->param())) {
                    if ($methodAnnotation->throw) {
                        throw new ValidateException($validate->getError());
                    } else {
                        exit($this->formatErrorMsg($validate->getError()));
                    }
                }
            }
            //过滤
            if ($methodAnnotation instanceof RequestParam) {
                $requestParams = app('request')->only($methodAnnotation->fields, $methodAnnotation->method ?: 'param');
                app('request')->requestParam = $requestParams;
            }
        }
    }

    /**
     * 设置注解读取白名单
     * @return array
     */
    protected function setWhiteList()
    {
        if ($whitelist = config('system.annotation.whitelist')) {
            $this->whitelist = array_merge($this->whitelist, $whitelist);
        }
    }

    /**
     * 格式化错误信息
     */
    protected function formatErrorMsg($msg = '')
    {
        return json_encode([
            'code' => 422, 'data' => '', 'msg' => $msg, 'time' => request()->time()
        ]);
    }
}
