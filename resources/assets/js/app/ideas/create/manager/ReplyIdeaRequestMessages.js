/**
 * Make validation messages for ReplyIdeaRequest.
 */
class ReplyIdeaRequestMessages
{
    /**
     * Get validation messages.
     *
     * @return Object
     */
    get(translator)
    {
        return {
            Reply: {
                required: translator.trans('validation.required', [
                    {
                        replace: "attribute",
                        with: "Reply"
                    }
                ]),
            },
            "Attachments[]": {
                checkPerFileSizeInMultipleFiles: "Max Upload file size is 2MB per file."
            },
            Status: {
                required: translator.trans('validation.required', [
                    {
                        replace: "attribute",
                        with: "Status"
                    }
                ]),
            },
        };
    }
}

export default ReplyIdeaRequestMessages;
