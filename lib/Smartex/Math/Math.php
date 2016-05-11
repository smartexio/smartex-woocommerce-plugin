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

namespace Smartex\Math;

class Math
{
    private static $engine;

    public static function setEngine($engine)
    {
        static::$engine = $engine;
    }
    public static function getEngine()
    {
        return static::$engine;
    }

    public static function __callStatic($name, $arguments)
    {
        if (is_null(static::$engine)) {
            if (extension_loaded('gmp')) {
                static::$engine = new GmpEngine();
            } elseif (extension_loaded('bcmath')) {
                static::$engine = new BcEngine();
            } else {
                throw new \Exception('The GMP or BCMATH extension for PHP is required.');
            }
        }

        return call_user_func_array(array(static::$engine, $name), $arguments);
    }
}
