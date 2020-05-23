<?php

namespace Tests;

use ReflectionClass;
use ReflectionException;

trait ReflectionHelper
{
    /**
     * Call any method of any object, including private and protected.
     *
     * @param        $object
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     * @throws ReflectionException
     * @see https://stackoverflow.com/a/8702347/10452175
     */
    public function callAnyMethod($object, string $method, array $args)
    {
        $class = new ReflectionClass($object);
        $methodObj = $class->getMethod($method);
        $methodObj->setAccessible(true);
        return $methodObj->invokeArgs($object, $args);
    }
}
