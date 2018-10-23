/**
 * Make validation messages for CreateIdeaRequest.
 */
class CreateIdeaRequestMessages
{
    /**
     * Get validation messages.
     *
     * @return Object
     */
    get(translator)
    {
        return {
            Customer: {
                required: translator.trans('validation.required', [
                    {
                        replace: "attribute",
                        with: "Customer"
                    }
                ]),
            },
            Project: {
                required: translator.trans('validation.required', [
                    {
                        replace: "attribute",
                        with: "Project"
                    }
                ]),
            },
            Room: {
                required: translator.trans('validation.required', [
                    {
                        replace: "attribute",
                        with: "Room"
                    }
                ]),
            },
            DesignItem: {
                required: translator.trans('validation.required', [
                    {
                        replace: "attribute",
                        with: "Design Item"
                    }
                ]),
            },
            Idea: {
                required: translator.trans('validation.required', [
                    {
                        replace: "attribute",
                        with: "Idea"
                    }
                ]),
            },
        };
    }
}

export default CreateIdeaRequestMessages;
