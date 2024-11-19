<?php

namespace Illuminate\Foundation\Support;

class LogParser
{
    /**
     * Extracts the request port from a log line.
     *
     * @param  string  $line
     * @return int
     *
     * @throws \InvalidArgumentException
     */
    public static function extractRequestPort(string $line)
    {
        // Updated regex to match the log line with optional datetime prefix (example: [Wed Nov 15 2024     10:12:45]) and port number.
        preg_match('/(\[\w+\s\w+\s\d+\s[\d:]+\s\d{4}\]\s)?:(\d+)\s(?:(?:\w+$)|(?:\[.*))/', $line, $matches);

        // If no match for the port number found, an exception is thrown with a message containing the problematic line.
        if (! isset($matches[2])) {
            throw new \InvalidArgumentException("Failed to extract the request port. Ensure the log line contains a valid port: {$line}");
        }

        return (int) $matches[2];
    }
}
