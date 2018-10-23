/**
 * Vendor class for controlling the password tab.
 *
 * Initializes essentials like form validations for company page.
 */
class ListClients
{
    /**
     * Initialize company page components.
     *
     * @return void
     */
    constructor()
    {
        $("#ClientsList").DataTable({
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
                {
                    targets: 5,
                    orderable: false
                },
            ]
        });
    }
}

$(document).ready(() => new ListClients);
