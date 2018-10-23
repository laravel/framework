import Notifier from "./Notifier";
import Translator from "./Translator";
import FormRequisites from "./FormRequisites";

/**
 * An abstract application class.
 *
 * Initializes notifier, translator and other form requisites. These
 * instances are served for the classes that extends this class.
 */
class Application
{
    /**
     * Get notifier for the application.
     *
     * @var Notifier
     */
    notifier = new Notifier;

    /**
     * Get translator for the application.
     *
     * @var Translator
     */
    translator = new Translator;

    /**
     * Get form prerequisites for the application.
     *
     * @var FormRequisites
     */
    formRequisites = new FormRequisites(this.notifier, this.translator);

    /**
     * Create a new instance of Application.
     *
     * @return void
     */
    constructor()
    {
        // Setup csrfToken for ajax request calls.
        this.formRequisites.csrfToken();
    }
}

export default Application;
