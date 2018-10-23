<template> 
    <div class="modal fade" tabindex="-1" role="dialog" id="EditReasonModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Edit Reason</h4>
                </div>
                <form :action="url" method="POST" accept-charset="utf-8" id="EditReasonForm">
                    <div class="modal-body pd-bt-0">
                        <div class="form-group">
                            <label for="EditDescription">Reason*</label>
                            <input type="text" name="Description" id="EditDescription" :value="selectedReason.Reason" class="form-control" placeholder="Ex: Customer Expectations Not Met by HECHPE"/>
                        </div>
                        <div class="form-group">
                            <label for="EditEnquiryStatus">Enquiry Status*</label>
                            <select name="EnquiryStatus" id="EditEnquiryStatus" class="form-control">
                                <option value="">Select Status</option>  
                                <option v-for="status in enquiryStatus" :value="status.Id" :selected="status.Id===selectedReason.EnquiryStatusId">{{ status.Name }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status*</label>
                            <div class="mr-tp-6">
                                <input type="radio" name="Status" id="EditStatusActive" value="Active" class="input-radio" :checked="selectedReason.IsActive"/>
                                <label for="EditStatusActive" tabindex="0"></label>
                                <label for="EditStatusActive" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                <input type="radio" name="Status" id="EditStatusInActive" value="Inactive" class="input-radio" :checked="!selectedReason.IsActive"/>
                                <label for="EditStatusInActive" tabindex="0"></label>
                                <label for="EditStatusInActive" class="text-normal cursor-pointer mr-rt-8">InActive</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary pull-left button-custom" id="EditReasonSubmitBtn">Update</button>
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
    import OverlayNotification from '../../../components/overlayNotification';
    export default {
        props: {
            "url": {
                type: String
            },
            "selected-reason": {
                type: Object
            },
            "loader": {
                type: Boolean
            },
            "enquiry-status": {
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