/**
 * Make validation rules.
 *
 * Validation rules for CreateVendorRequest.
 */
class ValidationRules
{
    /**
     * Initialize vendor form validations.
     *
     * @return void
     */
    get()
    {
        return {
            "Code": {
                "required": true,
                "maxlength": 100,
                "Validate_Blank_Spaces": true,
                "Minimum_Characters_Without_Spaces": true,
            },
            "Name": {
                "required": true,
                "maxlength": 255,
                "Validate_Blank_Spaces": true,
                "Minimum_Characters_Without_Spaces": true,
            },
            "Description": {
                "required": true,
                "Validate_Blank_Spaces": true,
                "Minimum_Characters_Without_Spaces": true,
            },
            "Unit": {
                "required": true,
            },
        }
    }
}

export default ValidationRules;
