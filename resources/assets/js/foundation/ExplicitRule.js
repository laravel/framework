/**
 * An abstract explicit validation rule class.
 *
 * Overrides default validation methods with custom
 * validation methods to have more control for developer.
 */
class ExplicitRule
{
    /**
     * Overrides a default validation methods with custom methods.
     *
     * @return void
     */
    add()
    {
        let that = this;

        $.validator.methods[this.name] = function (value, element, param) {
            return that.passes(value, element, param, this);
        };
    }
}

export default ExplicitRule;
