//Include needed packages
require('../../bootstrap');

//Register Vue components 
//Shows User primary info...
import UserInformation from '../../components/siteMeasurement/userInformation';
//Shows Site Name, Address...
import SiteInformation from '../../components/siteMeasurement/siteInformation';
//Shows Area Calculation table of Rooms items...
import RoomAreaCalculation from '../../components/siteMeasurement/roomAreasCals';

//Vue Object initialization
const VueInstance = new Vue({
    //Vue root element
    el: '#RoomsAreaCalculationsPage',

    //Vue data variables
    data: {
        status: JSON.parse(status),
        notes: notes,
        userInfo: siteDetails.user,
        siteInfo: siteDetails.siteInfo,
        rooms: JSON.parse(rooms),
        statusLabels: { 1: 'info', 2: 'info', 3: 'danger', 4: 'info', 5: 'danger', 6: 'success'}
    },

    //Vue object life cycle hook 
    mounted() {
        this.findNegativeValues();
    },

    // Computed properties
    computed: {
        formattedNotes: function () {
            return (this.notes ? this.notes : "<small>N/A</small>");
        }
    },

    //Vue components
    components: {
        'user-information': UserInformation,
        'site-information': SiteInformation,
        'room-area-results': RoomAreaCalculation
    },

    //Vue methods 
    methods: {
        findNegativeValues() {
            $('.table > tbody > tr > td').each(function () {
                //loop through the values and assign it to a variable 
                var currency = $(this).html();
                //strip the non numeric, negative symbol and decimal point characters
                // e.g. Spaces and currency symbols 
                var val = Number(currency.replace(/[^0-9\.-]+/g, ""));
                //check the value and assign class as necessary 
                if (val < 0) {
                    $(this).addClass('alert alert-danger cursor-help').attr("title", "Invalid values");
                }
            });
        }
    }
});