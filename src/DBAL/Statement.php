<?php

namespace BsbDoctrineReconnect\DBAL;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use IteratorAggregate;
use PDO;

/**
 * Class Statement
 *
 * @package BsbDoctrineReconnect\DBAL
 */
class Statement implements IteratorAggregate, DriverStatement
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
    private $values = [];

    /**
     * @var array binding parameters
     */
    private $params = [];

    /**
     * @param            string $sql
     * @param Connection        $conn
     */
    public function __construct($sql, Connection $conn)
    {
        $this->sql  = $sql;
        $this->conn = $conn;

        $this->createStatement();
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * @param null $params
     * @return bool|null
     * @throws DBALException
     */
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

    /**
     * @param mixed $param
     * @param mixed $value
     * @param null  $type
     * @return bool
     */
    public function bindValue($param, $value, $type = null)
    {
        $this->values[$param] = [$param, $value, $type];

        return $this->stmt->bindValue($param, $value, $type);
    }

    /**
     * @param mixed    $column
     * @param mixed    $variable
     * @param int|null $type
     * @param null     $length
     * @return bool
     */
    public function bindParam($column, &$variable, $type = PDO::PARAM_STR, $length = null)
    {
        $this->values[$column] = [$column, &$variable, $type];

        return $this->stmt->bindParam($column, $variable, $type);
    }

    /**
     * @return bool
     */
    public function closeCursor()
    {
        return $this->stmt->closeCursor();
    }

    /**
     * @return int
     */
    public function columnCount()
    {
        return $this->stmt->columnCount();
    }

    /**
     * @return string
     */
    public function errorCode()
    {
        return $this->stmt->errorCode();
    }

    /**
     * @return array
     */
    public function errorInfo()
    {
        return $this->stmt->errorInfo();
    }

    /**
     * @param int|null $fetchStyle
     * @return mixed
     */
    public function fetch($fetchStyle = PDO::FETCH_BOTH)
    {
        return $this->stmt->fetch($fetchStyle);
    }

    /**
     * @param int|null $fetchStyle
     * @return array
     */
    public function fetchAll($fetchStyle = PDO::FETCH_BOTH)
    {
        return $this->stmt->fetchAll($fetchStyle);
    }

    /**
     * @param int $columnIndex
     * @return mixed
     */
    public function fetchColumn($columnIndex = 0)
    {
        return $this->stmt->fetchColumn($columnIndex);
    }

    /**
     * @return int
     */
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
