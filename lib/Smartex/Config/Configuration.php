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

namespace Smartex\Config;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * This class contains all the valid configuration settings that can be used.
 * If you update this file to add new settings, please make sure you update the
 * documentation as well.
 *
 * @see http://symfony.com/doc/current/components/config/definition.html
 * @package Smartex
 */
class Configuration implements ConfigurationInterface
{
    private $pubfilename = '/.smartex/api.pub';
    private $prifilename = '/.smartex/api.key';
    private $sinfilename = '/.smartex/api.sin';
    private $defstorage  = 'Smartex\Storage\EncryptedFilesystemStorage';
    private $networks    = array('livenet', 'testnet');
    private $adapters    = array('curl', 'mock');

    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('smartex');

        $rootNode
            ->children()
                ->scalarNode('public_key')
                    ->info('Public Key Filename')
                    ->defaultValue($this->getPubKeyFilename())
                ->end()
                ->scalarNode('private_key')
                    ->info('Private Key Filename')
                    ->defaultValue($this->getPriKeyFilename())
                ->end()
                ->scalarNode('sin_key')
                    ->info('SIN Filename')
                    ->defaultValue($this->getSinFilename())
                ->end()
                ->enumNode('network')
                    ->values($this->networks)
                    ->info('Network')
                    ->defaultValue($this->networks[0])
                ->end()
                ->enumNode('adapter')
                    ->values($this->adapters)
                    ->info('Client Adapter')
                    ->defaultValue($this->adapters[0])
                ->end()
                ->append($this->addKeyStorageNode())
                ->scalarNode('key_storage_password')
                    ->info('Used to encrypt and decrypt keys when saving to filesystem')
                    ->defaultNull()
                ->end()
            ->end();

        return $treeBuilder;
    }

    /**
     * Adds the key_storage node with validation rules.
     * key_storage MUST:
     *     * implement Smartex\Storage\StorageInterface
     *     * be a class that can be loaded
     *
     * @return \Symfony\Component\Config\Definition\Builder\NodeDefinition
     * @throws \Exception
     */
    protected function addKeyStorageNode()
    {
        $builder = new TreeBuilder();
        $node    = $builder->root('key_storage', 'scalar');

        $node
            ->info('Class that is used to store your keys')
            ->defaultValue($this->defstorage)
            ->validate()
                ->always()
                ->then(function ($value) {
                    if (!class_exists($value)) {
                        throw new \Exception(
                            sprintf(
                                'Could not find class "%s".',
                                $value
                            )
                        );
                    }

                    // requires PHP >= 5.3.7
                    if (is_subclass_of($value, 'Smartex\Storage\StorageInterface') === false) {
                        throw new \Exception('[ERROR] In Configuration::addKeyStorageNode(): "' . $value . '" does not implement "Smartex\Storage\StorageInterface".');
                    }

                    return $value;
                })
            ->end();

        return $node;
    }

    /**
     * @return string
     */
    private function getPubKeyFilename()
    {
        return getenv('HOME') . $this->pubfilename;
    }

    /**
     * @return string
     */
    private function getPriKeyFilename()
    {
        return getenv('HOME') . $this->prifilename;
    }

    /**
     * @return string
     */
    private function getSinFilename()
    {
        return getenv('HOME') . $this->sinfilename;
    }
}
