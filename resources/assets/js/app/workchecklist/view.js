
$(document).ready(function () {
    $('#Project').select2({
        placeholder: "Select a Project",
        language: {
            noResults: function () {
                return "No Projects found";
            }
        }
    }).on("change", function (e) {
        onProjectSelect(this.value, e);
    });
    $('#Type').select2({
        placeholder: "Select a Type",
        language: {
            noResults: function () {
                return "No Types found";
            }
        }
    }).on("change", function (e) {
        onTypeSelect(this.value, e);
    });
    initialiseValidator();
    openChecklistModal();
//    initialiseDataTable();
    initialiseNoChecklistFoundLink();
});

function onProjectSelect(projectId, event) {
    if (projectId !== '') {
        $("#Project").next('span.select2').find(".select2-selection").removeClass("select2-selection-error").closest('.form-group').removeClass('has-error');
        $("#Project").next('span.select2').find('span.text-error').html("");
        //Get the checklists
        let checklistType = $("#Type").val() ? "/" + $("#Type").val() : "";
        window.location = ChecklistListUrl + "/" + $("#Project").val() + checklistType;
    }
}

function onTypeSelect(typeId, event) {
    if ($("#Project").val() === '') {
        let typeElement = $("#Project").next('span.select2').find(".select2-selection").addClass("select2-selection-error").closest('.form-group');
        if (!typeElement.hasClass('has-error')) {
            typeElement.addClass('has-error');
            $("#Project").next('span.select2').append("<span class='text-error'>Please select Project.</span>");
        }
    } else {
        //Get the checklists
        let checklistType = typeId ? "/" + typeId : "";
        window.location = ChecklistListUrl + "/" + $("#Project").val() + checklistType;
    }
}

function initialiseValidator() {
    // configure your validation
    $("#ChecklistForm").validate({
       
    });
}

function openChecklistModal() {
    $(".view-checklist-link").off("click").on('click', function (event) {
        event.preventDefault();
        $("#ChecklistViewModal").modal("show");
        $("#ChecklistViewModal .modal-content").html('<div class="modal-loader"><div class="large loader"></div><div class="loader-text">Fetching View</div></div>');
        $.ajax({
            url: this.href,
            type: 'GET',
            dataType: 'html'
        })
        .done(function (response) {
            $("#ChecklistViewModal .modal-content").html(response);
        })
        .fail(function () {
            $("#ChecklistViewModal .modal-content").html('<div class="alert alert-error"><div>' + AlertData[10077] + '</div></div>');
        });
    });
}

function initialiseDataTable() {
    $("#CheckListsTable").DataTable({
        paging: true,
        lengthChange: false,
        searching: true,
        ordering: false,
        info: true,
        autoWidth: false,
        oLanguage: {
            sEmptyTable: "No Checklists found."
        }
    });
    $("#CheckListsTable_filter input").attr('placeholder', 'Search...').focus();
}

function initialiseNoChecklistFoundLink() {
    $("#NoChecklistsFound").on('click', function (event) {
        event.preventDefault();
        let checklistType = $('#Type').val();
        if (checklistType) {
            window.location = this.href;
        } else {
            let typeElement = $("#Type").next('span.select2').find(".select2-selection").addClass("select2-selection-error").closest('.form-group');
            if (!typeElement.hasClass('has-error')) {
                typeElement.addClass('has-error');
                $("#Type").next('span.select2').append("<span class='text-error'>Please select Type.</span>");
            }
        }
    });
}