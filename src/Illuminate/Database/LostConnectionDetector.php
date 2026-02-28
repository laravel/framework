<?php

namespace Illuminate\Database;

use Illuminate\Contracts\Database\LostConnectionDetector as LostConnectionDetectorContract;
use Illuminate\Support\Str;
use Throwable;

class LostConnectionDetector implements LostConnectionDetectorContract
{
    /**
     * Determine if the given exception was caused by a lost connection.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    public function causedByLostConnection(Throwable $e): bool
    {
        $message = $e->getMessage();

        return Str::contains($message, [
            'server has gone away',
            'Server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
            'Transaction() on null',
            'child connection forced to terminate due to client_idle_limit',
            'query_wait_timeout',
            'reset by peer',
            'Physical connection is not usable',
            'TCP Provider: Error code 0x68',
            'ORA-03114',
            'Packets out of order. Expected',
            'Adaptive Server connection failed',
            'Communication link failure',
            'connection is no longer usable',
            'Login timeout expired',
            'SQLSTATE[HY000] [2002]',
            'running with the --read-only option so it cannot execute this statement',
            'The connection is broken and recovery is not possible. The connection is marked by the client driver as unrecoverable. No attempt was made to restore the connection.',
            'SQLSTATE[HY000]: General error: 7',
            'SSL error: unexpected eof',
            'SSL: Connection timed out',
            'SQLSTATE[HY000]: General error: 1105 The last transaction was aborted due to Seamless Scaling. Please retry.',
            'Temporary failure in name resolution',
            'SQLSTATE[08S01]: Communication link failure',
            'SQLSTATE[08006] [7]',
            'The client was disconnected by the server because of inactivity. See wait_timeout and interactive_timeout for configuring this behavior.',
            'TCP Provider: Error code 0x274C',
            'SSL: Operation timed out',
            'Reason: Server is in script upgrade mode. Only administrator can connect at this time.',
            'Unknown $curl_error_code: 77',
            'SSL: Handshake timed out',
            'SSL error: sslv3 alert unexpected message',
            'unrecognized SSL error code:',
            'SQLSTATE[HY000] [1045]',
            'SQLSTATE[HY000]: General error: 3989',
            'went away',
            'No such file or directory',
            'server is shutting down',
            'failed to connect to',
            'Channel connection is closed',
            'Connection lost',
            'Broken pipe',
            'SQLSTATE[25006]: Read only sql transaction: 7',
            'vtgate connection error: no healthy endpoints',
            'primary is not serving, there may be a reparent operation in progress',
            'current keyspace is being resharded',
            'no healthy tablet available',
            'transaction pool connection limit exceeded',
            'SSL operation failed with code 5',
        ]);
    }
}
