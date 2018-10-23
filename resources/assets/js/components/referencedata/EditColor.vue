<template> 
    <div class="modal fade" tabindex="-1" role="dialog" id="EditColorModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Edit Material Color</h4>
                </div>
                <form :action="url" method="POST" accept-charset="utf-8" id="EditColorForm">
                    <div class="modal-body pd-bt-0">
                       <div class="form-group">
                            <label for="Name">Color*</label>
                            <input type="text" name="Name" id="EditName" :value="selectedColor.Name" class="form-control" placeholder="Ex: "/>
                        </div>
                        <div class="form-group">
                            <label for="Description">Description</label>
                            <input type="text" name="Description" id="EditDescription" :value="selectedColor.Description" class="form-control" placeholder="Ex: "/>
                        </div>
                        <div class="form-group">
                            <label for="EditFormCategory">Form Category*</label>
                            <select name="FormCategory" id="EditFormCategory" class="form-control">
                                <option value="">Select Category</option>  
                                <option v-for="category in categories" :value="category.Id" :selected="category.Id===selectedColor.FormCategoryId">{{ category.Name }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status*</label>
                             <div class="mr-tp-6">
                                <input type="radio" name="Status" id="EditStatusActive" value="Active" class="input-radio" :checked="selectedColor.IsActive"/>
                                <label for="EditStatusActive" tabindex="0"></label>
                                <label for="EditStatusActive" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                <input type="radio" name="Status" id="EditStatusInActive" value="Inactive" class="input-radio" :checked="!selectedColor.IsActive"/>
                                <label for="EditStatusInActive" tabindex="0"></label>
                                <label for="EditStatusInActive" class="text-normal cursor-pointer mr-rt-8">InActive</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary pull-left button-custom" id="EditColorSubmitBtn">Update</button>
                        <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
                <div class="form-overlay" v-if="loader">
                    <div class="large loader"></div>
                    <div class="loader-text">Updating Color</div>
                </div>
            </div>
            <overlay-notification :form-over-lay="overlay" :notification-icon="notificationIcon" :notification-message="notificationMessage" @clearmessage="$emit('closeoverlay')" ></overlay-notification>
        </div>
    </div>
</template>
<script>
    // Register Child components
    import OverlayNotification from '../../components/overlayNotification';
    export default {
        props: {
            "url": {
                type: String
            },
            "selected-color": {
                type: Object
            },
            "categories": {
                type: Array
            },
            "loader": {
                type: Boolean
            },
            "overlay": {
                type: Boolean
            },
            "notification-icon": {
                type: String
            },
            "notification-message": {
                type: String
            }
        },
        components: {
            'overlay-notification': OverlayNotification
        }
    }
</script>