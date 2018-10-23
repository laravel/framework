/**
 *  
 * Material Master script
 * 
 **/
var formValidator;
var dataTableObject, SurfaceMaterialsdatatableObject, EdgebandDatatableObject, FanDatatableObject, 
HandleDatatableObject, WallCurtainDatatableObject, WallpaintDataTableObject, WallpaperDataTableObject, UphosteryDataTableObject = {};

/** Include needed packages **/
require('../../bootstrap');

// Vue Model variables object
var SearchVariables = {
    "Categories": categories, //Stores  the category of Surface material
    "SubBrands": [], // Stores the Form Material SubBrand
    "SurfaceMatcatgs": surfaceMatcatg, // Stores the Form Material Categories
    "CategoryId": null,
    "Slug": null,
    "CdnUrl": CDNURL, // S3 cloud storage URL
    ResultantMaterials: [],
    "Category": SelectedCategory// Pre selected category slug.
};

/** Initialize Vue instance **/
const VueInstance = new Vue({
    el: '#MaterialMaster',
    data: SearchVariables,
    mounted() {
        // Initialize data Pickers
        this.InitializeDatePickers();
    },
    created() {
        this.SubBrands = subBrands;
        this.CategoryId = this.getCategory(this.Category);
        this.Slug = this.getCategorySlug(this.CategoryId);
    },
    methods: {
        // Get Form Category using given Slug
        getCategory(surface) {
            if (surface) {
                if (this.Categories.length > 0) {
                    let catagory = _.find(this.Categories, ["Slug", surface]);
                    if (catagory !== "undefined") {
                        return catagory.Id;
                    }
                }
            }
            return null;
        },
        // Get Form Category Slug using given Category id
        getCategorySlug(CatId) {
            if (CatId) {
                if (this.Categories.length > 0) {
                    let catagory = _.find(this.Categories, ["Id", CatId]);
                    if (catagory !== "undefined") {
                        return catagory.Slug;
                    }
                }
            }
            return null;
        },
        // Assign Category and Slug to Vue variable
        getFormData(CategoryId) {
            if (CategoryId) {
                this.CategoryId = CategoryId;
                this.Slug = this.getCategorySlug(CategoryId);
            }
            return null;
        },
        // Check whether current form is empty.
        isFormEmpty() {
            var serializedArray = $("#MaterialSearchForm").serializeArray(), count = 0;
            serializedArray.map(function (input) {
                if (input.value.replace(/\s/g, "").length === 0) {
                    count++;
                }
            });
            if (count === serializedArray.length) {
                return true;
            } else {
                return false;
            }
        },
        // Initialise Date Pickers
        InitializeDatePickers() {
            $("#CreatedDate").datepicker({
                autoclose: true,
                endDate: '0d',
                format: "dd-M-yyyy",
                toggleActive: true,
                todayHighlight: true,
                todayBtn: true
            });

            // Define datepicker options for Updated Date
            $("#UpdatedDate").datepicker({
                autoclose: true,
                endDate: '0d',
                format: "dd-M-yyyy",
                toggleActive: true,
                todayHighlight: true,
                todayBtn: true
            });
        },
        // Initialize popup when user clicks on Full Sheet thumbnail
        initializeGallery(imagesJSON) {
            let self = this;
            // Parse JSON and get image path and title
            let thumbnails = JSON.parse(imagesJSON);
            // Create image object which fits for plugin input
            thumbnails.forEach(function (obj) {
                obj.src = self.CdnUrl + obj.Path;
                obj.title = obj.UserFileName;
            });
            this.$nextTick(function () {
                $(".image-link").magnificPopup({
                    items: thumbnails,
                    gallery: {
                        enabled: true
                    },
                    type: 'image',
                    callbacks: {
                        open: function () {
                            var mfp = $.magnificPopup.instance;
                            var proto = $.magnificPopup.proto;
                            var Count = mfp.items.length;
                            if (!mfp.index && Count > 1) {
                                mfp.arrowLeft.css('display', 'none');
                            }
                            if (!(mfp.index - (Count - 1)) && Count > 1) {
                                mfp.arrowRight.css('display', 'none');
                            }
                            // Extend function that moves to next item
                            mfp.next = function () {
                                if (mfp.index < (Count - 1)) {
                                    proto.next.call(mfp);
                                }
                                if (Count > 1) {
                                    if (!(mfp.index - (Count - 1))) {
                                        mfp.arrowRight.css('display', 'none');
                                    }
                                    if (mfp.index > 0) {
                                        mfp.arrowLeft.css('display', 'block');
                                    }
                                }
                            };
                            // Extend function that moves back to prev item
                            mfp.prev = function () {
                                if (mfp.index > 0) {
                                    proto.prev.call(mfp);
                                }
                                if (Count > 1) {
                                    if (!mfp.index) {
                                        mfp.arrowLeft.css('display', 'none');
                                    }
                                    if (Count > 1) {
                                        mfp.arrowRight.css('display', 'block');
                                    }
                                }
                            };
                        }
                    }
                }).magnificPopup('open');
            });
        },
        
        // Initialise DataTable
        InitializeDataTables() {
            datatableObject = $("#MaterialsListTable").DataTable({
                "columns": [
                    {
                        "orderable": false,
                        "width": "5%"
                    },
                    {
                        "width": "15%",
                    },
                    {
                        "width": "15%"
                    },
                    {
                        "width": "15%"
                    },
                    {
                        "width": "15%"
                    },
                    {
                        "width": "15%"
                    },
                    {
                        "width": "15%"
                    },
                    {
                        "width": "5%",
                        "orderable": false

                    },
                ],
                paging: true,
                lengthChange: false,
                searching: true,
                order: [],
                info: true,
                autoWidth: false,
                "oLanguage": {
                    "sEmptyTable": "No data available in table"
                },
                columnDefs: [
                    {
                        targets: 0,
                        orderable: false
                    },
                    {
                        targets: 6,
                        orderable: false
                    }

                ]
            });
            $("#MaterialsListTable_filter input").attr('placeholder', 'Search...').focus();
        },
        
        // Initialise Surface list Data Table
        InitializeSurfaceMaterialDataTable() {
            SurfaceMaterialsdatatableObject = $("#SurfaceMaterialsListTable").DataTable({
                "columns": [
                    {
                        "orderable": false,
                        "width": "3%"
                    },
                    {
                        "width": "9%"
                    },
                    {
                        "width": "8%"
                    },
                    {
                        "width": "8%"
                    },
                    {
                        "width": "7%"
                    },
                    {
                        "width": "8%"
                    },
                    {
                        "width": "8%",
                        "orderable": false
                    },
                    {
                        "width": "10%",
                        "orderable": false
                    },
                    {
                        "width": "10%"
                    },
                    {
                        "width": "8%",
                        "orderable": false
                    },
                    {
                        "width": "9%",
                        "orderable": false
                    },
                    {
                        "width": "7%",
                        "orderable": false
                    },
                    {
                        "width": "5%",
                        "orderable": false
                    }
                ],
                paging: true,
                lengthChange: false,
                searching: true,
                order: [],
                info: true,
                autoWidth: false,
                "oLanguage": {
                    "sEmptyTable": "No data available in table"
                }
            });
            $("#SurfaceMaterialsListTable_filter input").attr('placeholder', 'Search...').focus();
        },
        // Initialise Edgeband list Data Table
        InitializeEdgebandDataTable() {
            EdgebandDatatableObject = $("#EdgebandTable").DataTable({
                "columns": [
                    {
                        "orderable": false,
                        "width": "5%"
                    },
                    {
                        "width": "13%"
                    },
                    {
                        "width": "15%"
                    },
                    {
                        "width": "15%"
                    },
                    {
                        "width": "15%"
                    },
                    {
                        "width": "12%",
                        "orderable": false
                    },
                    {
                        "width": "15%",
                        "orderable": false
                    },
                    {
                        "width": "10%",
                        "orderable": false
                    }
                ],
                paging: true,
                lengthChange: false,
                searching: true,
                order: [],
                info: true,
                autoWidth: false,
                "oLanguage": {
                    "sEmptyTable": "No data available in table"
                }
            });
            $("#EdgebandTable_filter input").attr('placeholder', 'Search...').focus();
        },
        // Initialise Fan list Data Table
        InitializeFanDataTable() {
            FanDatatableObject = $("#FanTable").DataTable({
                "columns": [
                    {
                        "orderable": false,
                        "width": "7%"
                    },
                    {
                        "width": "15%"
                    },
                    {
                        "width": "15%"
                    },
                    {
                        "width": "15%",
                        "orderable": false
                    },
                    {
                        "width": "18%"
                    },
                    {
                        "width": "20%",
                        "orderable": false
                    },
                    {
                        "width": "10%",
                        "orderable": false
                    }
                ],
                paging: true,
                lengthChange: false,
                searching: true,
                order: [],
                info: true,
                autoWidth: false,
                "oLanguage": {
                    "sEmptyTable": "No data available in table"
                }
            });
            $("#FanTable_filter input").attr('placeholder', 'Search...').focus();
        },
        // Initialise Handle list Data Table
        InitializeHandleDataTable() {
            HandleDatatableObject = $("#HandleTable").DataTable({
                "columns": [
                    {
                        "orderable": false,
                        "width": "5%"
                    },
                    {
                        "width": "15%"
                    },
                    {
                        "width": "15%"
                    },
                    {
                        "width": "15%",
                        "orderable": false
                    },
                    {
                        "width": "15%"
                    },
                    {
                        "width": "15%",
                        "orderable": false
                    },
                    {
                        "width": "10%",
                        "orderable": false
                    },
                    {
                        "width": "10%",
                        "orderable": false
                    }
                ],
                paging: true,
                lengthChange: false,
                searching: true,
                order: [],
                info: true,
                autoWidth: false,
                "oLanguage": {
                    "sEmptyTable": "No data available in table"
                }
            });
            $("#HandleTable_filter input").attr('placeholder', 'Search...').focus();
        },
        // Initialise Wall Curtain list Data Table
        InitializeWallCurtainsDataTable() {
            WallCurtainDatatableObject = $("#WallCurtainsTable").DataTable({
                "columns": [
                    {
                        "orderable": false,
                        "width": "5%"
                    },
                    {
                        "width": "15%"
                    },
                    {
                        "width": "15%"
                    },
                    {
                        "width": "15%",
                        "orderable": false
                    },
                    {
                        "width": "15%",
                        "orderable": false
                    },
                    {
                        "width": "10%",
                        "orderable": false
                    },
                    {
                        "width": "15%"
                    },
                    {
                        "width": "10%",
                        "orderable": false
                    }
                ],
                paging: true,
                lengthChange: false,
                searching: true,
                order: [],
                info: true,
                autoWidth: false,
                "oLanguage": {
                    "sEmptyTable": "No data available in table"
                }
            });
            $("#WallCurtainsTable_filter input").attr('placeholder', 'Search...').focus();
        },
        // Initialise Wall Paint list Data Table
        InitializeWallpaintDataTable() {
            WallpaintDataTableObject = $("#WallpaintTable").DataTable({
                "columns": [
                    {
                        "orderable": false,
                        "width": "5%"
                    },
                    {
                        "width": "15%"
                    },
                    {
                        "width": "15%"
                    },
                    {
                        "width": "12%"
                    },
                    {
                        "width": "15%",
                        "orderable": false
                    },
                    {
                        "width": "10%",
                        "orderable": false
                    },
                    {
                        "width": "10%"
                    },
                    {
                        "width": "10%",
                        "orderable": false
                    },
                    {
                        "width": "8%",
                        "orderable": false
                    }
                ],
                paging: true,
                lengthChange: false,
                searching: true,
                order: [],
                info: true,
                autoWidth: false,
                "oLanguage": {
                    "sEmptyTable": "No data available in table"
                }
            });
            $("#WallpaintTable_filter input").attr('placeholder', 'Search...').focus();
        },
        // Initialise Wall Paper list Data Table
        InitializeWallpaperDataTable() {
            WallpaperDataTableObject = $("#WallpaperTable").DataTable({
                "columns": [
                    {
                        "orderable": false,
                        "width": "5%"
                    },
                    {
                        "width": "15%"
                    },
                    {
                        "width": "15%"
                    },
                    {
                        "width": "12%",
                        "orderable": false
                    },
                    {
                        "width": "10%",
                        "orderable": false
                    },
                    {
                        "width": "10%",
                        "orderable": false
                    },
                    {
                        "width": "13%"
                    },
                    {
                        "width": "10%",
                        "orderable": false
                    },
                    {
                        "width": "10%",
                        "orderable": false
                    }
                ],
                paging: true,
                lengthChange: false,
                searching: true,
                order: [],
                info: true,
                autoWidth: false,
                "oLanguage": {
                    "sEmptyTable": "No data available in table"
                }
            });
            $("#WallpaperTable_filter input").attr('placeholder', 'Search...').focus();
        },
        // Initialise Uphostery list Data Table
        InitializeUphosteryDataTable() {
            UphosteryDataTableObject = $("#UphosteryTable").DataTable({
                "columns": [
                    {
                        "orderable": false,
                        "width": "5%"
                    },
                    {
                        "width": "15%"
                    },
                    {
                        "width": "15%"
                    },
                    {
                        "width": "13%",
                        "orderable": false
                    },
                    {
                        "width": "12%",
                        "orderable": false
                    },
                    {
                        "width": "15%",
                        "orderable": false
                    },
                    {
                        "width": "15%"
                    },
                    {
                        "width": "10%",
                        "orderable": false
                    }
                ],
                paging: true,
                lengthChange: false,
                searching: true,
                order: [],
                info: true,
                autoWidth: false,
                "oLanguage": {
                    "sEmptyTable": "No data available in table"
                }
            });
            $("#UphosteryTable_filter input").attr('placeholder', 'Search...').focus();
        },
        // Shows Surface Masters list
        showSurfaceMastersResult(response) {
            if (SurfaceMaterialsdatatableObject) {
                SurfaceMaterialsdatatableObject.destroy();
                SurfaceMaterialsdatatableObject = null;
            }
            this.renderTableHtml(response);
            this.InitializeSurfaceMaterialDataTable();
        },
        // Shows total Edbgebands
        showEdgebandResult(response) {
            if (EdgebandDatatableObject) {
                EdgebandDatatableObject.destroy();
                EdgebandDatatableObject = null;
            }
            this.renderTableHtml(response);
            this.InitializeEdgebandDataTable();
        },
        // Shows total Fans
        showFanResult(response) {
            if (FanDatatableObject) {
                FanDatatableObject.destroy();
                FanDatatableObject = null;
            }
            this.renderTableHtml(response);
            this.InitializeFanDataTable();
        },
        // Shows total Hanldes
        showHandleResult(response) {
            if (HandleDatatableObject) {
                HandleDatatableObject.destroy();
                HandleDatatableObject = null;
            }
            this.renderTableHtml(response);
            this.InitializeHandleDataTable();
        },
        // Shows total Wall Curtains
        showWallCurtainResult(response) {
            if (WallCurtainDatatableObject) {
                WallCurtainDatatableObject.destroy();
                WallCurtainDatatableObject = null;
            }
            this.renderTableHtml(response);
            this.InitializeWallCurtainsDataTable();
        },
        // Shows total Wall Paints
        showWallpaintResult(response) {
            if (WallpaintDataTableObject) {
                WallpaintDataTableObject.destroy();
                WallpaintDataTableObject = null;
            }
            this.renderTableHtml(response);
            this.InitializeWallpaintDataTable();
        },
        // Shows total Wall Papers
        showWallpaperResult(response) {
            if (WallpaperDataTableObject) {
                WallpaperDataTableObject.destroy();
                WallpaperDataTableObject = null;
            }
            this.renderTableHtml(response);
            this.InitializeWallpaperDataTable();
        },
        // Shows total Uphostery
        showUphosteryResult(response) {
            if (UphosteryDataTableObject) {
                UphosteryDataTableObject.destroy();
                UphosteryDataTableObject = null;
            }
            this.renderTableHtml(response);
            this.InitializeUphosteryDataTable();
        },
        // Shows all result of given master
        showAllResult(response) {
            if (dataTableObject) {
                dataTableObject.destroy();
                dataTableObject = null;
            }
            this.renderTableHtml(response);
            this.InitializeDataTables();
        },
        // Render Table html into Dev
        renderTableHtml(html) {
            $(".search-result").removeClass("hidden").html("");
            $(".search-result").html(html);
        }
    }
});

$(document).ready(function () {
    InitializeValidator();
    InitializeSelect2();
    InitializeDateFilters();
    VueInstance.InitializeSurfaceMaterialDataTable();
    VueInstance.InitializeEdgebandDataTable();
    VueInstance.InitializeFanDataTable();
    VueInstance.InitializeHandleDataTable();
    VueInstance.InitializeWallpaintDataTable();
    VueInstance.InitializeWallpaperDataTable();
    VueInstance.InitializeWallCurtainsDataTable();
    VueInstance.InitializeDataTables();
    VueInstance.InitializeUphosteryDataTable();
    $("#Category").trigger('change');
});

var InitializeDateFilters = function () {
    // Highlight date filter when filter is applied to date.
    $('.date-picker').datepicker().on('changeDate', function (e) {
        var Button = $(this).siblings('div.input-group-btn').children('button').children("i");
        if (!Button.hasClass('filter-show')) {
            $(this).siblings('div.input-group-btn').children('button').children("i").addClass('filter-show');
        }
        if ($(this).val() === "") {
            $(this).siblings('div.input-group-btn').children('button').children("i").removeClass('filter-show');
        }
    });
    // Add active classes to list in dropdown menu when user selects one
    $("#CreatedDateFilters li, #UpdatedDateFilters li").on('click', function (event) {
        event.preventDefault();
        $(this).addClass('active').siblings('.active').removeClass('active');
    });
};

$(document).on('click', '.FullSheetImages', function (event) {
    event.preventDefault();
    let FullSheetImagesJson = $(this).attr("value");
    VueInstance.initializeGallery(FullSheetImagesJson);

});
$(document).on('click', '.SampleImages', function (event) {
    event.preventDefault();
    let SampleImagesJson = $(this).attr("value");
    VueInstance.initializeGallery(SampleImagesJson);

});
$(document).on('click', '.UsageImages', function (event) {
    event.preventDefault();
    let UsageImagesJson = $(this).attr("value");
    VueInstance.initializeGallery(UsageImagesJson);

});

// Initialize form validator
var InitializeValidator = function () {
    formValidator = $("#MaterialSearchForm").validate({
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
        rules: {
            DesignNumber: {
                number: {
                    depends: function (element) {
                        if ($("#Category option:selected").text() === "Surface Material") {
                            return true;
                        }
                        return false;
                    }
                },
                alphaNumericWithSpace: {
                    depends: function (element) {
                        if ($("#Category option:selected").text() === "Air Conditioners" || $("#Category option:selected").text() === "Chimney") {
                            return true;
                        }
                        return false;
                    }
                },
                maxlength: 15
            },
            RatedCapacity: {
                number: true,
                maxlength: 5
            },
            Shape: {
                CheckConsecutiveSpaces: true,
                ValidateAlphabet: true,
                maxlength: 15
            }
        },

        submitHandler: function (form, event) {
            event.preventDefault();
            $("#NotificationArea").html("");
            $("#MaterialSearchFormSubmit").trigger('blur');
            // Check whether form is empty.
            if (VueInstance.isFormEmpty()) {
                PopulateNotifications({
                    status: "warning",
                    alertMessage: "No search term given to start searching."
                });
            } else {
                let serializedArray = $(form).serializeArray(), formData = new FormData;

                serializedArray.map(function (element, index) {
                    if (element.value.length > 0) {
                        if (element.name === "CreatedDate") {
                            formData.append("CratedDateFilter", $("#CreatedDateFilters .active").data("filter-name"));
                        }
                        if (element.name === "UpdatedDate") {
                            formData.append("UpdatedDateFilter", $("#UpdatedDateFilters .active").data("filter-name"));
                        }
                        if (element.name === "SubBrand") {
                            formData.append("Subrand", $("#SubBrand option:selected").text().replace(/\s/g, ""));
                        }
                        formData.append(element.name, element.value);
                    }
                });
                $("#SearchFormOverlay").removeClass('hidden');
                $.ajax({
                    url: form.action,
                    type: 'POST',
                    dataType: 'html',
                    data: formData,
                    processData: false,
                    contentType: false
                })
                .done(function (response) {
                    switch (VueInstance.Slug) {
                        case "surface":
                            VueInstance.showSurfaceMastersResult(response);
                            break;
                        case "edgeband":
                            VueInstance.showEdgebandResult(response);
                            break;
                        case "fan":
                            VueInstance.showFanResult(response);
                            break;
                        case "handle":
                            VueInstance.showHandleResult(response);
                            break;
                        case "wallpaint":
                            VueInstance.showWallpaintResult(response);
                            break;
                        case "wallcurtain":
                            VueInstance.showWallCurtainResult(response);
                            break;
                        case "upholstery":
                            VueInstance.showUphosteryResult(response);
                            break;
                        default:
                            VueInstance.showAllResult(response);
                            break;
                    }
                })
                .fail(function (jqXHR) {
                    PopulateNotifications({
                        status: "error",
                        alertMessage: AlertData["10077"]
                    });
                })
                .always(function () {
                    $("#SearchFormOverlay").addClass('hidden');
                });
            }
        }
    });
};

// Reset form on form reset event.
$("#MaterialSearchForm").off("reset").on('reset', function () {
    $(".form-group").removeClass("has-error");
    $("select.form-control").trigger("change");
    formValidator.resetForm();
    $("i").removeClass("filter-show");
    $('.date-picker, .date-picker-addtopn').datepicker('clearDates');
    $('#SubBrand').val(null).trigger('change');
});

var InitializeSelect2 = function () {
    $("#Category").select2({
        placeholder: "Choose a Category"
    }).next("span.select2").css({
        display: 'block',
        width: '100%'
    });

    $("#SubBrand").select2({
        placeholder: "Choose a Sub Brand"
    }).next("span.select2").css({
        display: 'block',
        width: '100%'
    });
    $("#AcType, #ChimneyType, #Finish, #Pattern, #FanType, #Colour, #GeyserType, #BodyColour, #Type, #Material, #Finish, #HobType, #BasketType, #PlatformStoneType, #SinkType, #FinishType, #TapType, #MountType, #Wattages, #SurfaceCategory, #WaterPurifierType, #PurificationStages, #CurtainType").select2({
        placeholder: "Select an Item"
    }).next("span.select2").css({
        display: 'block',
        width: '100%'
    });
};


$(document.body).on("change", "#Category", function () {
    if (this.value.length > 0) {
        VueInstance.getFormData(this.value);
        $(".search-result").addClass('hidden');
        GetFilterView(this.value);
    }
});

var GetFilterView = function (CategId) {
    $.ajax({
        url: '/materials/getfilter/' + CategId,
        type: 'GET',
        dataType: 'html'
    })
    .done(function (response) {
        $("#AllFilterView").empty().html(response);
        VueInstance.InitializeDatePickers();
        InitializeSelect2();
        InitializeDateFilters();
    })
    .fail(function () {

    });
};
