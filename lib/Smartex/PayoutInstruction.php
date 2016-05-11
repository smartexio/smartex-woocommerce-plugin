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
 * Class PayoutInstruction
 * @package Smartex
 */
class PayoutInstruction implements PayoutInstructionInterface
{
    /**
     * A transaction is unpaid when the payout in ether has not yet occured.
     */
    const STATUS_UNPAID = 'unpaid';

    /**
     * A transaction is marked as paid once the payroll is complete and ethers are
     * sent to the recipient
     */
    const STATUS_PAID = 'paid';

    /**
     * @var string
     */
    protected $id;

    /**
     * @var array
     */
    protected $eth;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $address;

    /**
     * @var float
     */
    protected $amount;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var array
     */
    protected $transactions = array();

    public function __construct()
    {
        $this->transactions = array();
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the Smartex ID for this payout instruction
     *
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        if (!empty($id)) {
            $this->id = trim($id);
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the employers label for this instruction.
     * @param $label
     * @return $this
     */
    public function setLabel($label)
    {
        if (!empty($label)) {
            $this->label = trim($label);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set the ether account for this instruction.
     * @param $address
     * @return $this
     */
    public function setAddress($address)
    {
        if (!empty($address)) {
            $this->address = trim($address);
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
     * Set the amount for this instruction.
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
     * @inheritdoc
     */
    public function getEth()
    {
        return $this->eth;
    }

    /**
     * Set ETH array (available once rates are set)
     * @param $eth
     * @return $this
     */
    public function setEth($eth)
    {
        if (!empty($eth) && is_array($eth)) {
            $this->eth = $eth;
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
     * Set the status for this instruction
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
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * Add payout transaction to the
     * @param PayoutTransactionInterface $transaction
     * @return $this
     */
    public function addTransaction(PayoutTransactionInterface $transaction)
    {
        if (!empty($transaction)) {
            $this->transactions[] = $transaction;
        }

        return $this;
    }
}
