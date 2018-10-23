<template>
    <form :action="updateTypeUrl" method="POST" accept-charset="utf-8" id="EditTypeForm">
        <div class="modal-body pd-bt-0">
            <div class="form-group">
                <label for="EditType">Name*</label>
                <input type="text" name="EditType" id="EditType" :value="typeData.Name" class="form-control" placeholder="Ex: Cassette"/>
            </div>
            <div class="form-group">
                <label for="EditDescription">Description</label>
                <input type="text" name="EditDescription" :value="typeData.Description" id="EditDescription" class="form-control" placeholder="Ex: Cassette"/>
            </div>
            <div class="form-group">
                <label for="EditCategory">Category*</label>
                <select name="EditCategory" id="EditCategory" class="form-control" :value="typeData.FormCategoryId">
                    <option value="">Select a Category</option>
                    <option v-for="category in formCategories" :value="category.Id" :selected="category.Id==typeData.FormCategoryId">{{ category.Name }}</option>
                </select>
            </div>
            <div class="form-group">
                <label>Status*</label>
                <div class="mr-tp-6">
                    <input type="radio" name="EditStatus" id="EditTypeActive" value="Active" class="input-radio" :checked="typeData.IsActive"/>
                    <label for="EditTypeActive" tabindex="0"></label>
                    <label for="EditTypeActive" class="text-normal cursor-pointer mr-rt-8">Active</label>
                    <input type="radio" name="EditStatus" id="EditTypeInActive" value="Inactive" class="input-radio" :checked="!typeData.IsActive"/>
                    <label for="EditTypeInActive" tabindex="0"></label>
                    <label for="EditTypeInActive" class="text-normal cursor-pointer mr-rt-8">Inactive</label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary pull-left button-custom" id="EditTypeFormSubmitBtn">Save</button>
            <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
        </div>
    </form>
</template>

<script>
// Child component
export default {
    props: {
        "update-type-url": {
            type: String
        },
        "type-data": {
            type: Object
        },
        "form-categories" : {
            type: Array
        }
    }
}
</script>