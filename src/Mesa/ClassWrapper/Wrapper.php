<?php

namespace Mesa\ClassWrapper;

/**
 * Class Wrapper
 * @package Mesa\ClassWrapper
 */
class Wrapper
{

    protected $object = null;
    protected $namespace = null;
    protected $parameter = array();
    protected $class = null;


    /**
     * @param string|object $class
     *
     * @throws \Exception
     */
    public function __construct($class)
    {
        if (empty($class) || (!is_string($class) && !is_object($class))) {
            throw new \Exception('Type is not supported. Please use Namespace or Object');
        }

        if (is_string($class)) {
            $this->namespace = $class;
        }

        if (is_object($class)) {
            $this->object    = $class;
            $this->class     = new \ReflectionObject($class);
            $this->namespace = $this->class->getNamespaceName();
        }
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return $this
     * @throws \Exception
     */
    public function addParam($name, $value = "")
    {
        if (empty($name)) {
            throw new \Exception('Empty argument name');
        }
        $this->parameter[$name] = $value;

        return $this;
    }

    /**
     * @param string $methodName
     * @param array $param
     *
     * @return object
     * @throws \Exception
     */
    public function call($methodName, $param = array())
    {
        if ($this->object == null) {
            $this->object = $this->createClass();
        }

        foreach ($param as $key => $value) {
            $this->addParam($key, $value);
        }

        if (!$this->class->hasMethod($methodName)) {
            throw new \Exception(
                'Class [' . $this->class->getNamespaceName() . '] has no Method [' . $methodName . ']'
            );
        }

        $method = new \ReflectionMethod($this->object, $methodName);
        $param  = $this->prepareArgs($method);

        if (!$param) {
            return $method->invoke($this->object);
        }

        return $method->invokeArgs($this->object, $param);
    }

    /**
     * @param \ReflectionMethod $method
     *
     * @return array
     */
    protected function getArgs(\ReflectionMethod $method)
    {
        if ($method->getNumberOfParameters() == 0) {
            return array();
        }

        $parameter = array();
        foreach ($method->getParameters() as $arg) {
            $parameter[] = $arg;
        }

        return $parameter;
    }

    /**
     * @param \ReflectionMethod $method
     *
     * @return array
     * @throws \Exception
     */
    protected function prepareArgs(\ReflectionMethod $method)
    {
        if ($method->getNumberOfParameters() == 0) {
            return array();
        }

        $parameters = array();
        foreach ($this->getArgs($method) as $param) {
            try {
                $parameters[] = $this->getParam($param->getName());
            } catch (\Exception $e) {
                if (!$param->isOptional()) {
                    throw new \Exception(
                        'Parameter [' . $param->getName() . '] for Class [' . $this->namespace . '] not found'
                    );
                }
            }
        }

        return $parameters;
    }

    /**
     * @return object
     */
    protected function createClass()
    {
        $this->class = new \ReflectionClass($this->namespace);

        if (!$this->class->hasMethod("__construct")) {
            return $this->class->newInstance();
        }

        $method = $this->class->getMethod('__construct');
        $args   = $this->prepareArgs($method);

        if (!$args) {
            return $this->class->newInstance();
        }

        return $this->class->newInstanceArgs($args);
    }

    /**
     * @param string $name
     *
     * @return mixed
     * @throws \Exception
     */
    public function getParam($name)
    {
        if (!isset($this->parameter[$name])) {
            throw new \Exception(
                'Parameter [' . $name . '] for Class [' . $this->namespace . '] not found.'
            );
        }

        return $this->parameter[$name];
    }

    /**
     * @param string $name
     *
     * @return array
     */
    public function getMethodParams($name)
    {
        $method    = new \ReflectionMethod($this->namespace, $name);
        $parameters = array();
        foreach ($this->getArgs($method) as $argument) {
            $parameters[] = array(
                'name'      => $argument->name,
                'argument' => $argument
            );
        }

        return $parameters;
    }
}
