<?php

namespace Fairy;

class ControllerAnnotationScaner extends AnnotationScaner
{
    public function run(array $call)
    {
        list($instance, $action) = $call;
        $this->readMethodAnnotation($instance, $action);
        $this->readPropertiesAnnotation($instance);
    }
}
