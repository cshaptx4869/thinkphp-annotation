<?php

namespace Fairy;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\FileCacheReader;
use Fairy\Annotation\Autowire;
use Fairy\Annotation\RequestParam;
use Fairy\Annotation\Validator;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\ParserFactory;
use think\exception\ValidateException;
use think\Validate;
use ReflectionClass;

class AnnotationScaner
{
    /**
     * @var $annotationReader FileCacheReader|AnnotationReader
     */
    protected $annotationReader;

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

    public function __construct()
    {
        $this->init();
    }

    protected function init()
    {
        AnnotationRegistry::registerLoader('class_exists');
//        AnnotationRegistry::registerFile(__DIR__ . '/Annotation/Route.php'); //注册文件
//        AnnotationRegistry::registerAutoloadNamespace('Faily\\Annotation'); //注册命名空间
//        AnnotationRegistry::registerAutoloadNamespaces(['Faily\\Annotation' => null]); //注册多个命名空间
        // 注解读取白名单
        $this->setWhiteList();
        // 注解读取器
        $this->annotationReader = config('system.annotation.cache') ?
            new FileCacheReader(new AnnotationReader(), env('runtime_path') . DIRECTORY_SEPARATOR . "annotation", true) :
            new AnnotationReader();
    }

    /**
     * 读取类的所有属性的注解
     * @param $instance
     * @throws \ReflectionException
     */
    public function readPropertiesAnnotation($instance)
    {
        $reflectionClass = new \ReflectionClass($instance);
        $reflectionProperties = $reflectionClass->getProperties();
        foreach ($reflectionProperties as $reflectionProperty) {
            $propertyAnnotations = $this->annotationReader->getPropertyAnnotations($reflectionProperty);
            foreach ($propertyAnnotations as $propertyAnnotation) {
                if ($propertyAnnotation instanceof Autowire) {
                    if ($reflectionProperty->isPublic() && !$reflectionProperty->isStatic()) {
                        if ($class = $this->getType($reflectionClass, $reflectionProperty->getDocComment())) {
                            $reflectionProperty->setValue($instance, app($class));
                        }
                    }
                }
            }
        }
    }

    /**
     * 读取当前方法的注解
     * @param $instance
     * @param $action
     * @throws \ReflectionException
     */
    public function readMethodAnnotation($instance, $action)
    {
        $reflectionMethod = new \ReflectionMethod($instance, $action);
        $methodAnnotations = $this->annotationReader->getMethodAnnotations($reflectionMethod);
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

    /**
     * 获取类文件的内容
     * @param ReflectionClass $reflection
     * @return string
     */
    protected function getClassSource(ReflectionClass $reflection)
    {
        $filepath = $reflection->getFileName();
        $startLine = $reflection->getStartLine() - 1;
        $endLine = $reflection->getEndLine();
        $length = $endLine - $startLine;
        $source = file($filepath);
        $body = implode("", array_slice($source, $startLine, $length));

        return $body;
    }

    protected function getType(ReflectionClass $reflectionClass, $docComment)
    {
        if (!preg_match('/@var\s+(\S+)\s+/', $docComment, $matches)) {
            return false;
        }
        $type = $matches[1];
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP5);
        $code = $this->getClassSource($reflectionClass);
        $ast = $parser->parse($code);
        $uses = $this->getUses($ast);
        if (!(strpos($type, '\\') === 0)) {
            if (array_key_exists($type, $uses)) {
                $type = $uses[$type];
            } else {
                $type = $reflectionClass->getNamespaceName() . '\\' . $type;
            }
        }

        return $type;
    }

    /**
     * 获取类中的use信息
     * @param $stmts
     * @return array
     */
    protected function getUses($stmts)
    {
        $uses = [];
        foreach ($stmts as $v) {
            if ($v instanceof Namespace_) {
                foreach ($v->stmts as $vv) {
                    if ($vv instanceof Use_) {
                        foreach ($vv->uses as $vvv) {
                            $name = implode('\\', $vvv->name->parts);
                            if ($vvv->alias) {
                                $alias = $vvv->alias->name;
                            } else {
                                $pos = strrpos($name, '\\');
                                $alias = substr($name, $pos ? $pos + 1 : 0);
                            }
                            $uses[$alias] = $name;
                        }
                    }
                }
            }
        }

        return $uses;
    }
}
