
$(document).ready(function () {
    $("#UserMeasurementsTable").DataTable({
        "columns": [
            {"orderable": false},
            null,
            null,
            null,
            null,
            {"orderable": false}
        ],
        "paging": true,
        "lengthChange": false,
        "searching": true,
        "ordering": true,
        "info": true,
        "order": [],
        "autoWidth": false
    });
});
