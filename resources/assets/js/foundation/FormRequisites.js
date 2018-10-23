/**
 * Various types of helpers for forms.
 *
 * Initializes form helpers like setting up CSRF token
 * for ajax calls, prepare form before ajax submission,
 * parsing JSON and logging exceptions to browser's console
 * hiding and showing overlays and alerts.
 */
class FormRequisites
{
    /**
     * Get notifier for the form requisites.
     *
     * @var Notifier|undefined
     */
    notifier = undefined;

    /**
     * Get translator for the form requisites.
     *
     * @var Translator|undefined
     */
    translator = undefined;

    /**
     * Create a new instance of FormRequisites.
     *
     * @param  Notifier  notifier
     * @param  Translator  translator
     * @return void
     */
    constructor(notifier, translator)
    {
        this.notifier = notifier;
        this.translator = translator;
    }

    /**
     * Setup CSRF token for ajax request calls.
     *
     * @return void
     */
    csrfToken()
    {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    }

    /**
     * Prepare form for an ajax request call.
     *
     * @param  Selector  selector
     * @param  String  message
     * @return void
     */
    prepareFormForAjax(selector, message = "")
    {
        if (message.length > 0) {
            $(selector).removeClass('hidden').removeAttr('style').children('div.loader-text').html(message + '...');
        } else {
            $(selector).removeClass('hidden').removeAttr('style');
        }
        $(":focus").trigger('blur');
        $(".alert").addClass('hidden');
    }

    /**
     * Get parsed JSON from the given string.
     *
     * @param  Selector  selector
     * @param  String  responseText
     * @return Object|Boolean
     */
    parseJson(selector, responseText)
    {
        try {
            return JSON.parse(responseText);
        } catch (exception) {
            this.logException(exception);
            this.notifier.notify(selector, {
                status: "error",
                message: this.translator.trans('system.failure')
            });
            return false;
        }
    }

    /**
     * Log an exception to the browser's console.
     *
     * @param  Object  exception
     * @return void
     */
    logException(exception)
    {
        console.error('Exception thrown: ', exception.message);
        console.error(exception);
    }

    /**
     * Show overlay message for the given selector.
     *
     * @param  Selector  selector
     * @param  String  message
     * @return jQuery
     */
    showOverlay(selector, message)
    {
        return $(selector).removeClass('hidden').find(".loader-text").html(message + "...");
    }

    /**
     * Hide overlay for the given selector.
     *
     * @param  Selector  selector
     * @return jQuery
     */
    hideOverlay(selector)
    {
        return $(selector).addClass('hidden');
    }

    /**
     * Hide alert for the given selector.
     *
     * @param  Selector  selector
     * @return void
     */
    hideAlert(selector)
    {
        return $(selector).addClass('hidden');
    }
}

export default FormRequisites;
