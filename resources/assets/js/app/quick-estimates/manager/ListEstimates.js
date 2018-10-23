/**
 * ListEstimates class for listing all quick estimates.
 *
 * Initializes essentials like data-tables for listing table.
 */
class ListEstimates
{
    /**
     * Create a new instance of ListEstimates.
     *
     * @return void
     */
    constructor()
    {
        this.initializeDatatable();
    }

    /**
     * Initialize datatable on estimates list.
     *
     * @return void
     */
    initializeDatatable()
    {
        $("#QuickEstimatesList").DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": true,
            "order": [],
            "info": true,
            "autoWidth": false,
            "oLanguage": {
                "sEmptyTable": "No records found."
            },
            "columnDefs": [
                {
                    "targets": 0,
                    "orderable": false
                },
                {
                    "targets": 9,
                    "orderable": false
                },
            ],
        });
    }
}

$(document).ready(() => new ListEstimates());
