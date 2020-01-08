<?php

namespace Fairy;

trait ModelAnnotationScaner
{
    /**
     * @return static
     * @throws \ReflectionException
     */
    public static function __make()
    {
        /**@var $annotationScaner AnnotationScaner */
        $annotationScaner = app(AnnotationScaner::class);
        $modelObj = new static();
        $annotationScaner->readPropertiesAnnotation($modelObj);

        return $modelObj;
    }
}
