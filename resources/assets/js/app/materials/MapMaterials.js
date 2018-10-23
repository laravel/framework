/*
 * Global variables
 */
let NotificationTimeout = 10000, notificationTimeoutID, UpdateValidator, MapMaterialValidator, vueInstance, Formrules, Formmessages;

/** include needed packages **/
require('../../bootstrap');
var jquery = require('jquery');
require('select2');

/** Import Vue table package **/
let VueTables = require('vue-tables-2');
Vue.use(VueTables.ClientTable);

/* Register vue components for Map, update and view modal pop-up*/
import Map from '../../components/material/MapMaterialModal';
import Update from '../../components/material/UpdateMapMaterial';
import View from '../../components/material/ViewMapMaterial';

/*
 * Form validation rules.
 */
Formrules = {
    Brand: {
        required: true
    },
    SubBrand: {
        required: true
    },
    Warranty: {
        required: true,
        number: true,
        min:1,
        max:9999

    },
    Guarantee: {
        required: true,
        number: true,
        min:1,
        max:9999
    },
    Status:{
        required: true
    }
};

/*
 * Form validation messages.
 */
Formmessages = {
    Brand: {
        required: "Brand can't be blank."
    },
    SubBrand: {
        required: "SubBrand can't be blank."
    },
    FormCategory: {
        required: "FormCategory can't be blank."
    },
    "FormCategory[]":{
        required: "FormCategory can't be blank."
    },
    Warranty: {
        required: "Warranty can't be blank.",
        number: "Warranty should be numeric value."
    },
    Guarantee: {
        required: "Guarantee can't be blank.",
        number: "Guarantee should be numeric value."
    },
    Status:{
        required: "Status is required."
    }
};

//Vue Instance
vueInstance = new Vue({
    el: "#MapMaterials",
    data: {
        Data:[],
        Brands : BrandsData,
        SubBrands : SubBrandsData,
        FormCategories : FormCategoriesData,
        MaterialUrl : MapMaterialUrl,
        FormOverLay : true,
        NotificationMessage : "",
        NotificationIcon : "",
        Loader : true,
        SelectedMaterial: {},
        filteredSubBrands:[],
        filteredUpdateSubBrands:[],
        ShowSaveLoader: true,
        ShowUpdateLoader: true,
        columns: ['Id', 'Brand', 'SubBrand', 'FormCategory', 'Warranty', 'Guarantee', 'IsActive', 'Action'],
        options: {
            headings: {
                Id: 'S.No',
                IsActive: 'Status'
            },
            texts: {
                filterPlaceholder: "Search...",
                noResults: "No matching records found."
            },
            columnsClasses: {
                'Id': 'map-id',
                'Brand': 'map-brand',
                'SubBrand': 'map-subbrand',
                'FormCategory': 'map-formcategory',
                'Warranty': 'map-warranty',
                'Guarantee': 'map-guarantee',
                'IsActive' : 'map-status',
                'Action': 'map-action'
            },
            filterable: ['Brand', 'SubBrand', 'FormCategory'],
            sortable: ['Brand', 'SubBrand', 'FormCategory']
        }
    },
    components: {
        'map-popup': Map,
        'update-popup': Update,
        'view-popup': View
    },
    created() {
        this.Data = Data;
    },
    methods: {
        
        /*
         * Display new map material modal pop-up.
         * 
         * @param   No parameters
         * @return  No return[void]
         */
        mapMaterial(){
            $("#MapMaterialModal").modal({
                show: true
            });
        },
        
        /*
         * Display update map material modal pop-up.
         * 
         * @param  UpdateMaterial  Object
         * @return  No return[void]
         */
        edit(UpdateMaterial){
            UpdateForm();
            this.SelectedMaterial = UpdateMaterial;
            $("#UpdateMapMaterialModal").modal({
                show: true
            });
        },
        
        /*
         * Display map material data modal pop-up.
         * 
         * @param  int  Index
         * @param  ViewMaterial  Object 
         * @return  No return[void]
         */
        view(Index, ViewMaterial){
            ViewMaterial.Index = Index;
            this.SelectedMaterial = ViewMaterial;
            $("#ViewMapMaterialModal").modal({
                show: true
            }); 
        },
        
        /*
         * Call back method for form submit.
         * 
         * @param  data
         * @param  string  formname
         * @return  No return[void]
         */
        onSuccess(data, formname){
            this.Data = data;
            if(formname === "Update"){
                this.SelectedMaterial = _.find(this.Data, function (value) {
                    return value.Id === this.SelectedMaterial.Id;
                }.bind(this));
            }else{
                $("#MapMaterialForm").trigger('reset');
            }
        },
        
        /*
         * Return's brand name.
         * 
         * @param  int  BrandId
         * @return  string
         */
        getBrand(BrandId){
            if (BrandId) {
                if (this.Brands.length > 0) {
                    let Brand = _.find(this.Brands, ["Id", BrandId]);
                    if (!_.isUndefined(Brand)) {
                        return Brand.Name;
                    }
                }
            }
            return '<small>N/A</small>';
        },
        
        /*
         * Return's SubBrand name.
         * 
         * @param  int  SubBrandId
         * @return  string
         */
        getSubBrand(SubBrandId){
            if (SubBrandId) {
                if (this.SubBrands.length > 0) {
                    let SubBrand = _.find(this.SubBrands, ["Id", SubBrandId]);
                    if (!_.isUndefined(SubBrand)) {
                        return SubBrand.Name;
                    }
                }
            }
            return '<small>N/A</small>';                
        },
        
        /*
         * Return's FormCategory name.
         * 
         * @param  int  FormCategoryId
         * @return  string
         */
        getFormCategory(FormCategoryId){
            if (FormCategoryId) {
                if (this.FormCategories.length > 0) {
                    let FormCategory = _.find(this.FormCategories, ["Id", FormCategoryId]);
                    if (!_.isUndefined(FormCategory)) {
                        return FormCategory.Name;
                    }
                }
            }
            return '<small>N/A</small>';                 
        }
    }
});

/**
 * PopulateNotifications - function to populate the notifications of the form.
 * @param   JSON    ResponseJSON
 * @return  No return[void]
 */
var populateNotifications = function (Response, formname = "default") {

    var NotificationArea = $("#NotificationArea");
    if(formname === "Update"){
        NotificationArea = $("#UpdateNotificationArea");
    }
    
    if (NotificationArea.children('.alert').length === 0) {
        NotificationArea.html('<div class="alert alert-dismissible hidden"></div>');
    }
    var AlertDiv = NotificationArea.children('.alert');
    if (Response.status === "success") {
        AlertDiv.removeAttr('class').removeAttr('style').addClass('alert alert-dismissible alert-success').html('<strong><i class="icon fa fa-check"></i> </strong><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button> &nbsp;' + Response.message);
        if (NotificationTimeout) {
            clearTimeout(NotificationTimeout);
        }
        NotificationTimeout = setTimeout(ClearNotificationMessage, 10000);
    } else {
        AlertDiv.removeAttr('class').removeAttr('style').addClass('alert alert-danger').html('<strong><i class="icon fa fa-ban"></i> </strong>' + Response.message);
    }
};

/**
 * ClearNotificationMessage - function to close the notifications after 5sec time.
 * @param   No parameters
 * @return  No return[void]
 */
var ClearNotificationMessage = function () {
    $("#NotificationArea").children(".alert").fadeOut("slow", function() {
        $(this).addClass('hidden');
    });
    $("#UpdateNotificationArea").children(".alert").fadeOut("slow", function() {
        $(this).addClass('hidden');
    });
};

/**
 * Populates the laravel validator error's.
 * 
 * @return  No return(void)
 */
function populateFormErrors(errors, formValidator)
{
    for (let elementName in errors) {
        let errorObject = {},
        previousValue = $("#" + elementName).data("previousValue") ? $("#" + elementName).data("previousValue") : {};
        previousValue.valid = false;
        previousValue.message = errors[elementName][0];
        $("#" + elementName).data("previousValue", previousValue);
        errorObject[elementName] = errors[elementName];
        formValidator.showErrors(errorObject);
    }
}

/**
 * Map Material form validation initialization.
 * 
 * @param   No parameters
 * @return  No return[void]
 */
var mapMaterialForm = function(){
    let CreateRules = {"FormCategory[]":{
                required: true
        }};
    _.merge(CreateRules, Formrules);
    MapMaterialValidator = $("#MapMaterialForm").validate({
        ignore: [],
        onkeyup: function (element, event) {
            if (this.invalid.hasOwnProperty(element.name)) {
                $(element).valid();
            }
        },
        errorClass: "help-block text-danger",
        errorElement: "span",
        highlight: function (element, errorClass) {
            if (element.id === "FormCategory") {
                $(element).next('span.select2').find(".select2-selection").addClass("select2-selection-error").closest('.form-group').addClass('has-error');
            }else{
                $(element).closest('.form-group').addClass("has-error");
            }
        },
        unhighlight: function (element, errorClass) {
            if (element.id === "FormCategory" ) {
                $(element).next('span.select2').find(".select2-selection").removeClass("select2-selection-error").closest('.form-group').removeClass('has-error');
            }else{
                $(element).closest('.form-group').removeClass("has-error");
            }
        },
        errorPlacement: function (error, element) {
            error.appendTo($(element).parent());
        },
        rules: CreateRules,
        messages: Formmessages,
        submitHandler: function (form, event) {
            event.preventDefault();
            vueInstance.ShowSaveLoader = false;
            ajaxCall(form, MapMaterialValidator);
        }
    });
};

/**
 * Update Map Material form validation initialization.
 * 
 * @param   No parameters
 * @return  No return[void]
 */
var UpdateForm = function(){
    let UpdateRules = {FormCategory:{
                required: true
        }};
    _.merge(UpdateRules, Formrules);
    UpdateValidator = $("#UpdateForm").validate({
        ignore: [],
        onkeyup: function (element, event) {
            if (this.invalid.hasOwnProperty(element.name)) {
                $(element).valid();
            }
        },
        errorClass: "help-block text-danger",
        errorElement: "span",
        highlight: function (element, errorClass) {
            $(element).closest('.form-group').addClass("has-error");
        },
        unhighlight: function (element, errorClass) {
            $(element).closest('.form-group').removeClass("has-error");
        },
        errorPlacement: function (error, element) {
            error.appendTo($(element).parent());
        },
        rules: UpdateRules,
        messages: Formmessages,
        submitHandler: function (form, event) {
            event.preventDefault();
            vueInstance.ShowUpdateLoader = false;
            ajaxCall(form, UpdateValidator, "Update");
        }
    });
};

/**
 * Remote call for form submit.
 * 
 * @return  No return[void]
 */
var ajaxCall = function(form, FormVariable, formname = "default"){
            
    $(".alert").addClass('hidden');
    let formData = new FormData(form);
    $.ajax({
        url: form.action,
        type: 'POST',
        dataType: 'json',
        data: formData,
        processData: false,
        contentType: false
    })
    .done(function (response) {
        if(response.status === "success"){
            vueInstance.onSuccess(response.data, formname);
        }
       populateNotifications(response, formname);
    })
    .fail(function (jqXHR) {
        if (jqXHR.status === 422) {
            var responsedata = JSON.parse(jqXHR.responseText);
            populateFormErrors(responsedata.data.errors, FormVariable);
        }
        else if (jqXHR.status === 413) {
            populateNotifications({
                status: "warning",
                message: "Max upload file size allowed 10MB. Check files size and try again."
            });
        }else {
            populateNotifications({
                status: "error",
                message: AlertData["10077"]
            });
        }
    })
    .always(function () {
        vueInstance.ShowUpdateLoader = true;
        vueInstance.ShowSaveLoader = true;        
    });
};

/**
 * Initialize select2 for map material form fields.
 * 
 */
var initializeSelect = function (){
    jquery('#Brand').select2({placeholder: "Select a Brand"});
    jquery('#SubBrand').select2({placeholder: "Select a SubBrand"});
    jquery('#FormCategory').select2({placeholder: "Select a FormCategory"}).next("span.select2").css({
        display: 'block',
        width: '100%'
    });
};

/**
 * Initialize select2 for update form fields.
 * 
 */
var initializeUpdateSelect = function (){
    jquery('#UpdateBrand').select2({placeholder: "Select a Brand"});
    jquery('#UpdateSubBrand').select2({placeholder: "Select a SubBrand"});
    jquery('#UpdateFormCategory').select2({placeholder: "Select a FormCategory"});
};

var returnSortedValues = function (BrandId){
    let subbrands = [];
    _.forEach(vueInstance.SubBrands, function(value) {
        if(BrandId === value.BrandId){
             subbrands.push(value);
        }
    });
    return subbrands;
};

$(document).ready(function () {

    initializeSelect();
    mapMaterialForm();
    UpdateForm();
    
    jquery("#Brand").on('change', function(event){
        if(this.value.length>0){
            
            vueInstance.filteredSubBrands = returnSortedValues(this.value);
            
            vueInstance.$nextTick(function(){
                jquery('#SubBrand').select2({placeholder: "Select a SubBrand"});
                jquery("#SubBrand").val("").trigger('change');
            });
        }
    });
    
    jquery("#UpdateBrand").on('change', function(event){
        if(this.value.length>0){
            
            vueInstance.filteredUpdateSubBrands = returnSortedValues(this.value);
            
            vueInstance.$nextTick(function(){
                jquery('#UpdateSubBrand').select2({placeholder: "Select a SubBrand"});
                jquery("#UpdateSubBrand").trigger('change');
            });
        }
    });
    
    $("#MapMaterialForm").on('reset', function(event){
        jquery("#Brand, #SubBrand, #FormCategory").val("").trigger('change');
        MapMaterialValidator.resetForm();
    });
    
    $("#MapMaterialModal").on('hidden.bs.modal', function (event) {
        $("#MapMaterialForm").trigger('reset');
        $(".alert").addClass('hidden');
        
    });
       
    $("#UpdateMapMaterialModal, #ViewMapMaterialModal").on('hidden.bs.modal', function (event) {
        vueInstance.SelectedMaterial = {};
        $(".alert").addClass('hidden');
    });
    
    $('#UpdateMapMaterialModal').on('shown.bs.modal', function () {
        initializeUpdateSelect();
        jquery("#UpdateBrand").trigger('change');
    });
});