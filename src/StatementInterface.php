<?php
/**
 * Plasma Core component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/core/blob/master/LICENSE
*/

namespace Plasma;

use React\Promise\PromiseInterface;

/**
 * Represents any prepared statement.
 */
interface StatementInterface {
    /**
     * Get the driver-dependent ID of this statement.
     * The return type can be of ANY type, as the ID depends on the driver and DBMS.
     * @return mixed
     */
    function getID();
    
    /**
     * Get the prepared query.
     * @return string
     */
    function getQuery(): string;
    
    /**
     * Whether the statement has been closed.
     * @return bool
     */
    function isClosed(): bool;
    
    /**
     * Closes the prepared statement and frees the associated resources on the server.
     * Closing a statement more than once SHOULD have no effect.
     * @return PromiseInterface
     */
    function close(): PromiseInterface;
    
    /**
     * Executes the prepared statement. Resolves with a `QueryResult` instance.
     * @param array  $params
     * @return PromiseInterface
     * @throws Exception
     * @see \Plasma\QueryResultInterface
     */
    function execute(array $params = array()): PromiseInterface;
    
    /**
     * Runs the given querybuilder on an underlying driver instance.
     * The driver CAN throw an exception if the given querybuilder is not supported.
     * An example would be a SQL querybuilder and a Cassandra driver.
     * @param QueryBuilderInterface  $query
     * @return PromiseInterface
     * @throws Exception
     */
    function runQuery(QueryBuilderInterface $query): PromiseInterface;
}
