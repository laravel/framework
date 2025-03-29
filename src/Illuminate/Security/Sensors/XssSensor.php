<?php

namespace Illuminate\Security\Sensors;

use Illuminate\Http\Request;
use Illuminate\Security\IdsSensor;

class XssSensor extends IdsSensor
{
    /**
     * The detection weight of this sensor.
     *
     * @var int
     */
    protected $weight = 4;

    /**
     * The description of this sensor.
     *
     * @var string
     */
    protected $description = 'Detected potential XSS attack';

    /**
     * XSS attack patterns.
     *
     * @var array
     */
    protected $patterns = [
        '/<script.*?>.*?<\/script>/is',
        '/<iframe.*?>/is',
        '/<embed.*?>/is',
        '/<object.*?>/is',
        '/javascript:[^"]*/is',
        '/vbscript:[^"]*/is',
        '/on(?:load|error|mouseover|click|dblclick|mousedown|mouseup|mouseout|keydown|keypress|keyup|change|blur|focus|submit|reset|select)\s*=\s*["\'][^"]*["\']/',
        '/(?:document|window|eval|setTimeout|setInterval|Function)(?:\.|\[)[a-z]*(?:\.|\[)[a-z]*(?:\(|\)?:\s*["\'])/is',
        '/expression\s*\(.*?\)/',
        '/data:[^;]*;base64/',
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
            if (!is_string($param)) {
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