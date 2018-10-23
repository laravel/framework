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
     * @param  String  type
     * @return void
     */
    get(type = "create")
    {
        let ids = [
            "CustomItemDescription",
            "CustomItemRoom",
            "CustomItemQuantity",
            "CustomItemWidth",
            "CustomItemHeight",
            "CustomItemDepth",
            "CustomItemCategory",
            "CustomItemPaymentBy",
        ], rules = {};
        // Check whether given type is "create".
        if (type == "create") {
            _.forEach(ids, (id) => {
                rules[id] = {
                    "required": true,
                };
            });

            // Return validation rules for type "create".
            return rules;
        }

        // Make validation rules of "update".
        _.forEach(ids, (id) => {
            rules[`Update${id}`] = {
                "required": true,
            };
        });

        // Return validation rules for type "update".
        return rules;
    }
}

export default ValidationRules;
