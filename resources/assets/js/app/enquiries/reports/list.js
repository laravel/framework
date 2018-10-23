
var dataTableObject;

/** include needed packages **/
require('../../../bootstrap');

const vueInstance = new Vue({
    el: "#EnquiryReportsPage",
    data: {
        typePdf: "pdf",
        typeExcel: "excel",
        statusPdf: "",
        statusExcel: "",
        DownloadRoute: DownloadRoute
    },
    methods: {
        /**
         * Initialize datatable.
         *
         * @return void
         */
        initializeDatatable()
        {
            let status = $(location).attr("href").split('/').pop();
            let postUrl = '/enquiries/status/'+status;
            dataTableObject = $("#EnquiryReportTable").DataTable({
                destroy: true,
                processing: true,
                serverSide: true,
                ajax: {
                    "url": postUrl,
                    "type": "post",
                    "dataSrc": function (response) {
                        if (response.recordsTotal < 1) {
                            $("#EnquiryListBox,.download-options").addClass('hidden');
                            $(".no-enquiries").removeClass("hidden")
                        } else {
                            $("#EnquiryListBox,.download-options").removeClass('hidden');
                            $(".no-enquiries").addClass("hidden")
                        }
                        return response.data;
                    }
                },
                paging: true,
                lengthChange: false,
                searching: false,
                order: [],
                info: true,
                autoWidth: false,
                oLanguage: {
                    sEmptyTable: "No enquiries found."
                },
                columns: [
                    {
                        "data": "SNo",
                        "orderable": false,
                        "className": "text-vertical-align text-center"
                    },
                    {
                        "data": "EnquiryRef",
                        "orderable": false,
                        "className": "text-vertical-align"
                    },
                    {
                        "data": "UserData",
                        "orderable": false,
                        "className": "text-vertical-align"
                    },
                    {
                        "data": "ProjectInfo",
                        "orderable": false,
                        "className": "text-vertical-align"
                    },
                    {
                        "data": "SuperBArea",
                        "orderable": false,
                        "className": "text-vertical-align text-center"
                    },
                    {
                        "data": "EnqStatus",
                        "orderable": false,
                        "className": "text-vertical-align text-center"
                    },
                    {
                        "data": "EnqDescription",
                        "orderable": false,
                        "className": "text-vertical-align"
                    },
                    {
                        "data": "IsAwarded",
                        "orderable": false,
                        "className": "text-vertical-align text-center"
                    }
                ]
            });
        }
    }
});

$(document).ready(function () {
    vueInstance.initializeDatatable();
    let status = $(location).attr("href").split('/').pop();
    vueInstance.statusPdf = status;
    vueInstance.statusExcel = status;
});