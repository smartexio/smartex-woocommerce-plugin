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
 * Interface PayoutInterface
 * @package Smartex
 */
interface PayoutInterface
{
    /**
     * The payroll flow begins with the payroll provider creating a payout request,
     * using the payroll facade.
     */
    const STATUS_NEW = 'new';

    /**
     * Once Smartex receives a corresponding ACH transfer from the payroll provider,
     * the finance group marks the payout as 'funded'.
     */
    const STATUS_FUNDED = 'funded';

    /**
     * A payout is marked as processing when it has begun to be processed, but
     * is not yet complete.
     */
    const STATUS_PROCESSING = 'processing';

    /**
     * A batch is marked as complete when all payments have been delivered to the
     * destinations.
     */
    const STATUS_COMPLETE = 'complete';

    /**
     * A payout can be cancelled by making a request on the API with a payout specific
     * token (returned when payout was requested)
     */
    const STATUS_CANCELLED = 'cancelled';

    /**
     * The batch ID of the payout assigned by Smartex.io
     *
     * @return string
     */
    public function getId();

    /**
     * Return the account parameter given by Smartex.
     *
     * @return string
     */
    public function getAccountId();

    /**
     * This is the total amount of the batch. Note, this amount must equal the sum of
     * the amounts paid to the individual addresses. Adding an instruction to a payout
     * will automatically increase this value.
     *
     * @return float
     */
    public function getAmount();

    /**
     * This will return the Ethereum amount for this transaction. This
     * should only be called once the payout is funded
     *
     * @return float
     */
    public function getEthAmount();

    /**
     * This is the currency code set for the batch amount.  The pricing currencies
     * currently supported are USD and EUR.
     *
     * @return \Smartex\CurrencyInterface
     */
    public function getCurrency();

    /**
     * The date and time when the batch should be sent.  The time is in milliseconds
     * since midnight January 1, 1970.
     *
     * @return \DateTime
     */
    public function getEffectiveDate();

    /**
     * The date and time when the batch was created by the payee. The time is in milliseconds
     * since midnight January 1, 1970.
     *
     * @return \DateTime
     */
    public function getRequestDate();

    /**
     * This is an array containing the details of the batch.  Each payment instruction
     * entry specifies the details for a single payout.  There is no specific upper
     * limit on the number of payment instruction entries in the batch instructions
     *
     * @return array
     */
    public function getInstructions();

    /**
     * Status updates for the batch will be sent to this email address. If no value is
     * specified, the Merchant email will be used as a notification.
     *
     * @return string
     */
    public function getNotificationEmail();

    /**
     * A URL to send status update messages to your server (this must be an https
     * URL, unencrypted http URLs or any other type of URL is not supported).
     * Smartex.io will send a POST request with a JSON encoding of the batch to this
     * URL when the batch status changes.
     *
     * @return string
     */
    public function getNotificationUrl();

    /**
     * The method you select determines the exchange rate used to compute the
     * payouts for the entire batch.
     *
     * @return string
     */
    public function getPricingMethod();

    /**
     * This is your reference label for this batch.  It will be passed­through on each
     * response for you to identify the batch in your system.  Maximum string length is
     * 100 characters.
     * This passthrough variable can be a JSON­encoded string, for example
     * { "ref":711454, "company":"Random Company Ltd." }
     *
     * @return string
     */
    public function getReference();

    /**
     * Get the status for this payout.
     *
     * @return $string;
     */
    public function getStatus();

    /**
     * Get the payroll token
     *
     * @return mixed
     */
    public function getToken();

    /**
     * Get the response token, for cancelling payout requests later.
     *
     * @return string
     */
    public function getResponseToken();
}
