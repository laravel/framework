<template>
    <form :action="updatePermissionUrl" method="POST" accept-charset="utf-8" id="EditPermissionForm">
        <div class="modal-body pd-bt-0">
            <div class="form-group">
                <label for="EditPermissionTitle">Title*</label>
                <input type="text" name="EditPermissionTitle" id="EditPermissionTitle" :value="permissionData.Title" class="form-control"/>
            </div>
            <div class="form-group">
                <label for="EditPermissionSlug">Slug*</label>
                <input type="text" name="EditPermissionSlug" id="EditPermissionSlug" :value="permissionData.Slug" class="form-control"/>
            </div>
            <div class="form-group">
                <label for="EditPermissionDescription">Description</label>
                <input type="text" name="EditPermissionDescription" :value="permissionData.Description" id="EditPermissionDescription" class="form-control"/>
            </div>
            <div class="form-group">
                <label>Status*</label>
                <div class="mr-tp-6">
                    <input type="radio" name="EditPermissionStatus" id="EditPermissionActive" value="Active" class="input-radio" :checked="permissionData.IsActive"/>
                    <label for="EditPermissionActive" tabindex="0"></label>
                    <label for="EditPermissionActive" class="text-normal cursor-pointer mr-rt-8">Active</label>
                    <input type="radio" name="EditPermissionStatus" id="EditPermissionInActive" value="Inactive" class="input-radio" :checked="!permissionData.IsActive"/>
                    <label for="EditPermissionInActive" tabindex="0"></label>
                    <label for="EditPermissionInActive" class="text-normal cursor-pointer mr-rt-8">Inactive</label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary pull-left button-custom" id="EditPermissionFormSubmitBtn">Save</button>
            <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
        </div>
    </form>
</template>

<script>
    // Child component
    export default {
        props: {
            "update-permission-url": {
                type: String
            },
            "permission-data": {
                type: Object
            }
        }
    }
</script>