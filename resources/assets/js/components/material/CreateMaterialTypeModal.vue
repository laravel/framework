<template>
    <form :action="$parent.CreateTypeRoute" method="POST" accept-charset="utf-8" id="AddTypeForm">
        <div class="modal-body pd-bt-0">
            <div class="form-group">
                <label for="Name">Name*</label>
                <input type="text" name="Name" id="Name" class="form-control" placeholder="Ex: Cassette"/>
            </div>
            <div class="form-group">
                <label for="Description">Description</label>
                <input type="text" name="Description" id="Description" class="form-control" placeholder="Ex: Cassette"/>
            </div>
            <div class="form-group">
                <label for="Category">Category*</label>
                <select name="Category" id="Category" class="form-control">
                    <option value="">Select a Category</option>  
                    <option v-for="category in $parent.FormCategories" :value="category.Id">{{ category.Name }}</option>
                </select>
            </div>
            <div class="form-group">
                <label>Status*</label>
                <div class="mr-tp-6">
                    <input type="radio" name="Status" id="Active" value="Active" class="input-radio"/>
                    <label for="Active" tabindex="0"></label>
                    <label for="Active" class="text-normal cursor-pointer mr-rt-8">Active</label>
                    <input type="radio" name="Status" id="Inactive" value="Inactive" class="input-radio"/>
                    <label for="Inactive" tabindex="0"></label>
                    <label for="Inactive" class="text-normal cursor-pointer mr-rt-8">Inactive</label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary pull-left button-custom" id="AddTypeFormSubmitBtn">Save</button>
            <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
        </div>
    </form>
</template>