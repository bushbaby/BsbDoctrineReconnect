<?php

namespace BsbDoctrineReconnect\DBAL\Driver\PDOMySql;

use BsbDoctrineReconnect\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\PDOMySql\Driver as PDOMySqlDriver;

/**
 * Class Driver
 *
 * @package BsbDoctrineReconnect\DBAL\Driver\PDOMySql
 */
class Driver extends PDOMySqlDriver implements DriverInterface
{
    /**
     * @return array
     */
    public function getReconnectExceptions()
    {
        return [
            'SQLSTATE[HY000]: General error: 2006 MySQL server has gone away',
            'SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo failed: nodename nor servname provided, '
            . 'or not known'
        ];
    }

    /**
     * @param DBALException $e
     * @return bool
     */
    public function shouldStall(DBALException $e)
    {
        return stristr($e->getMessage(), 'php_network_getaddresses') !== false;
    }
}
