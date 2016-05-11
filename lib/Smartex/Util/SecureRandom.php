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

namespace Smartex\Util;

/**
 * Generates cryptographically-secure random numbers using the OpenSSL extension.
 *
 * @package Smartex
 */
class SecureRandom
{
    /**
     * @var boolean
     */
    protected static $hasOpenSSL;

    /**
     * Generates 32 bytes of random data using the OpenSSL extension.
     *
     * @see http://php.net/manual/en/function.openssl-random-pseudo-bytes.php
     * @param integer $bytes
     * @return string $random
     * @throws \Exception
     */
    public static function generateRandom($bytes = 32)
    {
        if (!self::hasOpenSSL()) {
            throw new \Exception('You MUST have openssl module installed.');
        }

        $random = openssl_random_pseudo_bytes($bytes, $isStrong);

        if (!$random || !$isStrong) {
            throw new \Exception('Cound not generate a cryptographically strong random number.');
        }

        return $random;
    }

    /**
     * Returns true/false if the OpenSSL extension is installed & enabled.
     *
     * @return boolean
     */
    public static function hasOpenSSL()
    {
        if (null === self::$hasOpenSSL) {
            self::$hasOpenSSL = function_exists('openssl_random_pseudo_bytes');
        }

        return self::$hasOpenSSL;
    }
}
