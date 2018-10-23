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
            },
            "Name": {
                "required": true,
                "maxlength": 100,
            },
            "Description": {
                "required": true,
                "sentence": true,
            },
        }
    }
}

export default ValidationRules;
