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
 * Creates a bill for the calling merchant.
 *
 * @package Smartex
 */
interface BillInterface
{
    /**
     * @return array
     */
    public function getItems();

    /**
     * @return Currency
     */
    public function getCurrency();

    /**
     * @return string
     */
    public function getName();

    /**
     * $address = array($lineOne, $lineTwo);
     *
     * @return array
     */
    public function getAddress();

    /**
     * @return string
     */
    public function getCity();

    /**
     * @return string
     */
    public function getState();

    /**
     * @return string
     */
    public function getZip();

    /**
     * @return string
     */
    public function getCountry();

    /**
     * @return string
     */
    public function getEmail();

    /**
     * @return string
     */
    public function getPhone();

    /**
     * @return string
     */
    public function getStatus();

    /**
     * @return string
     */
    public function getShowRate();

    /**
     * @return boolean
     */
    public function getArchived();
}
