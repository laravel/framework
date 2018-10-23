<template> 
    <div class="modal fade" role="dialog" id="AddColorModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Add Material Pattern</h4>
                </div>
                <form :action="url" method="POST" accept-charset="utf-8" id="AddColorForm">
                    <div class="modal-body pd-bt-0">
                        <div class="form-group">
                            <label for="Name">Pattern*</label>
                            <input type="text" name="Name" id="Name" class="form-control" placeholder="Ex: "/>
                        </div>
                        <div class="form-group">
                            <label for="Description">Description</label>
                            <input type="text" name="Description" id="Description" class="form-control" placeholder="Ex: "/>
                        </div>
                        <div class="form-group">
                            <label for="FormCategory">Form Category*</label>
                            <select name="FormCategory" id="FormCategory" class="form-control">
                                <option value="">Select Category</option>  
                                <option v-for="category in categories" :value="category.Id">{{ category.Name }}</option>
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
                                <label for="Inactive" class="text-normal cursor-pointer mr-rt-8">InActive</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary pull-left button-custom" id="AddColorSubmitBtn">Save</button>
                        <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
                <div class="form-overlay" v-if="loader">
                    <div class="large loader"></div>
                    <div class="loader-text">Saving Pattern</div>
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
            "loader": {
                type: Boolean
            },
            "categories": {
                type: Array
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