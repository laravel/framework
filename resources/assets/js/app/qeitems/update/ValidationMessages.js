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
            "Description": {
                "required": translator.trans('validation.required', [
                    {
                        "replace": "attribute",
                        "with": "Description"
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
            "QEItems[]": {
                "required": translator.trans('validation.required', [
                    {
                        "replace": "attribute",
                        "with": "QE Items",
                    }
                ]),
            },
            "Unit": {
                "required": translator.trans('validation.required', [
                    {
                        "replace": "attribute",
                        "with": "Unit",
                    }
                ]),
            },
            "Quantity": {
                "required": translator.trans('validation.required', [
                    {
                        "replace": "attribute",
                        "with": "Quantity",
                    }
                ]),
            },
            "Width": {
                "required": translator.trans('validation.required', [
                    {
                        "replace": "attribute",
                        "with": "Width",
                    }
                ]),
            },
            "Height": {
                "required": translator.trans('validation.required', [
                    {
                        "replace": "attribute",
                        "with": "Height",
                    }
                ]),
            },
            "Depth": {
                "required": translator.trans('validation.required', [
                    {
                        "replace": "attribute",
                        "with": "Depth",
                    }
                ]),
            },
            "RatecardItem": {
                "required": translator.trans('validation.required', [
                    {
                        "replace": "attribute",
                        "with": "Ratecard Item",
                    }
                ]),
            },
            "Category": {
                "required": translator.trans('validation.required', [
                    {
                        "replace": "attribute",
                        "with": "Category",
                    }
                ]),
            },
            "NewComment": {
                "required": translator.trans('validation.required', [
                    {
                        "replace": "attribute",
                        "with": "New Comment",
                    }
                ]),
            },
            "Type": {
                "required": translator.trans('validation.required', [
                    {
                        "replace": "attribute",
                        "with": "Type"
                    }
                ]),
            }
        };
    }
}

export default ValidationMessages;
