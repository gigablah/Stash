<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Driver;

use Stash;
use Stash\Interfaces\DriverInterface;

/**
 * The Couchbase driver is a wrapper around the php-couchbase extension, which
 * stores data in the CouchBase server.
 *
 * @package Stash
 * @author  Chris Heng <bigblah@gmail.com>
 */
class Couchbase implements DriverInterface
{
    protected $defaultOptions = array(
        'user' => '',
        'password' => '',
        'bucket' => 'default',
        'persistent' => true
    );
    protected $couchbase;
    protected $keyCache = array();

    /**
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        if (!self::isAvailable()) {
            throw new \RuntimeException('Unable to load Couchbase driver without Couchbase extension.');
        }

        // Normalize Server Options
        if (isset($options['servers'])) {
            $servers = is_array($options['servers'])
                ? $options['servers']
                : array($options['servers']);
            unset($options['servers']);
        } else {
            $servers = array('127.0.0.1');
        }

        // Merge in default values.
        $options = array_merge($this->defaultOptions, $options);

        $this->couchbase = new \Couchbase($servers, $options['user'], $options['password'], $options['bucket'], $options['persistent']);
    }

    /**
     * Empty destructor to maintain a standardized interface across all drivers.
     *
     */
    public function __destruct()
    {
    }

    /**
     *
     *
     * @param array $key
     * @return array
     */
    public function getData($key)
    {
        $json = $this->couchbase->get($this->makeKeyString($key));

        if ($json && null !== $data = json_decode($json, true)) {
            if (isset($data['data'])) {
                $data['data'] = unserialize($data['data']);
            }

            return $data;
        }

        return false;
    }

    /**
     *
     *
     * @param array $key
     * @param array $data
     * @param int $expiration
     * @return bool
     */
    public function storeData($key, $data, $expiration)
    {
        $store = json_encode(array('data' => serialize($data), 'expiration' => $expiration));

        return (Boolean) $this->couchbase->set($this->makeKeyString($key), $store, $expiration);
    }

    /**
     * Clears the cache tree using the key array provided as the key. If called with no arguments the entire cache gets
     * cleared.
     *
     * @param null|array $key
     * @return bool
     */
    public function clear($key = null)
    {
        if (is_null($key)) {
            $this->couchbase->flush();

            return true;
        }

        $keyString = $this->makeKeyString($key, true);
        $keyReal = $this->makeKeyString($key);
        $this->couchbase->increment($keyString, 1, true); // increment index for children items
        $this->couchbase->delete($keyReal); // remove direct item.
        $this->keyCache = array();

        return true;
    }

    /**
     *
     * @return bool
     */
    public function purge()
    {
        return true;
    }

    /**
     *
     *
     * @return bool
     */
    static public function isAvailable()
    {
        return class_exists('Couchbase', false);
    }


    protected function makeKeyString($key, $path = false)
    {
        // array(name, sub);
        // a => name, b => sub;

        $key = \Stash\Utilities::normalizeKeys($key);

        $keyString = 'cache:::';
        foreach ($key as $name) {
            //a. cache:::name
            //b. cache:::name0:::sub
            $keyString .= $name;

            //a. :pathdb::cache:::name
            //b. :pathdb::cache:::name0:::sub
            $pathKey = ':pathdb::' . $keyString;
            $pathKey = md5($pathKey);

            if (!isset($this->keyCache[$pathKey])) {
                $this->keyCache[$pathKey] = $this->couchbase->get($pathKey);
            }

            $index = $this->keyCache[$pathKey];

            //a. cache:::name0:::
            //b. cache:::name0:::sub1:::
            $keyString .= '_' . $index . ':::';
        }

        return $path ? $pathKey : md5($keyString);
    }
}
