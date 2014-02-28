<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.3.5
 * @package     Slim
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
namespace Slim;

/**
 * Route Factory
 *
 * ...
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   3.0.0
 */
class RouteFactory
{
    /**
     * The application instance
     * @var \Slim\App
     */
    protected $app;

    /**
     * Route factory callable
     * @var \Closure
     */
    protected $resolver;

    /**
     * Constructor
     * @param  \Slim\App  $app
     * @param  \Closure   $factory
     */
    public function __construct(App $app, \Closure $resolver)
    {
        $this->app = $app;
        $this->resolver = $resolver;
    }

    /**
     * Create a new Route instance
     * @param string $pattern
     * @param mixed $callable
     * @return \Slim\Interfaces\RouteInterface
     */
    public function make($pattern, $callable)
    {
        if ($this->referenceToController($callable)) {
            $callable = $this->resolveControllerCallback($callable);
        }

        return call_user_func($this->resolver, $pattern, $callable);
    }

    /**
     * Determine if the callable is a reference to a controller that should be resolved.
     * @param string    $callable
     * @return bool
     */
    protected function referenceToController($callable)
    {
        if (is_callable($callable)) return false;

        return is_string($callable) && preg_match('!^([^\:]+)\:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!', $callable);
    }

    /**
     * Define a callback that uses a given reference to a service or class name
     *
     * @param  string $callable
     * @return \Closure
     */
    protected function resolveControllerCallback($callable)
    {
        list($service, $method) = explode(':', $callable);
        $factory = $this;

        return function() use ($factory, $service, $method) {

            $instance = $factory->resolveControllerInstance($service);

            $args = func_get_args();

            return call_user_func_array(array($instance, $method), $args);
        };
    }

    /**
     * Resolve the controller instance used by the route to handle the request
     *
     * @param  string $service
     * @throws \InvalidArgumentException
     * @return mixed
     */
    protected function resolveControllerInstance($service)
    {
        if (isset($this->app[$service])) return $this->app[$service];

        if (class_exists($service)) {
            try {
                return new $service;
            } catch (\Exception $e) {}
        }

        throw new \InvalidArgumentException(
            "The specified '$service' route controller is an undefined service or the controller could not be instantiated."
        );
    }

}
