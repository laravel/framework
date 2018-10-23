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
            "Enquiry": {
                "required": true,
            },
            "Rooms[]": {
                "required": true,
            },
        };
    }
}

export default ValidationRules;
