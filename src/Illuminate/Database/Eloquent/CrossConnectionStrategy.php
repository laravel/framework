<?php

namespace Illuminate\Database\Eloquent;

/**
 * Strategy used by Eloquent to resolve relationship existence queries when the
 * parent and related models live on different database connections.
 */
enum CrossConnectionStrategy: string
{
    /**
     * Parent and related share the same connection. No cross-connection
     * handling is required and the existing correlated subquery is used.
     */
    case Same = 'same';

    /**
     * Related lives on a different database but on the same server (same
     * driver, host, and port). The existence subquery can stay on a single
     * PDO connection by qualifying the related table with its database name.
     */
    case Prefix = 'prefix';

    /**
     * Related lives on a different server or driver. The existence subquery
     * must be resolved by executing a separate query against the related
     * connection, then converting the result into a "where in" or "where not
     * in" clause on the parent.
     */
    case Resolve = 'resolve';
}
