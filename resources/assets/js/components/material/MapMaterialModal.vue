<template>
    <div class="modal fade" role="dialog" id="MapMaterialModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Map Material</h4>
                </div>
                <form :action="url" method="POST" accept-charset="utf-8" id="MapMaterialForm">
                    <div class="modal-body pd-bt-0">
                        <div class="form-group">
                            <label for="Brand">Brand*</label>
                            <select name="Brand" id="Brand" class="form-control" >
                                <option value="">Select a Brand</option>
                                <option v-for="brand in brands" :value="brand.Id" >{{ brand.Name }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="SubBrand">SubBrand*</label>
                            <select name="SubBrand" id="SubBrand" class="form-control" >
                                <option value="">Select a SubBrand</option>
                                <option v-for="SubBrand in subBrands" :value="SubBrand.Id" >{{ SubBrand.Name }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="FormCategory">FormCategory*</label>
                            <select name="FormCategory[]" id="FormCategory" class="form-control" multiple="multiple">
                                <option v-for="FormCategory in formCategories" :value="FormCategory.Id" >{{ FormCategory.Name }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="Warranty">Warranty*</label>
                            <input type="text" name="Warranty" id="Warranty" class="form-control" placeholder="Ex: 365"/>
                        </div>
                        <div class="form-group">
                            <label for="Guarantee">Guarantee*</label>
                            <input type="text" name="Guarantee" id="Guarantee" class="form-control" placeholder="Ex: 365"/>
                        </div>
                        <div class="form-group">
                            <label>Status*</label>
                            <div class="mr-tp-6">
                                <input type="radio" name="Status" id="Active" value="1" class="input-radio"/>
                                <label for="Active" tabindex="0"></label>
                                <label for="Active" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                <input type="radio" name="Status" id="Inactive" value="0" class="input-radio"/>
                                <label for="Inactive" tabindex="0"></label>
                                <label for="Inactive" class="text-normal cursor-pointer mr-rt-8">Inactive</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary pull-left button-custom" >Save</button>
                        <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
                <div class="overlay" id="MapFormOverlay" :class="{hidden: loader}">
                     <div class="large loader"></div>
                    <div class="loader-text">Mapping Material</div>
                </div>
                <div id="NotificationArea"></div>
            </div>
        </div>
    </div>
</template>
<script>
    // Child component
    export default {
        props: {
            "url": {
                type: String
            },
            "loader":{
                type: Boolean
            },
            "brands":{
                type: Array
            },
            "sub-brands":{
                type: Array
            },
            "form-categories":{
                type: Array
            }
        }
    }
</script>