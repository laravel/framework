/**
 * Make validation messages.
 *
 * Validation messages for CreateVendorRequest.
 */
class ValidationMessages
{
    /**
     * Initialize vendor form validations.
     *
     * @return void
     */
    get(translator)
    {
        return {
            "Enquiry": {
                "required": translator.trans('validation.required', [
                    {
                        "replace": "attribute",
                        "with": "Enquiry"
                    }
                ]),
            },
            "Rooms[]": {
                "required": translator.trans('validation.required', [
                    {
                        "replace": "attribute",
                        "with": "Rooms",
                    }
                ]),
            },
        };
    }
}

export default ValidationMessages;
