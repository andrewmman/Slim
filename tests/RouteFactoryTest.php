<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     3.0.0
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
class RouteFactoryTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testResolvingInstanceFromContainer()
    {
        list($factory, $app) = $this->getFactory();

        $app->shouldReceive('offsetExists')->once()->with('foo')->andReturn(true);
        $app->shouldReceive('offsetGet')->once()->with('foo')->andReturn('instance');

        $this->assertEquals(
            'instance',
            $this->invokeFactoryMethod('resolveControllerInstance', array('foo'), $factory)
        );
    }

    /**
     * @dataProvider getReferences
     */
    public function testReferencesToController($callable, $expects)
    {
        list($factory, $app) = $this->getFactory();

        $this->assertEquals($expects, $this->invokeFactoryMethod('referenceToController', array($callable), $factory));
    }

    public function getReferences()
    {
        return array(
          array('foo.bar', false),
          array(function() {}, false),
          array('foo:bar', true),
          array('foo.bar:baz', true),
          array('FooController:bar', true),
          array('\Foo\BarController:baz', true),
        );
    }

    public function testMakeControllerCallback()
    {
        list($factory, $app) = $this->getFactory();

        $callable = $this->invokeFactoryMethod('makeControllerCallback', array('FooController:bar'), $factory);

        $this->assertTrue(is_callable($callable));

        $app->shouldReceive('offsetExists')->once()->with('FooController')->andReturn(false);

        $this->assertEquals('foo', call_user_func($callable));
    }

    public function testResolvingUndefinedServiceThrowsException()
    {
        $this->setExpectedException('\InvalidArgumentException');

        list($factory, $app) = $this->getFactory();
        $app->shouldReceive('offsetExists')->once()->with('foo.bar')->andReturn(false);

        $this->invokeFactoryMethod('resolveControllerInstance', array('foo.bar'), $factory);
    }

    public function testResolvingControllerReturnsInstance()
    {
        list($factory, $app) = $this->getFactory();
        $app->shouldReceive('offsetExists')->once()->with('FooController')->andReturn(false);

        $this->assertInstanceOf(
            'FooController',
            $this->invokeFactoryMethod('resolveControllerInstance', array('FooController'), $factory)
        );
    }

    public function testResolvingControllerWithDependenciesThrowsException()
    {
        $this->setExpectedException('\InvalidArgumentException');

        list($factory, $app) = $this->getFactory();
        $app->shouldReceive('offsetExists')->once()->with('FooBarController')->andReturn(false);

        $this->invokeFactoryMethod('resolveControllerInstance', array('FooBarController'), $factory);
    }

    protected function invokeFactoryMethod($methodName, $parameters = array(), $factory)
    {
        $reflection = new \ReflectionClass(get_class($factory));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($factory, $parameters);
    }

    protected function getFactory()
    {
        $app = Mockery::mock('\Slim\App');
        $factory = new \Slim\RouteFactory($app, function() {});

        return array($factory, $app);
    }

}

class FooController {
    function bar() { return 'foo'; }
}

class FooBarController {
    function __construct(Foo $foo) {}
}
