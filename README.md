# BsbDoctrineReconnect

Zend Framework 2 module which attempts to reconnect when the database has 'gone away'.

It does this by overriding a few doctrine DBAL Classes via the configuration provided by the DoctrineORMModule.

I did not invent this method. I just liked it and thought it deserved a module so it can be reused easily. Credits ought to go to [circlical](http://circlical.com/blog/2013/9/12/mysql-server-has-gone-away-atop-doctrine2-and-zend-framework-2).

## Installation

```sh
php composer.phar require bushbaby/doctrine-reconnect
```

Then add `BsbDoctrineReconnect` to your `config/application.config.php`.

## Adjust your Doctrine DB Config

Copy the config/bsb-doctrine-reconnect.local.php.dist to your config/autoload/config/bsb-doctrine-reconnect.local.php directory and override the specified driverClass for each connection you have have defined. 

```php
<?php
return array(
    'doctrine' => array(
        'connection' => array(
            'orm_default' => array(
                'driverClass' => 'BsbDoctrineReconnect\DBAL\Driver\PDOMySql\Driver',
                'wrapperClass' => 'BsbDoctrineReconnect\DBAL\Connection',

                'params' => array(
                    'driverOptions' => array(
                        'x_reconnect_attempts' => 10,
                    ),
                )
            )
        )
    ),
);
```

## Test

You can manually test this actually works by connecting to mysql from the cli and killing the appropiate connection;

```mysql
mysql> SHOW PROCESSLIST;
+----+------+-----------+----------+---------+------+-------+------------------+
| Id | User | Host      | db       | Command | Time | State | Info             |
+----+------+-----------+----------+---------+------+-------+------------------+
|  1 | root | localhost | database | Sleep   |    1 |       | NULL             |
|  4 | root | localhost | NULL     | Query   |    0 | NULL  | SHOW PROCESSLIST |
+----+------+-----------+----------+---------+------+-------+------------------+
4 rows in set (0.00 sec)

mysql> KILL 1;
Query OK, 0 rows affected (0.00 sec)
```

## Known limitations

This method only works for statements outside of transactions.
