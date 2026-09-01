<?php

namespace Illuminate\Validation;

use Egulias\EmailValidator\Validation\DNSGetRecordWrapper;
use Egulias\EmailValidator\Validation\DNSRecords;

class FakeDnsGetRecordWrapper extends DNSGetRecordWrapper
{
    /**
     * Get a synthetic DNS record for the given host.
     *
     * @param  string  $host
     * @param  int  $type
     * @return \Egulias\EmailValidator\Validation\DNSRecords
     */
    public function getRecords(string $host, int $type): DNSRecords
    {
        return new DNSRecords([['type' => 'A']]);
    }
}
