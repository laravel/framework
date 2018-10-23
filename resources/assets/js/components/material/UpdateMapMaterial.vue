<template>
    <div class="modal fade" role="dialog" id="UpdateMapMaterialModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Update</h4>
                </div>
                <form :action="url" method="POST" accept-charset="utf-8" id="UpdateForm">
                    <div class="modal-body pd-bt-0">
                        <input type="hidden" :value="selectedMaterial.Id" name="mapId">
                        <div class="form-group">
                            <label for="UpdateBrand">Brand*</label>
                            <select name="Brand" id="UpdateBrand" class="form-control" >
                                <option value="">Select a Brand</option>
                                <option v-for="brand in brands" :value="brand.Id" :selected="selectedMaterial.Brand==brand.Id" >{{ brand.Name }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="UpdateSubBrand">SubBrand*</label>
                            <select name="SubBrand" id="UpdateSubBrand" class="form-control" >
                                <option value="">Select a SubBrand</option>
                                <option v-for="SubBrand in subBrands" :value="SubBrand.Id" :selected="selectedMaterial.SubBrand==SubBrand.Id"  >{{ SubBrand.Name }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="UpdateFormCategory">FormCategory*</label>
                            <select name="FormCategory" id="UpdateFormCategory" class="form-control" >
                                <option value="">Select a FormCategory</option>
                                <option v-for="FormCategory in formCategories" :value="FormCategory.Id" :selected="selectedMaterial.FormCategory==FormCategory.Id">{{ FormCategory.Name }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="Warranty">Warranty*</label>
                            <input type="text" name="Warranty" id="Warranty" :value="selectedMaterial.Warranty" class="form-control" placeholder="Ex: 365"/>
                        </div>
                        <div class="form-group">
                            <label for="Guarantee">Guarantee*</label>
                            <input type="text" name="Guarantee" id="Guarantee" :value="selectedMaterial.Guarantee" class="form-control" placeholder="Ex: 365"/>
                        </div>
                        <div class="form-group">
                            <label>Status*</label>
                            <div class="mr-tp-6">
                                <input type="radio" name="Status" id="UpdateActive" value="1" class="input-radio" :checked="selectedMaterial.IsActive==1"/>
                                <label for="UpdateActive" tabindex="0"></label>
                                <label for="UpdateActive" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                <input type="radio" name="Status" id="UpdateInactive" value="0" class="input-radio" :checked="selectedMaterial.IsActive==0"/>
                                <label for="UpdateInactive" tabindex="0"></label>
                                <label for="UpdateInactive" class="text-normal cursor-pointer mr-rt-8">Inactive</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary pull-left button-custom" >Update</button>
                        <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
                <div class="overlay" id="MapFormOverlay" :class="{hidden: loader}">
                     <div class="large loader"></div>
                    <div class="loader-text">Updating...</div>
                </div>
                <div id="UpdateNotificationArea"></div>
            </div>
        </div>
    </div>
</template>
<script>
    // Child component
    export default {
        props: {
            "selected-material": {
                type: Object
            },
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