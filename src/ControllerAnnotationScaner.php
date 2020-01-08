<?php

namespace Fairy;

class ControllerAnnotationScaner
{
    /**
     * @param array $call
     * @throws \ReflectionException
     */
    public function run(array $call)
    {
        /**@var $annotationScaner AnnotationScaner */
        $annotationScaner = app(AnnotationScaner::class);
        list($instance, $action) = $call;
        $annotationScaner->readMethodAnnotation($instance, $action);
        $annotationScaner->readPropertiesAnnotation($instance);
    }
}
