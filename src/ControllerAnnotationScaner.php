<?php

namespace Fairy;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\FileCacheReader;
use Fairy\Annotation\Autowire;
use Fairy\Annotation\DynamicAutowire;
use Fairy\Annotation\IgnoreAutowire;
use Fairy\Annotation\RequestParam;
use Fairy\Annotation\Validator;
use think\exception\ValidateException;
use think\Validate;

class ControllerAnnotationScaner
{
    public $dynamicAutowireClass = [];
    public $ignoreAutowireClass = [];

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
        // 注解读取白名单
        $this->setWhiteList();
        // 注解读取器
        $annotationReader = config('system.annotation.cache') ?
            new FileCacheReader(new AnnotationReader(), env('runtime_path') . DIRECTORY_SEPARATOR . "annotation", true) :
            new AnnotationReader();
        // 读取请求的控制器下的方法的所有注解
        $this->readMethodAnnotation($annotationReader, $instance, $action);
        // 读取请求控制器下的所有属性的注解
        $this->readPropertiesAnnotation($annotationReader, $instance);
    }

    /**
     * 读取类的所有属性的注解
     * @param FileCacheReader|AnnotationReader $annotationReader
     * @param $instance
     * @throws \ReflectionException
     */
    protected function readPropertiesAnnotation($annotationReader, $instance)
    {
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperties = $reflectionClass->getProperties();
        foreach ($reflectionProperties as $reflectionProperty) {
            $propertyAnnotations = $annotationReader->getPropertyAnnotations($reflectionProperty);
            foreach ($propertyAnnotations as $propertyAnnotation) {
                if ($propertyAnnotation instanceof Autowire) {
                    if ($reflectionProperty->isPublic() && !$reflectionProperty->isStatic()) {
                        if ($this->dynamicAutowireClass) {
                            if (in_array($propertyAnnotation->class, $this->dynamicAutowireClass)) {
                                $reflectionProperty->setValue($instance, app($propertyAnnotation->class));
                            }
                        } else if ($this->ignoreAutowireClass) {
                            if (!in_array($propertyAnnotation->class, $this->ignoreAutowireClass)) {
                                $reflectionProperty->setValue($instance, app($propertyAnnotation->class));
                            }
                        } else {
                            $reflectionProperty->setValue($instance, app($propertyAnnotation->class));
                        }
                    }
                }
            }
        }
    }

    /**
     * 读取当前方法的注解
     * @param FileCacheReader|AnnotationReader $annotationReader
     * @param $instance
     * @param $action
     * @throws \ReflectionException
     */
    protected function readMethodAnnotation($annotationReader, $instance, $action)
    {
        $reflectionMethod = new \ReflectionMethod($instance, $action);
        $methodAnnotations = $annotationReader->getMethodAnnotations($reflectionMethod);
        foreach ($methodAnnotations as $methodAnnotation) {
            if ($methodAnnotation instanceof Validator) {// 验证器
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
                        if (method_exists($this, 'getValidateErrorMsg')) {
                            call_user_func([$this, 'getValidateErrorMsg'], $validate->getError());
                        } else {
                            exit($this->formatErrorMsg($validate->getError()));
                        }
                    }
                }
            } else if ($methodAnnotation instanceof RequestParam) {// 参数获取器
                $requestParams = app('request')->only($methodAnnotation->fields, $methodAnnotation->method ?: 'param');
                if ($formatRules = $methodAnnotation->json) {
                    $this->formatRequestParams($formatRules, $requestParams);
                }
                if ($methodAnnotation->mapping) {
                    $mapping = [];
                    foreach ($requestParams as $key => $value) {
                        if (isset($methodAnnotation->mapping[$key])) {
                            $mapping[$methodAnnotation->mapping[$key]] = $value;
                        } else {
                            $mapping[$key] = $value;
                        }
                    }
                    $requestParams = $mapping;
                }
                app('request')->requestParam = $requestParams;
            } else if ($methodAnnotation instanceof DynamicAutowire) {// 设置当前方法Autowire注解声明的属性允许注入的类
                $this->dynamicAutowireClass = $methodAnnotation->class;
            } else if ($methodAnnotation instanceof IgnoreAutowire) {// 设置当前方法Autowire注解声明的属性忽略注入的类
                $this->ignoreAutowireClass = $methodAnnotation->class;
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
        foreach ($this->whitelist as $v) {
            AnnotationReader::addGlobalIgnoredName($v);
        }
    }

    /**
     * 格式化错误信息
     * @param string $msg
     * @return false|string
     */
    protected function formatErrorMsg($msg = '')
    {
        return json_encode([
            'code' => 422, 'data' => '', 'msg' => $msg, 'time' => request()->time()
        ]);
    }

    /**
     * 格式化请求参数
     * @param array $rules
     * @param $requestParams
     * @throws \Exception
     */
    protected function formatRequestParams(array $rules, &$requestParams)
    {
        foreach ($rules as $field => $rule) {
            if (is_int($field)) {
                $field = (string)$rule;
            }
            if (isset($requestParams[$field])) {
                $data = json_decode($requestParams[$field], true);
                if (is_null($data)) {
                    throw new \Exception($field . '字段的值无法json反序列化');
                }
                // 过滤json数据的字段
                if (is_array($rule) && $rule) {
                    foreach ($data as $k => $v) {
                        if (is_string($k)) {//一维数组
                            if (!in_array($k, $rule)) {
                                unset($data[$k]);
                            }
                        } else if (is_int($k) && is_array($v)) {//二维数组
                            foreach ($v as $kk => $vv) {
                                if (!in_array($kk, $rule)) {
                                    unset($data[$k][$kk]);
                                }
                            }
                        }
                    }
                }
                $requestParams[$field] = $data;
            }
        }
    }
}
