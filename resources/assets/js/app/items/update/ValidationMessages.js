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
            "Code": {
                "required": translator.trans('validation.required', [
                    {
                        "replace": "attribute",
                        "with": "Code",
                    }
                ]),
                "maxlength": translator.trans('validation.maxlength', [
                    {
                        "replace": "attribute",
                        "with": "Code",
                    }
                ]),
                "Validate_Blank_Spaces": translator.trans('validation.required', [
                    {
                        "replace": "attribute",
                        "with": "Code",
                    }
                ]),
            },
            "Name": {
                "required": translator.trans('validation.required', [
                    {
                        "replace": "attribute",
                        "with": "Name"
                    }
                ]),
                "maxlength": translator.trans('validation.maxlength', [
                    {
                        "replace": "attribute",
                        "with": "Name",
                    }
                ]),
                "Validate_Blank_Spaces": translator.trans('validation.required', [
                    {
                        "replace": "attribute",
                        "with": "Name",
                    }
                ]),
            },
            "Description": {
                "required": translator.trans('validation.required', [
                    {
                        "replace": "attribute",
                        "with": "Description"
                    }
                ]),
                "Validate_Blank_Spaces": translator.trans('validation.required', [
                    {
                        "replace": "attribute",
                        "with": "Description",
                    }
                ]),
            },
        };
    }
}

export default ValidationMessages;
