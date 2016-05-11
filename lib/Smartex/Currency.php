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

use Smartex\Client;

/**
 * For the most part this should conform to ISO 4217
 *
 * @see http://en.wikipedia.org/wiki/ISO_4217
 * @package Smartex
 */
class Currency implements CurrencyInterface
{
    /**
     * @see https://smartex.io/currencies
     * @var array
     */
    protected static $availableCurrencies = array(
        'ETH', 'AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG',
        'AZN', 'BAM', 'BBD', 'BDT', 'BGN', 'BHD', 'BIF', 'BMD', 'BND', 'BOB',
        'BRL', 'BSD', 'BTN', 'BWP', 'BYR', 'BZD', 'CAD', 'CDF', 'CHF', 'CLF',
        'CLP', 'CNY', 'COP', 'CRC', 'CVE', 'CZK', 'DJF', 'DKK', 'DOP', 'DZD',
        'EEK', 'EGP', 'ETB', 'EUR', 'FJD', 'FKP', 'GBP', 'GEL', 'GHS', 'GIP',
        'GMD', 'GNF', 'GTQ', 'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF', 'IDR',
        'ILS', 'INR', 'IQD', 'ISK', 'JEP', 'JMD', 'JOD', 'JPY', 'KES', 'KGS',
        'KHR', 'KMF', 'KRW', 'KWD', 'KYD', 'KZT', 'LAK', 'LBP', 'LKR', 'LRD',
        'LSL', 'LTL', 'LVL', 'LYD', 'MAD', 'MDL', 'MGA', 'MKD', 'MMK', 'MNT',
        'MOP', 'MRO', 'MUR', 'MVR', 'MWK', 'MXN', 'MYR', 'MZN', 'NAD', 'NGN',
        'NIO', 'NOK', 'NPR', 'NZD', 'OMR', 'PAB', 'PEN', 'PGK', 'PHP', 'PKR',
        'PLN', 'PYG', 'QAR', 'RON', 'RSD', 'RUB', 'RWF', 'SAR', 'SBD', 'SCR',
        'SDG', 'SEK', 'SGD', 'SHP', 'SLL', 'SOS', 'SRD', 'STD', 'SVC', 'SYP',
        'SZL', 'THB', 'TJS', 'TMT', 'TND', 'TOP', 'TRY', 'TTD', 'TWD', 'TZS',
        'UAH', 'UGX', 'USD', 'UYU', 'UZS', 'VEF', 'VND', 'VUV', 'WST', 'XAF',
        'XAG', 'XAU', 'XCD', 'XOF', 'XPF', 'YER', 'ZAR', 'ZMW', 'ZWL',
    );

    /**
     * @var string
     */
    protected $code;

    /**
     * @var string
     */
    protected $symbol;

    /**
     * @var integer
     */
    protected $precision;

    /**
     * @var string
     */
    protected $exchangePercentageFee;

    /**
     * @var boolean
     */
    protected $payoutEnabled;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $pluralName;

    /**
     * @var array
     */
    protected $alts;

    /**
     * @var array
     */
    protected $payoutFields;

    /**
     * @param  string    $code The Currency Code to use, ie USD
     * @throws Exception       Throws an exception if the Currency Code is not supported
     */
    public function __construct($code = null)
    {
        if (null !== $code) {
            $this->setCode($code);
        }

        $this->payoutEnabled = false;
        $this->payoutFields  = array();
    }

    /**
     * @inheritdoc
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * This will change the $code to all uppercase
     *
     * @param  string            $code The Currency Code to use, ie USD
     * @throws Exception               Throws an exception if the Currency Code is not supported
     * @return CurrencyInterface
     */
    public function setCode($code)
    {
        if (null !== $code && !in_array(strtoupper($code), self::$availableCurrencies)) {
            throw new \Smartex\Client\ArgumentException(
                sprintf('The currency code "%s" is not supported.', $code)
            );
        }

        $this->code = strtoupper($code);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSymbol()
    {
        return $this->symbol;
    }

    /**
     * @param string $symbol
     *
     * @return CurrencyInterface
     */
    public function setSymbol($symbol)
    {
        if (!empty($symbol) && ctype_print($symbol)) {
            $this->symbol = trim($symbol);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPrecision()
    {
        return $this->precision;
    }

    /**
     * @param integer $precision
     *
     * @return CurrencyInterface
     */
    public function setPrecision($precision)
    {
        if (!empty($precision) && ctype_digit(strval($precision))) {
            $this->precision = (int) $precision;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getExchangePctFee()
    {
        return $this->exchangePercentageFee;
    }

    /**
     * @param string $fee
     *
     * @return CurrencyInterface
     */
    public function setExchangePctFee($fee)
    {
        if (!empty($fee) && ctype_print($fee)) {
            $this->exchangePercentageFee = trim($fee);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isPayoutEnabled()
    {
        return $this->payoutEnabled;
    }

    /**
     * @param boolean $enabled
     *
     * @return CurrencyInterface
     */
    public function setPayoutEnabled($enabled)
    {
        $this->payoutEnabled = (boolean) $enabled;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return CurrencyInterface
     */
    public function setName($name)
    {
        if (!empty($name) && ctype_print($name)) {
            $this->name = trim($name);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPluralName()
    {
        return $this->pluralName;
    }

    /**
     * @param string $pluralName
     *
     * @return CurrencyInterface
     */
    public function setPluralName($pluralName)
    {
        if (!empty($pluralName) && ctype_print($pluralName)) {
            $this->pluralName = trim($pluralName);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAlts()
    {
        return $this->alts;
    }

    /**
     * @param array $alts
     *
     * @return CurrencyInterface
     */
    public function setAlts($alts)
    {
        $this->alts = $alts;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPayoutFields()
    {
        return $this->payoutFields;
    }

    /**
     * @param array $payoutFields
     *
     * @return CurrencyInterface
     */
    public function setPayoutFields(array $payoutFields)
    {
        $this->payoutFields = $payoutFields;

        return $this;
    }
}
