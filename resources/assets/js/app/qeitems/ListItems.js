import { applyKeyboardNavigationPatch } from "support/Select2Patches";

/**
 * Vendor class for controlling the password tab.
 *
 * Initializes essentials like form validations for company page.
 */
class ListItems
{
    /**
     * Initialize company page components.
     *
     * @return void
     */
    constructor()
    {
        this.initializeSelect2(["#Room", "#RatecardItem"]);
        this.initializeFormLoader();
        this.initializeDatatable();
    }

    /**
     * Initialize select2 on the given selectors.
     *
     * @param  Array  selectors
     * @return void
     */
    initializeSelect2(selectors)
    {
        for (let selector of selectors) {
            let node = $(selector);
            node.select2();
            applyKeyboardNavigationPatch(node);
        }
    }

    /**
     * Show form loader on the form submit.
     *
     * @return void
     */
    initializeFormLoader()
    {
        $("#QEItemsForm").on("submit", () => {
            $("#QEItemsFormLoader").removeClass("hidden");
        });
    }

    /**
     * Initialize datatable on qeitems list.
     *
     * @return void
     */
    initializeDatatable()
    {
        $("#QEItemsList").DataTable({
            paging: false,
            order: [
                [1, 'asc'],
            ],
            info: true,
            autoWidth: false,
            oLanguage: {
                sEmptyTable: "No records found."
            },
            columnDefs: [
                {
                    targets: 0,
                    orderable: false
                },
                {
                    targets: 4,
                    orderable: false
                },
            ]
        });
    }
}

$(document).ready(() => new ListItems());
