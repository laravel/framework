/**
 * Make validation rules for ReplyIdeaRequest.
 */
class ReplyIdeaRequestRules
{
    /**
     * Get validation rules.
     *
     * @return Object
     */
    get()
    {
        return {
            Reply: {
                required: true,
                minlength: 3,
                CheckConsecutiveSpaces: true
            },
            "Attachments[]":{
                checkPerFileSizeInMultipleFiles: true
            }
        };
    }
}

export default ReplyIdeaRequestRules;
