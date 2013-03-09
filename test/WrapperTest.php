<?php

namespace Mesa\ClassWrapper;

require_once __DIR__ . '/../src/Mesa/ClassWrapper/Wrapper.php';

class WrapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Exception
     **/
    public function testConstructEmpty()
    {
        $subject = new Wrapper("");
    }

    public function testConstructObject()
    {
        $subject = new Wrapper(
            new Testing(1, 2, 3)
        );
    }

    public function testAddParam()
    {
        $subject = new Wrapper(
            new Testing(1, 2, 3)
        );
        $this->assertTrue($subject->addParam("first", 1) instanceof \Mesa\ClassWrapper\Wrapper);
    }

    /**
     * @expectedException \Exception
     **/
    public function testAddParamEmptyName()
    {
        $subject = new Wrapper(
            new Testing(1, 2, 3)
        );
        $subject->addParam("", 1);
    }

    public function testCall()
    {
        $subject = new Wrapper("\Mesa\ClassWrapper\Testing");
        $subject->addParam("first", 1);
        $subject->addParam("second", 1);
        $this->assertSame(
            3,
            $subject->call(
                "callMeMaybe",
                array(
                    'param1' => 1,
                    'param2' => 1
                )
            )
        );
    }

    public function testCallWithObject()
    {
        $subject = new Wrapper(
            new Testing(1, 2, 3)
        );
        $this->assertSame(
            3,
            $subject->call(
                "callMeMaybe",
                array(
                    'param1' => 1,
                    'param2' => 1
                )
            )
        );
    }

    public function testCallWithNoParamInFunction()
    {
        $subject = new Wrapper(
            new Testing(1, 2, 3)
        );

        $this->assertTrue(
            $subject->call('noParameter')
        );
    }

    /**
     * @expectedException \Exception
     **/
    public function testCallWithMissingParameter()
    {
        $subject = new Wrapper(
            '\Mesa\ClassWrapper\Testing'
        );
        $subject->call('noParameter');
    }

    /**
     * @expectedException \Exception
     **/
    public function testCallNotExistingMethod()
    {
        $subject = new Wrapper('\Mesa\ClassWrapper\EmptyConstructor');
        $this->assertTrue($subject->call('callMe'));
    }

    public function testClassWithoutConstructor()
    {
        $subject = new Wrapper('\Mesa\ClassWrapper\NoConstructor');
        $this->assertTrue($subject->call('testMe'));
    }

    public function testGetAllWithTypeHint()
    {
        $subject = new Wrapper('\Mesa\ClassWrapper\Testing');
        $result = $subject->getMethodParams('withTypeHint');
        $this->assertSame(
            'class1',
            $result[0]["name"]
        );
    }

    public function testGetAllWithEmptyArgs()
    {
        $subject = new Wrapper('\Mesa\ClassWrapper\Testing');
        $result = $subject->getMethodParams('noParameter');
        $this->assertSame(
            array(),
            $result
        );
    }
}

class Testing
{
    protected $testValue;

    public function __construct($first, $second, $third = 0)
    {
        $this->testValue = $first;
    }

    public function callMeMaybe($param1, $param2, $param3 = 0)
    {
        return $param1 + $param2 + $param3 + $this->testValue;
    }

    public function withTypeHint(\Mesa\ClassWrapper\Testing $class1, Array $array, $object)
    {
        return true;
    }

    public function notExistingClass(Does\not\exist $class)
    {
        return true;
    }

    public function noParameter()
    {
        return true;
    }
}

class EmptyConstructor
{
    public function __construct()
    {

    }

    public function testMe()
    {
        return true;
    }
}

class NoConstructor
{
    public function testMe()
    {
        return true;
    }
}
