<?php

namespace BsbDoctrineReconnect\DBAL;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use PDO;

class Statement implements \IteratorAggregate, DriverStatement
{
    private $_sql;

    /**
     * @var \Doctrine\DBAL\Statement
     */
    private $_stmt;

    /**
     * @var Connection
     */
    private $_conn;
    private $_values = array();
    private $_params = array();

    public function __construct($sql, Connection $conn)
    {
        $this->_sql  = $sql;
        $this->_conn = $conn;

        $this->createStatement();
    }

    private function createStatement()
    {
        $this->_stmt = $this->_conn->prepareUnwrapped($this->_sql);

        foreach ($this->_values as $args) {
            $this->bindValue($args[0], $args[1], $args[2]);
        }

        foreach ($this->_params as $args) {
            $this->bindParam($args[0], $args[1], $args[2]);
        }
    }

    public function execute($params = null)
    {
        $stmt    = null;
        $attempt = 0;
        $retry   = true;

        while ($retry) {
            $retry = false;

            try {
                $stmt = $this->_stmt->execute($params);
            } catch (DBALException $e) {
                if ($this->_conn->validateReconnectAttempt($e, $attempt)) {
                    $this->_conn->close();
                    $this->createStatement();
                    $attempt++;
                    $retry = true;
                } else {
                    throw $e;
                }
            }
        }

        return $stmt;
    }

    public function bindValue($param, $value, $type = null)
    {
        $this->_values[$param] = array($param, $value, $type);

        return $this->_stmt->bindValue($param, $value, $type);
    }

    public function bindParam($column, &$variable, $type = PDO::PARAM_STR, $length = null)
    {
        $this->_values[$column] = array($column, &$variable, $type);

        return $this->_stmt->bindParam($column, $variable, $type);
    }

    public function closeCursor()
    {
        return $this->_stmt->closeCursor();
    }

    public function columnCount()
    {
        return $this->_stmt->columnCount();
    }

    public function errorCode()
    {
        return $this->_stmt->errorCode();
    }

    public function errorInfo()
    {
        return $this->_stmt->errorInfo();
    }

    public function fetch($fetchStyle = PDO::FETCH_BOTH)
    {
        return $this->_stmt->fetch($fetchStyle);
    }

    public function fetchAll($fetchStyle = PDO::FETCH_BOTH)
    {
        return $this->_stmt->fetchAll($fetchStyle);
    }

    public function fetchColumn($columnIndex = 0)
    {
        return $this->_stmt->fetchColumn($columnIndex);
    }

    public function rowCount()
    {
        return $this->_stmt->rowCount();
    }

    /**
     * {@inheritdoc}
     */
    public function setFetchMode($fetchMode, $arg2 = null, $arg3 = null)
    {
        // This thin wrapper is necessary to shield against the weird signature
        // of PDOStatement::setFetchMode(): even if the second and third
        // parameters are optional, PHP will not let us remove it from this
        // declaration.
        if ($arg2 === null && $arg3 === null) {
            return parent::setFetchMode($fetchMode);
        }

        if ($arg3 === null) {
            return parent::setFetchMode($fetchMode, $arg2);
        }

        return parent::setFetchMode($fetchMode, $arg2, $arg3);
    }

    /**
     * Required by interface IteratorAggregate.
     *
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return $this->_stmt;
    }
}
