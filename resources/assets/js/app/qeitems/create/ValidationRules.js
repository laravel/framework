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
            "Description": {
                "required": true,
            },
            "Rooms[]": {
                "required": () => {
                    return $("#Type").val() == "QE";
                },
            },
            "QEItems[]": {
                "required": () => {
                    return $("#Type").val() == "DE";
                },
            },
            "Unit": {
                "required": true,
            },
            "Quantity": {
                "required": true,
            },
            "Width": {
                "required": true,
            },
            "Height": {
                "required": true,
            },
            "Depth": {
                "required": true,
            },
            "RatecardItem": {
                "required": true,
            },
            "Category": {
                "required": true,
            },
            "References[]":{
                checkMultipleFilesExtensions: true,
                checkPerFileSizeInMultipleFiles: true
            },
            "NewComment": {
                "required": function () {
                    return $("#Comments").val().slice(-1)[0] === "addnew";
                }
            },
            "Type": {
                "required": true,
            }
        };
    }
}

export default ValidationRules;
