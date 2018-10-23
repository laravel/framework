require('../../bootstrap');
let moment = require('moment');

const VueInstance = new Vue({
    el: "#MappedProjectsList",
    data() {
        return {
            Projects: Projects,
            MappedUsers: UserDetails,
            Status: {1:'Mapped', 2:'In Progress', 3:'In Progress'}
        };
    },
    filters: {
        giveDate(MappedAt){
            return moment(MappedAt).format("DD-MMM-YYYY");
        }
    },
    methods: {
    }
});
