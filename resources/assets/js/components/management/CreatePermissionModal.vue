<template>
    <form  method="POST" :action="$parent.StorePermissionUrl" enctype="multipart/form-data" id="AddPermissionForm">
        <div class="modal-body pd-bt-0">
            <div class="form-group">
                <label for="Title">Title*</label>
                <input type="text" name="Title" id="Title" class="form-control" />
            </div>
            <div class="form-group">
                <label for="Slug">Slug*</label>
                <input type="text" name="Slug" id="Slug" class="form-control" />
            </div>
            <div class="form-group">
                <label for="Description">Description</label>
                <input type="text" name="Description" id="Description" class="form-control"/>
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
            <button type="submit" class="btn btn-primary pull-left button-custom" id="AddPermissionFormSubmitBtn">Save</button>
            <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
        </div>
    </form>
</template>