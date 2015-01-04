<?php

namespace BsbDoctrineReconnect\DBAL;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use PDO;

class Statement implements \IteratorAggregate, DriverStatement
{
    /**
     * @var string
     */
    private $sql;

    /**
     * @var \Doctrine\DBAL\Statement
     */
    private $stmt;

    /**
     * @var Connection
     */
    private $conn;

    /**
     * @var array binding values
     */
    private $values = array();

    /**
     * @var array binding parameters
     */
    private $params = array();

    /**
     * @param            string $sql
     * @param Connection $conn
     */
    public function __construct($sql, Connection $conn)
    {
        $this->sql  = $sql;
        $this->conn = $conn;

        $this->createStatement();
    }

    private function createStatement()
    {
        $this->stmt = $this->conn->prepareUnwrapped($this->sql);

        foreach ($this->values as $args) {
            $this->bindValue($args[0], $args[1], $args[2]);
        }

        foreach ($this->params as $args) {
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
                $stmt = $this->stmt->execute($params);
            } catch (DBALException $e) {
                if ($this->conn->validateReconnectAttempt($e, $attempt)) {
                    $this->conn->close();
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
        $this->values[$param] = array($param, $value, $type);

        return $this->stmt->bindValue($param, $value, $type);
    }

    public function bindParam($column, &$variable, $type = PDO::PARAM_STR, $length = null)
    {
        $this->values[$column] = array($column, &$variable, $type);

        return $this->stmt->bindParam($column, $variable, $type);
    }

    public function closeCursor()
    {
        return $this->stmt->closeCursor();
    }

    public function columnCount()
    {
        return $this->stmt->columnCount();
    }

    public function errorCode()
    {
        return $this->stmt->errorCode();
    }

    public function errorInfo()
    {
        return $this->stmt->errorInfo();
    }

    public function fetch($fetchStyle = PDO::FETCH_BOTH)
    {
        return $this->stmt->fetch($fetchStyle);
    }

    public function fetchAll($fetchStyle = PDO::FETCH_BOTH)
    {
        return $this->stmt->fetchAll($fetchStyle);
    }

    public function fetchColumn($columnIndex = 0)
    {
        return $this->stmt->fetchColumn($columnIndex);
    }

    public function rowCount()
    {
        return $this->stmt->rowCount();
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
        return $this->stmt;
    }
}
