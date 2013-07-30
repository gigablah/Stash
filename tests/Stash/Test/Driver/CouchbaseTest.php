<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Test\Driver;

/**
 * @package Stash
 * @author  Chris Heng <bigblah@gmail.com>
 */
class CouchbaseTest extends AbstractDriverTest
{
    protected $driverClass = 'Stash\Driver\Couchbase';

    protected function getOptions() {
        $options = parent::getOptions();
        $couchbaseOptions = array('bucket' => 'test', 'servers' => array('127.0.0.1:8091'));

        return array_merge($options, $couchbaseOptions);
    }
}
