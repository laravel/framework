
//Include needed packages
require('../../bootstrap');

//Create Vue instance
new Vue({

    //Root(mount) Element
    el: '#ActiveProjectsPage',

    //Vue Data variables
    data: {

        projects: Projects
    },

    //Vue Object creation cycle hook
    mounted() {

        //Function to initialize DataTable
        this.initializeDatatable();
    },

    methods: {

        initializeDatatable() {

            $("#ActiveProjectTable").DataTable({
                "columns": [
                    {"orderable": false},
                    null,
                    null,
                    null,
                    {"orderable": false}
                ],
                "paging": true,
                "lengthChange": false,
                "searching": false,
                "ordering": true,
                "info": true,
                "order": [],
                "autoWidth": false
            });
        }
    }
});