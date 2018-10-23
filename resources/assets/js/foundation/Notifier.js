/**
 * Notifies alerts and overlays.
 *
 * Serves notifications for the application. Clears overlays
 * with success or error messages, populates form errors.
 */
class Notifier
{
    /**
     * No. of milli-seconds for alert notification timeout.
     *
     * @var Integer
     */
    notificationTimeout = 10000;

    /**
     * No. of milli-seconds for overlay notification timeout.
     *
     * @var Integer
     */
    overlayNotificationTimeout = 2000;

    /**
     * Alert notification timeout id.
     *
     * @var Integer|undefined
     */
    notificationTimeoutId = undefined;

    /**
     * Template for creating an alert in the notification area.
     *
     * @var String
     */
    alertTemplate = `
        <div class="alert alert-dismissible no-border hidden">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <p class="body"></p>
        </div>
    `;

    /**
     * Clear alert notification of the given area.
     *
     * @param  Selector  selector
     * @return void
     */
    clearAlertNotification(selector)
    {
        $(selector + " .alert").fadeOut("slow", function () {
            $(selector).addClass('hidden');
        });
    }

    /**
     * Notify alerts in the given notification area.
     *
     * @param  Selector  selector
     * @param  Object  response
     * @return void
     */
    notify(selector, response)
    {
        let notificationArea = $(selector);
        if (notificationArea.children('.alert').length === 0) {
            notificationArea.html(this.alertTemplate);
        }
        notificationArea.removeClass('hidden');

        let alertNode = notificationArea.children('.alert');
        if (this.notificationTimeoutId) {
            clearTimeout(this.notificationTimeoutId);
        }

        if (response.status == "success") {
            alertNode.removeAttr('class').removeAttr('style').addClass('alert alert-dismissible no-border alert-' + response.message.type).find("p.body").html(response.message.body);
            this.notificationTimeoutId = setTimeout(function () {
                this.clearAlertNotification(selector);
            }.bind(this), this.notificationTimeout);
        } else {
            alertNode.removeAttr('class').removeAttr('style').addClass('alert alert-danger no-border').find("p.body").html(response.message).parent().find("button.close").remove();
        }
    }

    /**
     * Populates errors for the given form.
     *
     * @param  Selector  selector
     * @param  Object  errors
     * @return void
     */
    populateFormErrors(selector, errors)
    {
        let validator = $(selector).data('validator');
        for (let elementName in errors) {
            let errorObject = {},
                previousValue = $("#" + elementName).data("previousValue") ? $("#" + elementName).data("previousValue") : {};
            previousValue.valid = false;
            previousValue.message = errors[elementName][0];
            $("#" + elementName).data("previousValue", previousValue);
            errorObject[elementName] = errors[elementName][0];
            validator.showErrors(errorObject);
        }
    }

    /**
     * Clear overlay notification of the given area.
     *
     * @param  Selector  selector
     * @return void
     */
    clearOverlayNotification(selector)
    {
        $(selector).fadeOut("slow", function () {
            $(this).addClass('hidden').removeAttr("style").find('.overlay-notification-icon').removeAttr('class').addClass('large loader').siblings('.loader-text').html('Processing...');
        });
    }

    /**
     * Notifies message for the given overlay.
     *
     * @param  Selector  selector
     * @param  String  message
     * @param  Boolean  isSuccess
     * @param  Boolean  clearNotification
     * @return void
     */
    notifiyOverlay(selector, message, isSuccess = true, clearNotification = false)
    {
        let overlay = $(selector);
        if (isSuccess) {
            overlay.removeClass('hidden').find('div.loader-text').html(message).parent().find('div.loader').removeClass('large loader').addClass('fa fa-check-circle text-success overlay-notification-icon');
        } else {
            overlay.removeClass('hidden').find('div.loader-text').html(message).parent().find('div.loader').removeClass('large loader').addClass('fa fa-exclamation-circle text-danger overlay-notification-icon');
        }

        if (clearNotification) {
            setTimeout(function () {
                this.clearOverlayNotification(selector);
            }.bind(this), this.overlayNotificationTimeout);
        }
    }

    /**
     * Notifies success message for the given overlay and clears notification after overlay timeout.
     *
     * @param  Selector  selector
     * @param  String  message
     * @return void
     */
    notifiyOverlaySuccessWithClearance(selector, message)
    {
        this.notifiyOverlay(selector, message, true, true);
    }

    /**
     * Notifies error message for the given overlay and clears notification after overlay timeout.
     *
     * @param  Selector  selector
     * @param  String  message
     * @return void
     */
    notifiyOverlayErrorWithClearance(selector, message)
    {
        this.notifiyOverlay(selector, message, false, true);
    }
}

export default Notifier;
