<?php

namespace Illuminate\Security\Sensors;

use Illuminate\Http\Request;
use Illuminate\Security\IdsSensor;

class SqlInjectionSensor extends IdsSensor
{
    /**
     * The detection weight of this sensor.
     *
     * @var int
     */
    protected $weight = 5;

    /**
     * The description of this sensor.
     *
     * @var string
     */
    protected $description = 'Detected potential SQL injection attempt';

    /**
     * SQL injection attack patterns.
     *
     * @var array
     */
    protected $patterns = [
        '/\bUNION\s+ALL\s+SELECT\b/i',
        '/\bAND\s+\d+\s*=\s*\d+\b/i',
        '/\bOR\s+\d+\s*=\s*\d+\b/i',
        '/\bAND\s+[\'"]\s*=\s*[\'"](?:\s*--|\s*#|\s*\/\*)/i',
        '/\bOR\s+[\'"]\s*=\s*[\'"](?:\s*--|\s*#|\s*\/\*)/i',
        '/(?:;|\)|--)\s*EXEC\s+xp_/i',
        '/(?:;|\)|--)\s*DECLARE\s+/i',
        '/\/\*!(?:\d+)?\s?(?:SELECT|UPDATE|DELETE|INSERT|UNION|LOAD_FILE)/i',
        '/\bEXEC\s+\w+\s+(?:\s*@)/i',
        '/\bSELECT\s+.*?\bFROM\s+information_schema\./i',
        '/\bSELECT\s+@@version/i',
        '/\bSELECT\s.*?\bFROM\s.*?\bWHERE\s.*?\bIN\s+\(.*?\bSELECT\b/i',
        '/\bDROP\s+TABLE\b/i',
        '/\bTRUNCATE\s+TABLE\b/i',
        '/\bALTER\s+TABLE\b.*?\bDROP\b/i',
    ];

    /**
     * Detect if this sensor has identified a threat.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function detect(Request $request): bool
    {
        $params = $this->getAllParameters($request);

        foreach ($params as $param) {
            if (! is_string($param)) {
                continue;
            }

            foreach ($this->patterns as $pattern) {
                if (preg_match($pattern, $param)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get all request parameters.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function getAllParameters(Request $request): array
    {
        return array_merge(
            $request->query->all(),
            $request->request->all(),
            $request->cookies->all(),
            $request->headers->all(),
            $request->route()?->parameters() ?? []
        );
    }
}
