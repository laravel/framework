<?php

namespace Illuminate\Http;

use Illuminate\Foundation\Http\FormRequest;

abstract class DataRequest extends FormRequest
{
    protected function passedValidation(): void
    { 
        foreach ($this->validated() as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }
}
