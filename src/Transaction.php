<?php
/**
 * Plasma Core component
 * Copyright 2018 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/core/blob/master/LICENSE
*/

namespace Plasma;

class Transaction implements TransactionInterface {
    /**
     * @var \Plasma\ClientInterface
     */
    protected $client;
    
    /**
     * @var \Plasma\DriverInterface|null
     */
    protected $driver;
    
    /**
     * @var int
     */
    protected $isolation;
    
    /**
     * Creates a client with the specified factory and options.
     * @param \Plasma\ClientInterface  $client
     * @param \Plasma\DriverInterface  $driver
     * @param int                      $isolation
     * @throws \Plasma\Exception  Thrown if the transaction isolation level is invalid.
     */
    function __construct(\Plasma\ClientInterface $client, \Plasma\DriverInterface $driver, int $isolation) {
        switch($isolation) {
            case \Plasma\TransactionInterface::ISOLATION_UNCOMMITTED:
            case \Plasma\TransactionInterface::ISOLATION_COMMITTED:
            case \Plasma\TransactionInterface::ISOLATION_REPEATABLE:
            case \Plasma\TransactionInterface::ISOLATION_SERIALIZABLE:
                // Valid isolation level
            break;
            default:
                throw new \Plasma\Exception('Invalid isolation level given');
            break;
        }
        
        $this->client = $client;
        $this->driver = $driver;
        $this->isolation = $isolation;
    }
    
    /**
     * Destructor. Implicit rollback and automatically checks the connection back into the client on deallocation.
     */
    function __destruct() {
        if($this->driver !== null && $this->driver->getConnectionState() === \Plasma\DriverInterface::CONNECTION_OK) {
            $this->rollback()->then(null, function () {
                // Error during implicit rollback, close the session
                $this->driver->close();
            });
        }
    }
    
    /**
     * Get the isolation level for this transaction.
     * @return int
     */
    function getIsolationLevel(): int {
        return $this->isolation;
    }
    
    /**
     * Whether the transaction is still active, or has been committed/rolled back.
     * @return bool
     */
    function isActive(): bool {
        return ($this->driver !== null);
    }
    
    /**
     * Executes a plain query. Resolves with a `QueryResult` instance.
     * @param string  $query
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\TransactionException  Thrown if the transaction has been committed or rolled back.
     * @see \Plasma\QueryResultInterface
     */
    function query(string $query): \React\Promise\PromiseInterface {
        if($this->driver === null) {
            throw new \Plasma\TransactionException('Transaction has been committed or rolled back');
        }
        
        return $this->driver->query($query);
    }
    
    /**
     * Prepares a query. Resolves with a `StatementInterface` instance.
     * @param string  $query
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\TransactionException  Thrown if the transaction has been committed or rolled back.
     * @see \Plasma\StatementInterface
     */
    function prepare(string $query): \React\Promise\PromiseInterface {
        if($this->driver === null) {
            throw new \Plasma\TransactionException('Transaction has been committed or rolled back');
        }
        
        return $this->driver->prepare($query);
    }
    
    /**
     * Quotes the string for use in the query.
     * @param string  $str
     * @return string
     * @throws \LogicException  Thrown if the driver does not support quoting.
     */
    function quote(string $str): string {
        return $this->driver->quote($str);
    }
    
    /**
     * Commits the changes.
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\TransactionException  Thrown if the transaction has been committed or rolled back.
     */
    function commit(): \React\Promise\PromiseInterface {
        return $this->driver->query('COMMIT')->then(function () {
            $this->driver->endTransaction();
            $this->client->checkinConnection($this->driver);
            $this->driver = null;
        });
    }
    
    /**
     * Rolls back the changes.
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\TransactionException  Thrown if the transaction has been committed or rolled back.
     */
    function rollback(): \React\Promise\PromiseInterface {
        return $this->driver->query('ROLLBACK')->then(function () {
            $this->driver->endTransaction();
            $this->client->checkinConnection($this->driver);
            $this->driver = null;
        });
    }
    
    /**
     * Creates a savepoint with the given identifier.
     * @param string  $identifier
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\TransactionException  Thrown if the transaction has been committed or rolled back.
     */
    function createSavepoint(string $identifier): \React\Promise\PromiseInterface {
        return $this->query('SAVEPOINT '.$this->driver->quote($identifier));
    }
    
    /**
     * Rolls back to the savepoint with the given identifier.
     * @param string  $identifier
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\TransactionException  Thrown if the transaction has been committed or rolled back.
     */
    function rollbackTo(string $identifier): \React\Promise\PromiseInterface {
        return $this->query('ROLLBACK TO '.$this->driver->quote($identifier));
    }
    
    /**
     * Releases the savepoint with the given identifier.
     * @param string  $identifier
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\TransactionException  Thrown if the transaction has been committed or rolled back.
     */
    function releaseSavepoint(string $identifier): \React\Promise\PromiseInterface {
        return $this->query('RELEASE SAVEPOINT '.$this->driver->quote($identifier));
    }
}
