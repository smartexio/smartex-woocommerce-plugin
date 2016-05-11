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

date_default_timezone_set('utc');

/**
 * @package Smartex
 */
class Token implements TokenInterface
{
    /**
     * @var string
     */
    protected $token;

    /**
     * @var string
     */
    protected $resource;

    /**
     * @var string
     */
    protected $facade;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var array
     */
    protected $policies;

    /**
     * @var string
     */
    protected $pairingCode;

    /**
     * @var \DateTime
     */
    protected $pairingExpiration;

    public function __construct()
    {
        $this->policies = array();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getToken();
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string
     * @return Token
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return string
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param string
     * @return Token
     */
    public function setResource($resource)
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * @return string
     */
    public function getFacade()
    {
        return $this->facade;
    }

    /**
     * @param string
     * @return Token
     */
    public function setFacade($facade)
    {
        $this->facade = $facade;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime
     * @return Token
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return array
     */
    public function getPolicies()
    {
        return $this->policies;
    }

    /**
     * @param string
     * @return Token
     */
    public function setPolicies($policies)
    {
        $this->policies = $policies;

        return $this;
    }

    /**
     * @return string
     */
    public function getPairingCode()
    {
        return $this->pairingCode;
    }

    /**
     * @param string
     * @return Token
     */
    public function setPairingCode($pairingCode)
    {
        $this->pairingCode = $pairingCode;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getPairingExpiration()
    {
        return $this->pairingExpiration;
    }

    /**
     * @param \DateTime
     * @return Token
     */
    public function setPairingExpiration(\DateTime $pairingExpiration)
    {
        $this->pairingExpiration = $pairingExpiration;

        return $this;
    }
}
