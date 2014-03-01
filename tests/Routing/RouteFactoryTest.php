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
        $this->mockApp = $this->getMockBuilder('Slim\App')
            ->disableOriginalConstructor()
            ->getMock();

        $this->factory = new Slim\Routing\RouteFactory($this->mockApp, function() {}, function() {});
    }

    /**
     * @dataProvider getReferences
     */
    public function testReferencesToController($callable, $expects)
    {
        $this->assertEquals($expects, $this->invokeFactoryMethod('referenceToController', array($callable)));
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

    public function testMakeRoute()
    {
        $factory = new Slim\Routing\RouteFactory($this->mockApp, function($p, $c) {
            return new \Slim\Route($p, $c);
        }, function() {});

        $route = $factory->make('/foo', function(){ return 'bar'; });

        $this->assertInstanceOf('\Slim\Route', $route);

        $route = $factory->make('/foo', 'foo:bar');

        $this->assertInstanceOf('\Slim\Route', $route);
        $this->assertInstanceOf('\Slim\Routing\ControllerDispatcher', $route->getCallable());
    }

    public function testMakeControllerCallback()
    {
        $factory = new Slim\Routing\RouteFactory($this->mockApp, function() {}, function($class) {
            return new $class;
        });

        $dispatcher = $this->invokeFactoryMethod('makeControllerCallback', array('FooController:bar'), $factory);

        $this->assertInstanceOf('\Slim\Routing\ControllerDispatcher', $dispatcher);

        $this->mockApp->expects($this->once())
            ->method('offsetExists')
            ->with('FooController')
            ->will($this->returnValue(false));

        $this->assertEquals('foo_bar', $dispatcher('_bar'));
    }

    public function testResolvingUndefinedServiceThrowsException()
    {
        $this->setExpectedException('\InvalidArgumentException');

        $this->mockApp->expects($this->once())
            ->method('offsetExists')
            ->with('foo.bar')
            ->will($this->returnValue(false));

        $this->invokeFactoryMethod('resolveControllerInstance', array('foo.bar'));
    }

    protected function invokeFactoryMethod($methodName, $parameters = array(), $factory = null)
    {
        $factory = $factory ?: $this->factory;
        $reflection = new \ReflectionClass(get_class($factory));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($factory, $parameters);
    }

}

class FooController {
    function bar($a) { return 'foo'.$a; }
}
