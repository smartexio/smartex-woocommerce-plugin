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

namespace Smartex\Storage;

/**
 * @package Smartex
 */
class EncryptedFilesystemStorage implements StorageInterface
{
    /**
     * @var string
     */
    private $password;

    /**
     * Initialization Vector
     */
    const IV = '0000000000000000';

    /**
     * @var string
     */
    const METHOD = 'AES-128-CBC';

    /**
     * @var int
     */
    const OPENSSL_RAW_DATA = 1;

    /**
     * @param string $password
     */
    public function __construct($password)
    {
        $this->password = $password;
    }

    /**
     * @inheritdoc
     */
    public function persist(\Smartex\KeyInterface $key)
    {
        $path = $key->getId();
        $data = serialize($key);

        $encrypted = $this->dataEncrypt($data);
        $encoded   = $this->dataEncode($encrypted);

        $this->saveToFile($encoded, $path);
    }

    /**
     * @inheritdoc
     */
    public function load($id)
    {
        $encoded   = $this->readFromFile($id);
        $decoded   = $this->dataDecode($encoded);
        $decrypted = $this->dataDecrypt($decoded);

        return unserialize($decrypted);
    }
    
    /**
     * @param string $data
     * @return string
     * @throws \Exception
     */
    private function dataEncrypt($data)
    {
        $encrypted = openssl_encrypt($data, self::METHOD, $this->password, self::OPENSSL_RAW_DATA, self::IV);

        if ($encrypted === false) {
            throw new \Exception('[ERROR] In EncryptedFilesystemStorage::dataEncrypt(): Could not encrypt data "' . $data . '". OpenSSL error(s) are: "' . $this->getOpenSslErrors() . '".');
        }

        return $encrypted;
    }

    /**
     * @param string $data
     * @return string
     * @throws \Exception
     */
    private function dataDecrypt($data)
    {
        $decrypted = openssl_decrypt($data, self::METHOD, $this->password, self::OPENSSL_RAW_DATA, self::IV);

        if ($decrypted === false) {
            throw new \Exception('[ERROR] In EncryptedFilesystemStorage::dataDecrypt(): Could not decrypt data "' . $data . '". OpenSSL error(s) are: "' . $this->getOpenSslErrors() . '".');
        }

        return $decrypted;
    }

    /**
     * @param string $data
     * @return string
     * @throws \Exception
     */
    private function dataEncode($data)
    {
        $encoded = base64_encode($data);

        if ($encoded === false) {
            throw new \Exception('[ERROR] In EncryptedFilesystemStorage::dataEncode(): Could not encode data "' . $data . '".');
        }

        return $encoded;
    }

    /**
     * @param string $data
     * @return string
     * @throws \Exception
     */
    private function dataDecode($data)
    {
        $decoded = base64_decode($data);

        if ($decoded === false) {
            throw new \Exception('[ERROR] In EncryptedFilesystemStorage::dataDecode(): Could not decode data "' . $data . '".');
        }

        return $decoded;
    }

    /**
     * @param string $data
     * @param string $path
     * @throws \Exception
     */
    private function saveToFile($data, $path)
    {
        if (file_put_contents($path, $data) === false) {
            throw new \Exception('[ERROR] In EncryptedFilesystemStorage::saveToFile(): Could not write to the file "' . $path . '".');
        }
    }

    /**
     * @param string $path
     * @return string
     * @throws \Exception
     */
    private function readFromFile($path)
    {
        if (is_file($path) === false || is_readable($path) === false) {
            throw new \Exception('[ERROR] In EncryptedFilesystemStorage::readFromFile(): The file "' . $path . '" does not exist or cannot be read, check permissions.');
        }

        $data = file_get_contents($path);

        if ($data === false) {
            throw new \Exception('[ERROR] In EncryptedFilesystemStorage::readFromFile(): The file "' . $path . '" cannot be read, check permissions.');
        }

        return $data;
    }

    /**
     * @return string
     */
    private function getOpenSslErrors()
    {
        $openssl_error_msg = '';

        while ($msg = openssl_error_string()) {
            $openssl_error_msg .= $msg . "\r\n";
        }

        return $openssl_error_msg;
    }
}
