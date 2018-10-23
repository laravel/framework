<template> 
    <div class="modal fade" tabindex="-1" role="dialog" id="EditRangeModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Edit Price Range</h4>
                </div>
                <form :action="url" method="POST" accept-charset="utf-8" id="EditRangeForm">
                    <div class="modal-body pd-bt-0">
                       <div class="form-group">
                            <label for="Name">Name*</label>
                            <input type="text" name="Name" id="EditName" :value="selectedRange.Name" class="form-control" placeholder="Ex: "/>
                        </div>
                        <div class="form-group">
                            <label for="Description">Description</label>
                            <input type="text" name="Description" id="EditDescription" :value="selectedRange.Description" class="form-control" placeholder="Ex: "/>
                        </div>
                        <div class="form-group">
                            <label>Status*</label>
                             <div class="mr-tp-6">
                                <input type="radio" name="Status" id="EditStatusActive" value="Active" class="input-radio" :checked="selectedRange.IsActive"/>
                                <label for="EditStatusActive" tabindex="0"></label>
                                <label for="EditStatusActive" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                <input type="radio" name="Status" id="EditStatusInActive" value="Inactive" class="input-radio" :checked="!selectedRange.IsActive"/>
                                <label for="EditStatusInActive" tabindex="0"></label>
                                <label for="EditStatusInActive" class="text-normal cursor-pointer mr-rt-8">InActive</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary pull-left button-custom" id="EditRangeSubmitBtn">Update</button>
                        <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
                <div class="form-overlay" v-if="loader">
                    <div class="large loader"></div>
                    <div class="loader-text">Updating Reason</div>
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
            "selected-range": {
                type: Object
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