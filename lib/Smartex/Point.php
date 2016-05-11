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
 * Object to represent a point on an elliptic curve
 *
 */
class Point implements PointInterface
{
    /**
     * MUST be a HEX value
     *
     * @var string
     */
    protected $x;

    /**
     * MUST be a HEX value
     *
     * @var string
     */
    protected $y;

    /**
     * @param string $x
     * @param string $y
     */
    public function __construct($x, $y)
    {
        $this->x = (string) $x;
        $this->y = (string) $y;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->isInfinity()) {
            return self::INFINITY;
        }

        return sprintf('(%s, %s)', $this->x, $this->y);
    }

    /**
     * @return string
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * @return string
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * @return boolean
     */
    public function isInfinity()
    {
        return (self::INFINITY == $this->x || self::INFINITY == $this->y);
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize(array($this->x, $this->y));
    }

    /**
     * @inheritdoc
     */
    public function unserialize($data)
    {
        list(
            $this->x,
            $this->y
        ) = unserialize($data);
    }
}
