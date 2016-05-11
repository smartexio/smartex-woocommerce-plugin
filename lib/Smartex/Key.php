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
 * Abstract object that is used for Public, Private, and SIN keys
 *
 */
abstract class Key extends Point implements KeyInterface
{
    /**
     * @var string
     */
    protected $hex;

    /**
     * @var string
     */
    protected $dec;

    /**
     * @var string
     */
    protected $id;

    /**
     * @param string $id
     */
    public function __construct($id = null)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns a new instance of self.
     *
     * @param string $id
     * @return \Smartex\KeyInterface
     */
    public static function create($id = null)
    {
        $class = get_called_class();

        return new $class($id);
    }

    /**
     * @return string
     */
    public function getHex()
    {
        return $this->hex;
    }

    /**
     * @return string
     */
    public function getDec()
    {
        return $this->dec;
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize(
            array(
                $this->id,
                $this->x,
                $this->y,
                $this->hex,
                $this->dec,
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function unserialize($data)
    {
        list(
            $this->id,
            $this->x,
            $this->y,
            $this->hex,
            $this->dec
        ) = unserialize($data);
    }
    
    /**
     * @return boolean
     */
    public function isGenerated()
    {
        return (!empty($this->hex));
    }
}
