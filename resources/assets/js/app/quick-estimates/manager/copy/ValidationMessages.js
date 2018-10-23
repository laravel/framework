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
     * @param  \Core\Translator  translator
     * @param  String  type
     * @return void
     */
    get(translator, type = "create")
    {
        let ids = [
            "CustomItemDescription",
            "CustomItemRoom",
            "CustomItemUnit",
            "CustomItemQuantity",
            "CustomItemWidth",
            "CustomItemHeight",
            "CustomItemDepth",
            "CustomItemCategory",
        ], messages = {};
        // Check whether given type is "create".
        if (type == "create") {
            _.forEach(ids, (id) => {
                messages[id] = {
                    "required": translator.trans("validation.required", [
                        {
                            "replace": "attribute",
                            "with": id.split(/CustomItem/)[1],
                        },
                    ]),
                };
            });
            // Create custom message for create item payment by.
            messages["CustomItemPaymentBy"] = {
                "required": translator.trans("validation.required", [
                    {
                        "replace": "attribute",
                        "with": "Payment by",
                    },
                ]),
            };

            // Return validation messages for type "create".
            return messages;
        }

        // Make validation messages of "update".
        _.forEach(ids, (id) => {
            messages[`Update${id}`] = {
                "required": translator.trans("validation.required", [
                    {
                        "replace": "attribute",
                        "with": id.split(/CustomItem/)[1],
                    },
                ]),
            };
        });
        // Create custom message for update item payment by.
        messages["UpdateCustomItemPaymentBy"] = {
            "required": translator.trans("validation.required", [
                {
                    "replace": "attribute",
                    "with": "Payment by",
                },
            ]),
        };

        // Return validation messages for type "update".
        return messages;
    }
}

export default ValidationMessages;
