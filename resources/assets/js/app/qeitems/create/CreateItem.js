import FileChooser from "support/FileChooser";
import Application from "foundation/Application";
import CreateItemRequest from "./CreateItemRequest";
import { applyKeyboardNavigationPatch } from "support/Select2Patches";

/**
 * Vendor class for controlling the password tab.
 *
 * Initializes essentials like form validations for password page.
 */
class CreateItem extends Application
{
    /**
     * Initialize item page components.
     *
     * @return void
     */
    execute()
    {
        // Initialize form validations.
        let request = new CreateItemRequest();
        request.init(this.notifier, this.translator, this.formRequisites);
        $(request.formSelector).validate(request.getOptions());

        // Make custom file input.
        let fileChooser = new FileChooser();
        fileChooser.make("#References", "Upload");

        // Initialize select2 for select elements.
        this.initializeSelect2([
            "#Unit",
            "#Rooms",
            "#Category",
            "#RatecardItem",
            "#QEItems",
        ]);

        $("#Type").on("change", () => {
            if ($("#Type").val() == "DE") {
                $("#QEItemsBlock").removeClass("hidden");
                $("#RoomsBlock").addClass("hidden");
            } else {
                $("#QEItemsBlock").addClass("hidden");
                $("#RoomsBlock").removeClass("hidden");
            }
        });

        // Initialize comment select2 and handle mandatory field.
        this.initializeCommentSelect2();
        this.showOrHideMandatory();

        // On form reset, clear form customized fields.
        this.clearCustomInputsOnFormReset(request.formSelector);
    }

    /**
     * Initialize select2 for given selectors.
     *
     * @param  Array  selectors
     * @return void
     */
    initializeSelect2(selectors)
    {
        for (let selector of selectors) {
            let node = $(selector);
            node.select2().on("select2:select", () => {
                return node.val().length !== 0 && node.valid();
            });

            applyKeyboardNavigationPatch(node);
        }
    }

    /**
     * Initialize comment select2.
     *
     * @return void
     */
    initializeCommentSelect2()
    {
        $("#Comments").select2().on("select2:select", this.showOrHideNewComment).on("change", this.showOrHideNewComment);
    }

    /**
     * Show or hide new comment element.
     *
     * @return void
     */
    showOrHideNewComment()
    {
        let comments = $("#Comments").val();
        if (comments !== null && comments.slice(-1)[0] === "addnew") {
            return $("#NewComment").closest("div.col-md-4").removeClass("hidden");
        }
        return $("#NewComment").val("").closest("div.col-md-4").addClass("hidden");
    }
    
    /**
     * Show or hide mandatory field block.
     *
     * @return void
     */
    showOrHideMandatory()
    {
        let mandatoryBlockNode = $("#MandatoryBlock");
        $("input[name=Preselected]").on("change", () => {
            return $("#PreselectedYes").is(":checked") ? mandatoryBlockNode.removeClass("hidden") : mandatoryBlockNode.addClass("hidden");
        });
    }

    /**
     * Clear customized fields on form reset.
     *
     * @param  Selector  formSelector
     * @return void
     */
    clearCustomInputsOnFormReset(formSelector)
    {console.log("in");
        $(formSelector).on("reset", () => {
            setTimeout(() => {
                $("#Unit, #Rooms, #Comments, #Category, #RatecardItem, #References, #QEItems, input[name=Preselected]").trigger("change");
            }, 0);
        });
    }
}

$(document).ready(() => (new CreateItem()).execute());
