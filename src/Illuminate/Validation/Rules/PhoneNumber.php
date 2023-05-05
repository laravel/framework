<?php

namespace Illuminate\Validation\Rules;

use Exception;

class PhoneNumber
{
    /**
     * Regular expressions for phone numbers for different countries.
     * @var array
     */
    public const PHONES_REGEXES = [
        /**
         * AFRICA COUNTRY PHONE NUMBER
         */
        "BJ" => '/^(\+229|00229|229)?\d{8}$/', //Benin
        "BF" => '/^(\+226|00226|226)?\d{8}$/', //Burkina Faso
        "CV" => '/^(\+238|00238|238)?\d{7}$/', //Cape Verde
        "CI" => '/^(\+225|00225|225)?\d{8}$/', //Ivory Coast
        "GM" => '/^(\+220|00220|220)?\d{7,8}$/', //Gambia
        "GH" => '/^(\+233|00233|233)?\d{9}$/', //Ghana
        "GN" => '/^(\+224|00224|224)?\d{8}$/', //Guinea
        "GW" => '/^(\+245|00245|245)?\d{7,8}$/', //Guinea-Bissau
        "LR" => '/^(\+231|00231|231)?\d{7,8}$/', //Liberia
        "ML" => '/^(\+223|00223|223)?\d{8}$/', //Mali
        "MR" => '/^(\+222|00222|222)?\d{8}$/', //Mauritania
        "NE" => '/^(\+227|00227|227)?\d{8}$/', //Niger
        "NG" => '/^(\+234|00234|234)?\d{10}$/', //Nigeria
        "SN" => '/^(\+221|00221|221)?\d{9}$/', //Senegal
        "SL" => '/^(\+232|00232|232)?\d{8,9}$/', //Sierra Leone
        "TG" => '/^(\+228|00228|228)?\d{8}$/', //Togo
        /**
         * AMERICA COUNTRIES PHONE NUMBER
         */
        "US" => '/^(\+1|001)?\d{10}$/' //United States
        //The list must be completed
    ];

    protected $regex = '';

    public function __construct(protected string $country_code, protected string $value)
    {
        $this->setRegex($country_code);
    }
    /**
     * Check phone number is valid.
     *
     * @return bool
     */
    public function isValid()
    {
        return preg_match(
            $this->regex,
            str_replace(array('-', ' ', '.'), '', $this->value)
        );
    }


    private function setRegex(string $country_code)
    {
        if (!array_key_exists($country_code, PhoneNumber::PHONES_REGEXES)) {
            throw new Exception("No validation rule defined for {$country_code} country", 1);
        }
        $this->regex = PhoneNumber::PHONES_REGEXES[$country_code];
    }
}
