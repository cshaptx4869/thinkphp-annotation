<?php


namespace Fairy\Annotation;


use Fairy\AnnotationInterceptor;
use Fairy\Proxy;
use think\Db;
use think\facade\Log;

/**
 * 事务管理
 * @Annotation
 * @Target("METHOD")
 */
class Transaction implements AnnotationInterceptor
{
    public function beforeAction($methodName, $arguments, Proxy $proxy)
    {
        Db::startTrans();
        Log::sql('[ DB ] START TRANSACTION');
    }

    public function afterAction($result, $methodName, $arguments, Proxy $proxy)
    {
        if ($result) {
            Db::commit();
            Log::sql('[ DB ] COMMIT TRANSACTION');
        } else {
            Db::rollback();
            Log::sql('[ DB ] ROLLBACK TRANSACTION');
        }
    }
}
