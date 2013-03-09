<?php

namespace Mesa\ClassWrapper;

class Wrapper
{

    protected $object = null;
    protected $namespace = null;
    protected $parameter = array();
    protected $classRefl = null;


    public function __construct($class)
    {
        if (empty($class) || (!is_string($class) && !is_object($class))) {
            throw new \Exception('Type is not supported');
        }

        if (is_string($class)) {
            $this->namespace = $class;
        }

        if (is_object($class)) {
            $this->object = $class;
            $this->classRefl = new \ReflectionObject($class);
            $this->namespace = $this->classRefl->getNamespaceName();
        }
    }

    public function addParam($name, $value = "")
    {
        if (empty($name)) {
            throw new \Exception('Empty argument name');
        }
        $this->parameter[$name] = $value;
        return $this;
    }

    public function call($method, $args = array())
    {
        if ($this->object == null) {
            $this->object = $this->createClass();
        }

        foreach ($args as $key => $value) {
            $this->addParam($key, $value);
        }

        if (!$this->classRefl->hasMethod($method)) {
            throw new \Exception(
                'Class [' . $this->classRefl->getNamespaceName() . '] has no Method [' . $method .']'
            );
        }

        $methodRefl = new \ReflectionMethod($this->object, $method);
        $args = $this->prepareArgs($methodRefl);

        if (!$args) {
            return $methodRefl->invoke($this->object);
        }
        return $methodRefl->invokeArgs($this->object, $args);

    }

    protected function getArgs(\ReflectionMethod $methodRefl)
    {
        if ($methodRefl->getNumberOfParameters() == 0) {
            return array();
        }

        foreach ($methodRefl->getParameters() as $arg) {
                $parameter[] = $arg;
        }

        return $parameter;
    }

    protected function prepareArgs(\ReflectionMethod $methodRefl)
    {
        if ($methodRefl->getNumberOfParameters() == 0) {
            return false;
        }

        $parameter = array();
        foreach ($this->getArgs($methodRefl) as $arg) {
            try {
                $parameter[] = $this->getParam($arg->getName());
            } catch (\Exception $e) {
                if (!$arg->isOptional()) {
                    throw new \Exception(
                        'Parameter [' . $arg->getName() . '] for Class [' . $this->namespace . '] not found'
                    );
                }
            }
        }
        return $parameter;
    }

    protected function createClass()
    {
        $this->classRefl = new \ReflectionClass($this->namespace);

        if (!$this->classRefl->hasMethod("__construct")) {
            return $this->classRefl->newInstance();
        }
        
        $methodRefl = $this->classRefl->getMethod('__construct');
        $args = $this->prepareArgs($methodRefl);

        if (!$args) {
            return $this->classRefl->newInstance();
        }

        return $this->classRefl->newInstanceArgs($args);
    }

    public function getParam($name)
    {
        if (!isset($this->parameter[$name])) {
            throw new \Exception(
                'Parameter [' . $name . '] for Class [' . $this->namespace . '] not found'
            );
        }

        return $this->parameter[$name];
    }

    public function getMethodParams($method)
    {
        $methodRefl = new \ReflectionMethod($this->namespace, $method);
        $parameter = array();
        foreach ($this->getArgs($methodRefl) as $arg) {
            $parameter[] = array(
                'name' => $arg->name,
                'reflParam' => $arg
            );
        }

        return $parameter;
    }
}
