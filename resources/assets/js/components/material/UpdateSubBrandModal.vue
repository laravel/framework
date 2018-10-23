<template>
    <form :action="updateBrandUrl" method="POST" accept-charset="utf-8" id="EditBrandForm">
        <div class="modal-body pd-bt-0">
            <div class="form-group">
                <label for="EditBrandName">Name*</label>
                <input type="text" name="EditBrandName" id="EditBrandName" :value="brandData.Name" class="form-control" placeholder="Ex: Greenlam"/>
            </div>
            <div class="form-group">
                <label for="EditBrand">Brand*</label>
                <select name="EditBrand" id="EditBrand" class="form-control">
                    <option value="">Select a Brand</option>
                    <option v-for="brand in brands" :value="brand.Id" :selected="brand.Id==brandData.BrandId">{{ brand.Name }}</option>
                </select>
            </div>
            <div class="form-group">
                <label for="EditBrandDescription">Description</label>
                <input type="text" name="EditBrandDescription" :value="brandData.Description" id="EditBrandDescription" class="form-control" placeholder="Ex: Brand Description"/>
            </div>
            <div class="form-group">
                <label>Status*</label>
                <div class="mr-tp-6">
                    <input type="radio" name="EditBrandStatus" id="EditBrandActive" value="Active" class="input-radio" :checked="brandData.IsActive"/>
                    <label for="EditBrandActive" tabindex="0"></label>
                    <label for="EditBrandActive" class="text-normal cursor-pointer mr-rt-8">Active</label>
                    <input type="radio" name="EditBrandStatus" id="EditBrandInActive" value="Inactive" class="input-radio" :checked="!brandData.IsActive"/>
                    <label for="EditBrandInActive" tabindex="0"></label>
                    <label for="EditBrandInActive" class="text-normal cursor-pointer mr-rt-8">Inactive</label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary pull-left button-custom" id="EditBrandFormSubmitBtn">Save</button>
            <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
        </div>
    </form>
</template>

<script>
    // Child component
    export default {
        props: {
            "update-brand-url": {
                type: String
            },
            "brand-data": {
                type: Object
            },
            "brands": {
                type: Array
            }
        }
    }
</script>