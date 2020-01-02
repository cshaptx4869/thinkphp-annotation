thinkphp-annotation
=======
前言:
-------

thinkphp5.1

用注解的方式在控制器中实现：

- 数据验证
- 获取参数
- 属性对象注入



安装
------------

```bash
composer require cshaptx4869/thinkphp-annotation
```



## 配置

`tags.php `添加行为

```php
'action_begin' => [
     \Fairy\ControllerAnnotationScaner::class
]
```

添加 `system.php` 配置文件（可选）

```php
return [
    'annotation' => [
        'cache' => false,//是否开启注解读取缓存
        'writelist' => []//注解读取白名单
    ]
]
```



## 使用

`ArticleController` 控制器：

```php
<?php

namespace app\index\controller;

use think\Request;

class ArticleController
{
    /**
     * 属性对象注入
     * @Autowire(class="\app\common\model\ArticleModel")
     */
    public $articleModel;
    
    /**
     * 数据验证
     * @Validator(class="\app\index\validate\Article\Save")
     * 获取参数
     * @RequestParam(fields={"title","image_url","content","is_temporary"},method="post")
     */
    public function save(Request $request)
    {
        //获取过滤过后的参数
        $postData = $request->requestParam;

        return MyToolkit::success($this->articleModel->store($postData));
    }
}
```



## IDE 注解支持插件

一些ide已经提供了对注释的支持

- Eclipse via the [Symfony2 Plugin](http://symfony.dubture.com/)
- PHPStorm via the [PHP Annotations Plugin](http://plugins.jetbrains.com/plugin/7320) or the [Symfony2 Plugin](http://plugins.jetbrains.com/plugin/7219)

