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

namespace Smartex\Client;

use Smartex\Client\Adapter\AdapterInterface;
use Smartex\Network\NetworkInterface;
use Smartex\TokenInterface;
use Smartex\InvoiceInterface;
use Smartex\PayoutInterface;
use Smartex\Util\Util;
use Smartex\PublicKey;
use Smartex\PrivateKey;

date_default_timezone_set('utc');
/**
 * Client used to send requests and receive responses for Smartex's Web API.
 *
 * @package Smartex
 */
class Client implements ClientInterface
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var TokenInterface
     */
    protected $token;

    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var PublicKey
     */
    protected $publicKey;

    /**
     * @var PrivateKey
     */
    protected $privateKey;

    /**
     * @var NetworkInterface
     */
    protected $network;

    /**
     * The network is either livenet or testnet and tells the client where to
     * send the requests.
     *
     * @param NetworkInterface $network
     */
    public function setNetwork(NetworkInterface $network)
    {
        $this->network = $network;
    }

    /**
     * Set the Public Key to use to help identify who you are to Smartex. Please
     * note that you must first pair your keys and get a token in return to use.
     *
     * @param PublicKey $key
     */
    public function setPublicKey(PublicKey $key)
    {
        $this->publicKey = $key;
    }

    /**
     * Set the Private Key to use, this is used when signing request strings
     *
     * @param PrivateKey $key
     */
    public function setPrivateKey(PrivateKey $key)
    {
        $this->privateKey = $key;
    }

    /**
     * Set the network adapter object to use.
     *
     * @param AdapterInterface $adapter
     */
    public function setAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Assigns the token to use for a request.
     *
     * @param TokenInterface $token
     * @return ClientInterface
     */
    public function setToken(TokenInterface $token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function createInvoice(InvoiceInterface $invoice)
    {
        $request = $this->createNewRequest();
        $request->setMethod(Request::METHOD_POST);
        $request->setPath('invoices');

        $currency     = $invoice->getCurrency();
        $item         = $invoice->getItem();
        $buyer        = $invoice->getBuyer();
        $buyerAddress = $buyer->getAddress();

        $this->checkPriceAndCurrency($item->getPrice(), $currency->getCode());

        $body = array(
            'price'             => $item->getPrice(),
            'currency'          => $currency->getCode(),
            'posData'           => $invoice->getPosData(),
            'notificationURL'   => $invoice->getNotificationUrl(),
            'transactionSpeed'  => $invoice->getTransactionSpeed(),
            'fullNotifications' => $invoice->isFullNotifications(),
            'notificationEmail' => $invoice->getNotificationEmail(),
            'redirectURL'       => $invoice->getRedirectUrl(),
            'orderID'           => $invoice->getOrderId(),
            'itemDesc'          => $item->getDescription(),
            'itemCode'          => $item->getCode(),
            'physical'          => $item->isPhysical(),
            'buyerName'         => trim(sprintf('%s %s', $buyer->getFirstName(), $buyer->getLastName())),
            'buyerAddress1'     => isset($buyerAddress[0]) ? $buyerAddress[0] : '',
            'buyerAddress2'     => isset($buyerAddress[1]) ? $buyerAddress[1] : '',
            'buyerCity'         => $buyer->getCity(),
            'buyerState'        => $buyer->getState(),
            'buyerZip'          => $buyer->getZip(),
            'buyerCountry'      => $buyer->getCountry(),
            'buyerEmail'        => $buyer->getEmail(),
            'buyerPhone'        => $buyer->getPhone(),
            'guid'              => Util::guid(),
            'token'             => $this->token->getToken(),
        );

        $request->setBody(json_encode($body));

        $this->addIdentityHeader($request);
        $this->addSignatureHeader($request);

        $this->request  = $request;
        $this->response = $this->sendRequest($request);

        $body = json_decode($this->response->getBody(), true);

        $error_message = false;
        $error_message = (!empty($body['error'])) ? $body['error'] : $error_message;
        $error_message = (!empty($body['errors'])) ? $body['errors'] : $error_message;
        $error_message = (is_array($error_message)) ? implode("\n", $error_message) : $error_message;

        if (false !== $error_message) {
            throw new \Exception($error_message);
        }

        $data = $body['data'];
        $invoiceToken = new \Smartex\Token();

        $invoice
            ->setToken($invoiceToken->setToken($data['token']))
            ->setId($data['id'])
            ->setUrl($data['url'])
            ->setStatus($data['status'])
            ->setEthPrice($data['ethPrice'])
            ->setPrice($data['price'])
            ->setInvoiceTime($data['invoiceTime'])
            ->setExpirationTime($data['expirationTime'])
            ->setCurrentTime($data['currentTime'])
            ->setEthPaid($data['ethPaid'])
            ->setRate($data['rate'])
            ->setExceptionStatus($data['exceptionStatus']);

        return $invoice;
    }

    /**
     * @inheritdoc
     */
    public function getCurrencies()
    {
        $this->request = $this->createNewRequest();
        $this->request->setMethod(Request::METHOD_GET);
        $this->request->setPath('currencies');

        $this->response = $this->sendRequest($this->request);

        $body = json_decode($this->response->getBody(), true);

        if (empty($body['data'])) {
            throw new \Exception('Error with request: no data returned');
        }

        $currencies = $body['data'];

        array_walk($currencies, function (&$value, $key) {
            $currency = new \Smartex\Currency();
            $currency
                ->setCode($value['code'])
                ->setSymbol($value['symbol'])
                ->setPrecision($value['precision'])
                ->setExchangePctFee($value['exchangePctFee'])
                ->setPayoutEnabled($value['payoutEnabled'])
                ->setName($value['name'])
                ->setPluralName($value['plural'])
                ->setAlts($value['alts'])
                ->setPayoutFields($value['payoutFields']);

            $value = $currency;
        });

        return $currencies;
    }

    /**
     * @inheritdoc
     */
    public function createPayout(PayoutInterface $payout)
    {
        $request = $this->createNewRequest();
        $request->setMethod($request::METHOD_POST);
        $request->setPath('payouts');

        $amount         = $payout->getAmount();
        $currency       = $payout->getCurrency();
        $effectiveDate  = $payout->getEffectiveDate();
        $token          = $payout->getToken();

        $body = array(
            'token'         => $token->getToken(),
            'amount'        => $amount,
            'currency'      => $currency->getCode(),
            'instructions'  => array(),
            'effectiveDate' => $effectiveDate,
            'pricingMethod' => $payout->getPricingMethod(),
            'guid'          => Util::guid(),
        );

        // Optional
        foreach (array('reference','notificationURL','notificationEmail') as $value) {
            $function = 'get' . ucfirst($value);

            if ($payout->$function() != null) {
                $body[$value] = $payout->$function();
            }
        }

        // Add instructions
        foreach ($payout->getInstructions() as $instruction) {
            $body['instructions'][] = array(
                'label'   => $instruction->getLabel(),
                'address' => $instruction->getAddress(),
                'amount'  => $instruction->getAmount()
            );
        }

        $request->setBody(json_encode($body));

        $this->addIdentityHeader($request);
        $this->addSignatureHeader($request);

        $this->request  = $request;
        $this->response = $this->sendRequest($request);

        $body = json_decode($this->response->getBody(), true);

        $error_message = false;
        $error_message = (!empty($body['error'])) ? $body['error'] : $error_message;
        $error_message = (!empty($body['errors'])) ? $body['errors'] : $error_message;
        $error_message = (is_array($error_message)) ? implode("\n", $error_message) : $error_message;

        if (false !== $error_message) {
            throw new \Exception($error_message);
        }

        $data = $body['data'];

        $payout
            ->setId($data['id'])
            ->setAccountId($data['account'])
            ->setResponseToken($data['token'])
            ->setStatus($data['status']);

        foreach ($data['instructions'] as $c => $instruction) {
            $payout->updateInstruction($c, 'setId', $instruction['id']);
        }

        return $payout;
    }

    /**
     * @inheritdoc
     */
    public function getPayouts($status = null)
    {
        $request = $this->createNewRequest();
        $request->setMethod(Request::METHOD_GET);

        $path = 'payouts?token='
                    . $this->token->getToken()
                    . (($status == null) ? '' : '&status=' . $status);

        $request->setPath($path);

        $this->addIdentityHeader($request);
        $this->addSignatureHeader($request);

        $this->request  = $request;
        $this->response = $this->sendRequest($this->request);

        $body = json_decode($this->response->getBody(), true);

        $error_message = false;
        $error_message = (!empty($body['error'])) ? $body['error'] : $error_message;
        $error_message = (!empty($body['errors'])) ? $body['errors'] : $error_message;
        $error_message = (is_array($error_message)) ? implode("\n", $error_message) : $error_message;

        if (false !== $error_message) {
            throw new \Exception($error_message);
        }

        $payouts = array();

        array_walk($body['data'], function ($value, $key) use (&$payouts) {
            $payout = new \Smartex\Payout();
            $payout
                ->setId($value['id'])
                ->setAccountId($value['account'])
                ->setCurrency(new \Smartex\Currency($value['currency']))
                ->setEffectiveDate($value['effectiveDate'])
                ->setRequestdate($value['requestDate'])
                ->setPricingMethod($value['pricingMethod'])
                ->setStatus($value['status'])
                ->setAmount($value['amount'])
                ->setResponseToken($value['token'])
                ->setRate(@$value['rate'])
                ->setEthAmount(@$value['eth'])
                ->setReference(@$value['reference'])
                ->setNotificationURL(@$value['notificationURL'])
                ->setNotificationEmail(@$value['notificationEmail']);

            array_walk($value['instructions'], function ($value, $key) use (&$payout) {
                $instruction = new \Smartex\PayoutInstruction();
                $instruction
                    ->setId($value['id'])
                    ->setLabel($value['label'])
                    ->setAddress($value['address'])
                    ->setAmount($value['amount'])
                    ->setStatus($value['status']);

                array_walk($value['transactions'], function ($value, $key) use (&$instruction) {
                    $transaction = new \Smartex\PayoutTransaction();
                    $transaction
                        ->setTransactionId($value['txid'])
                        ->setAmount($value['amount'])
                        ->setDate($value['date']);

                    $instruction->addTransaction($transaction);
                });

                $payout->addInstruction($instruction);
            });

            $payouts[] = $payout;
        });

        return $payouts;
    }

    /**
     * @inheritdoc
     */
    public function deletePayout(PayoutInterface $payout)
    {
        $request = $this->createNewRequest();
        $request->setMethod(Request::METHOD_DELETE);
        $request->setPath(sprintf('payouts/%s?token=%s', $payout->getId(), $payout->getResponseToken()));

        $this->addIdentityHeader($request);
        $this->addSignatureHeader($request);

        $this->request  = $request;
        $this->response = $this->sendRequest($this->request);

        $body = json_decode($this->response->getBody(), true);

        if (empty($body['data'])) {
            throw new \Exception('Error with request: no data returned');
        }

        $data = $body['data'];

        $payout->setStatus($data['status']);

        return $payout;
    }

    /**
     * @inheritdoc
     */
    public function getPayout($payoutId)
    {
        $request = $this->createNewRequest();
        $request->setMethod(Request::METHOD_GET);
        $request->setPath(sprintf('payouts/%s?token=%s', $payoutId, $this->token->getToken()));

        $this->addIdentityHeader($request);
        $this->addSignatureHeader($request);

        $this->request  = $request;
        $this->response = $this->sendRequest($this->request);

        $body = json_decode($this->response->getBody(), true);

        if (empty($body['data'])) {
            throw new \Exception('Error with request: no data returned');
        }

        $data   = $body['data'];
        $payout = new \Smartex\Payout();

        $payout
            ->setId($data['id'])
            ->setAccountId($data['account'])
            ->setStatus($data['status'])
            ->setCurrency(new \Smartex\Currency($data['currency']))
            ->setRate(@$data['rate'])
            ->setAmount($data['amount'])
            ->setEthAmount(@$data['eth'])
            ->setPricingMethod(@$data['pricingMethod'])
            ->setReference(@$data['reference'])
            ->setNotificationEmail(@$data['notificationEmail'])
            ->setNotificationUrl(@$data['notificationURL'])
            ->setRequestDate($data['requestDate'])
            ->setEffectiveDate($data['effectiveDate'])
            ->setResponseToken($data['token']);

        array_walk($data['instructions'], function ($value, $key) use (&$payout) {
            $instruction = new \Smartex\PayoutInstruction();
            $instruction
                ->setId($value['id'])
                ->setLabel($value['label'])
                ->setAddress($value['address'])
                ->setStatus($value['status'])
                ->setAmount($value['amount'])
                ->setEth($value['eth']);

            array_walk($value['transactions'], function ($value, $key) use (&$instruction) {
                $transaction = new \Smartex\PayoutTransaction();
                $transaction
                    ->setTransactionId($value['txid'])
                    ->setAmount($value['amount'])
                    ->setDate($value['date']);

                $instruction->addTransaction($transaction);
            });

            $payout->addInstruction($instruction);
        });

        return $payout;
    }

    /**
     * @inheritdoc
     */
    public function getTokens()
    {
        $request = $this->createNewRequest();
        $request->setMethod(Request::METHOD_GET);
        $request->setPath('tokens');

        $this->addIdentityHeader($request);
        $this->addSignatureHeader($request);

        $this->request  = $request;
        $this->response = $this->sendRequest($this->request);

        $body = json_decode($this->response->getBody(), true);

        if (empty($body['data'])) {
            throw new \Exception('Error with request: no data returned');
        }

        $tokens = array();

        array_walk($body['data'], function ($value, $key) use (&$tokens) {
            $key   = current(array_keys($value));
            $value = current(array_values($value));
            $token = new \Smartex\Token();
            $token
                ->setFacade($key)
                ->setToken($value);

            $tokens[$token->getFacade()] = $token;
        });

        return $tokens;
    }

    /**
     * @inheritdoc
     */
    public function createToken(array $payload = array())
    {
        if (isset($payload['pairingCode']) && 1 !== preg_match('/^[a-zA-Z0-9]{7}$/', $payload['pairingCode'])) {
            throw new \Exception('[ERROR] In Client::createToken(): The pairing code provided is not legal.');
        }

        $this->request = $this->createNewRequest();
        $this->request->setMethod(Request::METHOD_POST);
        $this->request->setPath('tokens');

        $payload['guid'] = Util::guid();

        $this->request->setBody(json_encode($payload));
        $this->response = $this->sendRequest($this->request);

        $body = json_decode($this->response->getBody(), true);

        if (isset($body['error'])) {
            throw new \Smartex\Client\SmartexException($this->response->getStatusCode() . ': ' . $body['error']);
        }

        $tkn = $body['data'][0];
        $createdAt = new \DateTime();
        $pairingExpiration = new \DateTime();
        $token = new \Smartex\Token();

        $token
            ->setPolicies($tkn['policies'])
            ->setToken($tkn['token'])
            ->setFacade($tkn['facade'])
            ->setCreatedAt($createdAt->setTimestamp(floor($tkn['dateCreated'] / 1000)));

        if (isset($tkn['resource'])) {
            $token->setResource($tkn['resource']);
        }

        if (isset($tkn['pairingCode'])) {
            $token->setPairingCode($tkn['pairingCode']);
            $token->setPairingExpiration($pairingExpiration->setTimestamp(floor($tkn['pairingExpiration'] / 1000)));
        }

        return $token;
    }

    /**
     * Returns the Response object that Smartex returned from
     * the request that was sent.
     *
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Returns the request object that was sent to Smartex.
     *
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @inheritdoc
     */
    public function getInvoice($invoiceId)
    {
        $this->request = $this->createNewRequest();
        $this->request->setMethod(Request::METHOD_GET);

        if ($this->token->getFacade() === 'merchant') {
            $this->request->setPath(sprintf('invoices/%s?token=%s', $invoiceId, $this->token->getToken()));
            $this->addIdentityHeader($this->request);
            $this->addSignatureHeader($this->request);
        } else {
            $this->request->setPath(sprintf('invoices/%s', $invoiceId));
        }

        $this->response = $this->sendRequest($this->request);

        $body = json_decode($this->response->getBody(), true);

        if (isset($body['error'])) {
            throw new \Exception($body['error']);
        }

        $data = $body['data'];
        $invoice = new \Smartex\Invoice();
        $invoiceToken = new \Smartex\Token();

        $invoice
            ->setToken($invoiceToken->setToken($data['token']))
            ->setUrl($data['url'])
            ->setPosData($data['posData'])
            ->setStatus($data['status'])
            ->setEthPrice($data['ethPrice'])
            ->setPrice($data['price'])
            ->setCurrency(new \Smartex\Currency($data['currency']))
            ->setOrderId($data['orderId'])
            ->setInvoiceTime($data['invoiceTime'])
            ->setExpirationTime($data['expirationTime'])
            ->setCurrentTime($data['currentTime'])
            ->setId($data['id'])
            ->setEthPaid($data['ethPaid'])
            ->setRate($data['rate'])
            ->setExceptionStatus($data['exceptionStatus']);

        return $invoice;
    }

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function sendRequest(RequestInterface $request)
    {
        if (null === $this->adapter) {
            // Uses the default adapter
            $this->adapter = new \Smartex\Client\Adapter\CurlAdapter();
        }

        return $this->adapter->sendRequest($request);
    }

    /**
     * @param RequestInterface $request
     * @throws \Exception
     */
    protected function addIdentityHeader(RequestInterface $request)
    {
        if (null === $this->publicKey) {
            throw new \Exception('[ERROR] In Client::addIdentityHeader(): No public key value found. Please set your kublic key first before you can add the x-identity header.');
        }

        $request->setHeader('x-identity', (string) $this->publicKey);
    }

    /**
     * @param RequestInterface $request
     * @throws \Exception
     */
    protected function addSignatureHeader(RequestInterface $request)
    {
        if (null === $this->privateKey) {
            throw new \Exception('Please set your Private Key');
        }

        if (true == property_exists($this->network, 'isPortRequiredInUrl')) {
            if ($this->network->isPortRequiredInUrl === true) {
                $url = $request->getUriWithPort();
            } else {
                $url = $request->getUri();
            }
        } else {
            $url = $request->getUri();
        }

        $message = sprintf(
            '%s%s',
            $url,
            $request->getBody()
        );

        $signature = $this->privateKey->sign($message);

        $request->setHeader('x-signature', $signature);
    }

    /**
     * @return RequestInterface
     */
    protected function createNewRequest()
    {
        $request = new Request();
        $request->setHost($this->network->getApiHost());
        $request->setPort($this->network->getApiPort());
        $this->prepareRequestHeaders($request);

        return $request;
    }

    /**
     * Prepares the request object by adding additional headers
     *
     * @see http://en.wikipedia.org/wiki/User_agent
     * @param RequestInterface $request
     */
    protected function prepareRequestHeaders(RequestInterface $request)
    {
        $request->setHeader(
            'User-Agent',
            sprintf('%s/%s (PHP %s)', self::NAME, self::VERSION, phpversion())
        );

        $request->setHeader('X-Smartex-Plugin-Info', sprintf('%s/%s', self::NAME, self::VERSION));
        $request->setHeader('Content-Type', 'application/json');
        $request->setHeader('X-Accept-Version', '1.0.0');
    }

    /**
     * @param string $price
     * @param string $currency
     * @throws \Exception
     */
    protected function checkPriceAndCurrency($price, $currency)
    {
        // Get the decimal precision of the price
        $decimalPrecision = strlen(substr($price, strpos($price, '.') + 1));

        if (($decimalPrecision > 2 && $currency != 'ETH') || $decimalPrecision > 6) {
            throw new \Exception('Incorrect price format or currency type.');
        }
    }
}
