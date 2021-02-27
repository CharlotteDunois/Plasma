<?php
/**
 * Plasma Core component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/core/blob/master/LICENSE
*/

namespace Plasma;

use Evenement\EventEmitterInterface;
use React\Promise\PromiseInterface;

/**
 * This is the more advanced query result interface, which is event based.
 * That means, for `SELECT` statements a `data` event will be emitted for each row.
 * At the end of a query, a `end` event will be emitted to notify of the completion.
 * On error, it will emit an `error` event.
 */
interface StreamQueryResultInterface extends EventEmitterInterface, QueryResultInterface {
    /**
     * Buffers all rows and returns a promise which resolves with an instance of `QueryResultInterface`.
     * This method does not guarantee that all rows get returned, as the buffering depends on when this
     * method gets invoked. As such implementations may buffer rows directly from the start to ensure
     * all rows get returned. But users must not assume this behaviour is the case.
     * @return PromiseInterface
     */
    function all(): PromiseInterface;
}
