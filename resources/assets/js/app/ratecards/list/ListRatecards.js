/**
 * Vendor class for controlling the password tab.
 *
 * Initializes essentials like form validations for company page.
 */
class ListRatecards
{
    /**
     * Initialize company page components.
     *
     * @return void
     */
    constructor()
    {
        $('#RatecardsList').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "oLanguage": {
                "sEmptyTable": "No data available in table"
            }
        });
        $("#RateCardListingTable_filter input").attr('placeholder', 'Search...');
    }
}

$(document).ready(() => new ListRatecards());
