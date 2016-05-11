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
 * Class Payout
 * @package Smartex
 */
class Payout implements PayoutInterface
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $account_id;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var float
     */
    protected $amount;

    /**
     * @var float
     */
    protected $eth;
    /**
     * @var CurrencyInterface
     */
    protected $currency;

    /**
     * @var string
     */
    protected $effectiveDate;

    /**
     * @var string
     */
    protected $requestDate;

    /**
     * @var array
     */
    protected $instructions = array();

    /**
     * @var string
     */
    protected $notificationEmail;

    /**
     * @var string
     */
    protected $notificationUrl;

    /**
     * @var string
     */
    protected $pricingMethod;

    /**
     * @var string
     */
    protected $rate;

    /**
     * @var string
     */
    protected $reference;

    /**
     * @var string
     */
    protected $responseToken;

    /**
     * @var TokenInterface
     */
    protected $token;

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the batch ID as assigned from smartex.
     *
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        if (!empty($id) && ctype_print($id)) {
            $this->id = trim($id);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAccountId()
    {
        return $this->account_id;
    }

    /**
     * Set Account Id - Smartex account ID for the payout.
     *
     * @param $id
     * @return $this
     */
    public function setAccountId($id)
    {
        if (!empty($id) && ctype_print($id)) {
            $this->account_id = $id;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Sets the amount for this payout.
     * @param $amount
     * @return $this
     */
    public function setAmount($amount)
    {
        if (!empty($amount)) {
            $this->amount = $amount;
        }

        return $this;
    }

    /**
     * @interitdoc
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set Currency
     * @param CurrencyInterface $currency
     * @return $this
     */
    public function setCurrency(CurrencyInterface $currency)
    {
        if (!empty($currency)) {
            $this->currency = $currency;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getEffectiveDate()
    {
        return $this->effectiveDate;
    }

    /**
     * Set Effective date - date payout should be given to employees.
     * @param $effectiveDate
     * @return $this
     */
    public function setEffectiveDate($effectiveDate)
    {
        if (!empty($effectiveDate)) {
            $this->effectiveDate = $effectiveDate;
        }

        return $this;
    }

    /**
     * Get rate assigned to payout at effectiveDate
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * Set the rate in ether for the payouts of this transaction.
     * @param $rate
     * @return $this
     */
    public function setRate($rate)
    {
        if (!empty($rate)) {
            $this->rate = $rate;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getEthAmount()
    {
        return $this->eth;
    }

    /**
     * Set the Ethereum amount for this payout, once set by Smartex.
     * @param $amount
     * @return $this
     */
    public function setEthAmount($amount)
    {
        if (!empty($amount)) {
            $this->eth = $amount;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRequestDate()
    {
        return $this->requestDate;
    }

    /**
     * Set
     */
    public function setRequestDate($requestDate)
    {
        if (!empty($requestDate)) {
            $this->requestDate = $requestDate;
        }

        return $this;
    }
    /**
     * @inheritdoc
     */
    public function getInstructions()
    {
        return $this->instructions;
    }

    /**
     * Add Instruction of PayoutInstructionInterface type
     * Increases $this->amount by value.
     *
     * @param PayoutInstructionInterface $instruction
     * @return $this
     */
    public function addInstruction(PayoutInstructionInterface $instruction)
    {
        if (!empty($instruction)) {
            $this->instructions[] = $instruction;
        }

        return $this;
    }

    /**
     * Update Instruction - Supply an index of the instruction to update,
     * plus the function and single argument, to do something to an instruction.
     *
     * @param $index
     * @param $function
     * @param $argument
     * @return $this
     */
    public function updateInstruction($index, $function, $argument)
    {
        if (!empty($argument) && ctype_print($argument)) {
            $this->instructions[$index]->$function($argument);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets the status for the current payout request
     * @param $status
     * @return $this
     */
    public function setStatus($status)
    {
        if (!empty($status) && ctype_print($status)) {
            $this->status = trim($status);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set the token to authorize this request.
     * @param TokenInterface $token
     * @return $this
     */
    public function setToken(TokenInterface $token)
    {
        if (!empty($token)) {
            $this->token = $token;
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getResponseToken()
    {
        return $this->responseToken;
    }

    /**
     * Set Response Token - returned by Smartex when payout request is created
     *
     * @param $responseToken
     * @return $this
     */
    public function setResponseToken($responseToken)
    {
        if (!empty($responseToken)) {
            $this->responseToken = trim($responseToken);
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPricingMethod()
    {
        return $this->pricingMethod;
    }

    /**
     * Set the pricing method for this payout request
     * @param $pricingMethod
     * @return $this
     */
    public function setPricingMethod($pricingMethod)
    {
        if (!empty($pricingMethod) && ctype_print($pricingMethod)) {
            $this->pricingMethod = trim($pricingMethod);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Set the payroll providers reference for this payout
     *
     * @param $reference
     * @return $this
     */
    public function setReference($reference)
    {
        if (!empty($reference) && ctype_print($reference)) {
            $this->reference = trim($reference);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getNotificationEmail()
    {
        return $this->notificationEmail;
    }

    /**
     * Set an email address where updates to payout status should be sent.
     *
     * @param $notificationEmail
     * @return $this
     */
    public function setNotificationEmail($notificationEmail)
    {
        if (!empty($notificationEmail) && ctype_print($notificationEmail)) {
            $this->notificationEmail = trim($notificationEmail);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getNotificationUrl()
    {
        return $this->notificationUrl;
    }

    /**
     * Set a notification url - where updated Payout objects will be sent
     *
     * @param $notificationUrl
     * @return $this
     */
    public function setNotificationUrl($notificationUrl)
    {
        if (!empty($notificationUrl) && ctype_print($notificationUrl)) {
            $this->notificationUrl = trim($notificationUrl);
        }

        return $this;
    }
}
