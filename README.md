# thinkphp-annotation



前言:
-------

thinkphp5.1中用注解的方式实现：

- 请求数据验证
- 请求数据过滤、格式化
- 属性对象自动注入
- 自动事务

> 最新文档地址：https://www.cnblogs.com/cshaptx4869/p/12178960.html 



安装
------------

```bash
composer require cshaptx4869/thinkphp-annotation
```



## 配置

`tags.php `添加行为，用于控制器注解扫描

```php
'action_begin' => [
     \Fairy\ControllerAnnotationScaner::class
]
```

模型中使用属性注解的话，需要在模型中引入 \Fairy\ModelAnnotationScaner 的trait

```php
use \Fairy\ModelAnnotationScaner;
```

添加 `system.php` 配置文件（可选）

```php
return [
    'annotation' => [
        'cache' => false,// 是否开启注解读取缓存，默认false
        'writelist' => []// 注解读取白名单，默认[]
        'interceptor' => [// 注解拦截器相关配置
            'enable' => true,// 默认开启注解拦截器
            'whitelist' => []// 注解拦截器白名单，默认[]
        ],
        'validate' => [
            'callback' => function($msg) {
            	// 自定义验证错误信息后续处理
            }
        ]
    ]
]
```

> PS：默认验证器注解验证不通过会终止程序运行并返回`json`格式的验证错误信息。可通过配置 callback函数自定义后续处理。请注意，不同版本使用上会有些许差别。
>



## 支持的注解

###### v0.1.0版：

| 注解名           | 申明范围 | 作用                         |
| ---------------- | -------- | ---------------------------- |
| @Autowire        | 属性     | 自动注入类对象               |
| @DynamicAutowire | 方法     | 声明当前方法允许属性注入的类 |
| @IgnoreAutowire  | 方法     | 声明当前方法忽略属性注入的类 |
| @RequestParam    | 方法     | 过滤、格式化请求参数         |
| @Validator       | 方法     | 验证器验证                   |

###### v0.1.1版：

| 注解名        | 申明范围 | 作用                 |
| ------------- | -------- | -------------------- |
| @RequestParam | 方法     | 过滤、格式化请求参数 |
| @Validator    | 方法     | 验证器验证           |
| @Autowire     | 属性     | 自动注入类对象       |
| @Transaction  | 方法     | 自动事务             |

> #### 版本差异：
>
> v0.1.1新增：
>
> **Transaction 注解**
>
> Transaction 注解根据当前方法返回值自动判断事务后续处理，如返回值等价于true就会自动commit，否则rollback。 
>
> Transaction 注解需要搭配 Autowire注解使用，且不支持在控制器中使用，推荐在模型中使用。
>
> **ModelAnnotationScaner**  
>
> 支持模型中使用属性注解
>
> 
>
> Autowire 注解改动：
>
> v0.1.0 版本中 Autowire 注解必须写class属性，如 Autowire(class=ArticleModel::class)，而在v0.1.1版本中 Autowire 注解则没有class属性而是通过@var ArticleModel 注解来自动识别。



## v0.1.0版本使用示例

`ArticleController` 控制器：

```php
<?php

namespace app\index\controller;

use app\index\validate\Article\SaveValidate;
use app\common\model\ArticleModel;
// 引入对应的注解
use Fairy\Annotation\Autowire;
use Fairy\Annotation\RequestParam;
use Fairy\Annotation\Validator;
use think\Request;

class ArticleController
{
    /**
     * 属性对象注入
     * @Autowire(class=ArticleModel::class)
     */
    public $articleModel;
    
    /**
     * 数据验证
     * clsss: thinkphp定义的验证器类名(必填) string类型
     * scene: 验证场景名 string类型
     * batch：是否批量验证 bool类型
     * throw: 验证失败是否抛出异常 bool类型
     * @Validator(
     *     class=SaveValidate::class,
     *     scene="save",
     *     batch=false,
     *     throw=false
     * )
     *
     * 获取参数
     * fields: 定义要获取的字段名，可批量设置默认值 array类型
     * mapping: 转换前台传递的字段名为自定义的字段名 array类型
     * method: 获取参数的方法,支持get、post、put、delte string类型
     * json: 格式化json字段的数据 array类型
     * 
     * json使用示例：
     * json:{field1,field2,...fieldn}
     * 表示格式化field1,field2,...,字段的json数据
     *
     * 支持json一维和二维字段的涮选，如
     * json:{field1:{childField1,childField2...}}
     * 表示格式化field1字段的json数据，并只获取field1字段下的childField1和childField2下标的值(支持深度一维和二维,会自动识别)
     *
     * @RequestParam(
     *     fields={"title","image_url","content","category_id","is_temporary","extra":"默认值"},
     *     json={"category_id"},
     *     mapping={"image_url":"img_url"},
     *     method="post"
     * )
     */
    public function save(Request $request)
    {
        //获取过滤过后的参数
        $postData = $request->requestParam;

        return MyToolkit::success($this->articleModel->store($postData));
    }
```



## v0.1.1版本使用示例

`ArticleController` 控制器：

```php
<?php

namespace app\index\controller;

use app\index\validate\Article\SaveValidate;
use app\common\model\ArticleModel;
// 引入对应的注解
use Fairy\Annotation\Autowire;
use Fairy\Annotation\RequestParam;
use Fairy\Annotation\Validator;
use think\Request;

class ArticleController
{
    /**
     * 属性对象注入
     * @Autowire()
     * @var ArticleModel
     */
    public $articleModel;
    
    /**
     * 数据验证
     * clsss: thinkphp定义的验证器类名(必填) string类型
     * scene: 验证场景名 string类型
     * batch：是否批量验证 bool类型
     * throw: 验证失败是否抛出异常 bool类型
     * @Validator(
     *     class=SaveValidate::class,
     *     scene="save",
     *     batch=false,
     *     throw=false
     * )
     *
     * 获取参数
     * fields: 定义要获取的字段名，可批量设置默认值 array类型
     * mapping: 转换前台传递的字段名为自定义的字段名 array类型
     * method: 获取参数的方法,支持get、post、put、delte string类型
     * json: 格式化json字段的数据 array类型
     * 
     * json使用示例：
     * json:{field1,field2,...fieldn}
     * 表示格式化field1,field2,...,字段的json数据
     *
     * 支持json一维和二维字段的涮选，如
     * json:{field1:{childField1,childField2...}}
     * 表示格式化field1字段的json数据，并只获取field1字段下的childField1和childField2下标的值(支持深度一维和二维,会自动识别)
     *
     * @RequestParam(
     *     fields={"title","image_url","content","category_id","is_temporary","extra":"默认值"},
     *     json={"category_id"},
     *     mapping={"image_url":"img_url"},
     *     method="post"
     * )
     */
    public function save(Request $request)
    {
        //获取过滤过后的参数
        $postData = $request->requestParam;

        return MyToolkit::success($this->articleModel->store($postData));
    }
}
```

`ArticleModel` 模型：

```php
<?php

namespace app\common\model;

use Fairy\Annotation\Autowire;
use Fairy\Annotation\Transaction;
use Fairy\ModelAnnotationScaner;
use think\Db;
use think\Model;

class ArticleModel extends Model
{
    // 引入支持模型的属性使用注解的trait
    use ModelAnnotationScaner;

    /**
     * 注入对象
     * @Autowire()
     * @var ArticleCategoryRelationModel
     */
    public $articleCategoryRelationModel;
    
    /**
     * 注解控制事务
     * 返回值等价于true 事务自动 commit 否则 rollback
     * @Transaction()
     */
    public function store(array $params)
    {
        $categoryIds = $params['category_id'];
        unset($params['category_id']);
        $articleId = Db::name('article')->insertGetId($params);
        $realtion = array_map(function ($categoryId) use ($articleId) {
            return [
                'article_id' => $articleId,
                'category_id' => $categoryId
            ];
        }, $categoryIds);

        return $this->articleCategoryRelationModel->store($realtion);
    }
}
```



## IDE 注解插件支持

一些ide已经提供了对注释的支持，推荐安装，以便提供注解语法提示

- Eclipse via the [Symfony2 Plugin](http://symfony.dubture.com/)
- PHPStorm via the [PHP Annotations Plugin](http://plugins.jetbrains.com/plugin/7320) or the [Symfony2 Plugin](http://plugins.jetbrains.com/plugin/7219)

