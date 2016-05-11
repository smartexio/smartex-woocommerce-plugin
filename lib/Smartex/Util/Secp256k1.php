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
 * Elliptic curve parameters for secp256k1, see:
 * http://www.secg.org/collateral/sec2_final.pdf
 *
 * @package Smartex
 */
class Secp256k1 implements CurveParameterInterface
{
    const A = '00';
    const B = '07';
    const G = '0479BE667EF9DCBBAC55A06295CE870B07029BFCDB2DCE28D959F2815B16F81798483ADA7726A3C4655DA4FBFC0E1108A8FD17B448A68554199C47D08FFB10D4B8';
    const H = '01';
    const N = 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141';
    const P = 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFC2F';

    public function aHex()
    {
        return '0x' . strtolower(self::A);
    }

    public function bHex()
    {
        return '0x' . strtolower(self::B);
    }

    public function gHex()
    {
        return '0x' . strtolower(self::G);
    }

    public function gxHex()
    {
        return '0x' . substr(strtolower(self::G), 0, 64);
    }

    public function gyHex()
    {
        return '0x' . substr(strtolower(self::G), 66, 64);
    }

    public function hHex()
    {
        return '0x' . strtolower(self::H);
    }

    public function nHex()
    {
        return '0x' . strtolower(self::N);
    }

    public function pHex()
    {
        return '0x' . strtolower(self::P);
    }
}
