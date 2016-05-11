<?php
/**
 * The MIT License (MIT)
 * 
 * Copyright (c) 2016 Smartex.io Ltd.
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Smartex;

/**
 * @package Smartex
 */
class Autoloader
{
    /**
     * Register the autoloader, by default this will put the Smartex autoloader
     * first on the stack, to append the autoloader, pass `false` as an argument.
     *
     * Some applications will throw exceptions if the class isn't found and
     * some are not compatible with PSR standards.
     *
     * @param boolean $prepend
     * @throws \Exception
     */
    public static function register($prepend = true)
    {
        if (false === spl_autoload_register(array(__CLASS__, 'autoload'), true, (bool)$prepend)) {
            throw new \Exception('[ERROR] In Autoloader::register(): Call to spl_autoload_register() failed.');
        }
    }

    /**
     * Unregister this autoloader.
     *
     * @param boolean $ignoreFailures
     * @throws \Exception
     */
    public static function unregister($ignoreFailures = true)
    {
        if (false === spl_autoload_unregister(array(__CLASS__, 'autoload'))) {
            if ($ignoreFailures === false) {
                throw new \Exception('[ERROR] In Autoloader::unregister(): Call to spl_autoload_unregister() failed.');
            }
        }
    }

    /**
     * Give a class name and it will require the file.
     *
     * @param  string $class
     * @return bool|null
     * @throws \Exception
     */
    public static function autoload($class)
    {
        if (0 === strpos($class, 'Smartex\\')) {
            $classname = substr($class, 7);

            $file = __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $classname) . '.php';

            if (is_file($file) && is_readable($file)) {
                require_once $file;

                return true;
            }

            throw new \Exception('[ERROR] In Autoloader::autoload(): Class "' . $class . '" Not Found');
        }
    }
}
