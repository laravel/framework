/**
 * Make validation rules for CreateIdeaRequest.
 */
class CreateIdeaRequestRules
{
    /**
     * Get validation rules.
     *
     * @return Object
     */
    get()
    {
        return {
            Project: {
                required: true
            },
            Room: {
                required: true
            },
            DesignItem: {
                required: true
            },
            Idea: {
                required: true,
                minlength: 3,
                CheckConsecutiveSpaces: true
            },
        };
    }
}

export default CreateIdeaRequestRules;
