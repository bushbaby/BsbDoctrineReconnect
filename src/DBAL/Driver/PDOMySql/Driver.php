<?php

namespace BsbDoctrineReconnect\DBAL\Driver\PDOMySql;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\PDOMySql\Driver as PDOMySqlDriver;

class Driver extends PDOMySqlDriver
{

    public function getReconnectExceptions()
    {
        return array(
            'SQLSTATE[HY000]: General error: 2006 MySQL server has gone away',
            'SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo failed: nodename nor servname provided, or not known'
        );
    }

    public function shouldStall(DBALException $e)
    {
        return stristr($e->getMessage(), 'php_network_getaddresses') !== false;
    }

}
