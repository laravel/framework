<div class="modal fade" tabindex="-1" role="dialog" id="DeleteAcModal" data-deleteac-url="{{ route("sitemeasurement.room.ac.delete",["siteid" => "", "roomid" => ""]) }}">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title no-text-transform">Confirm</h4>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this AC ?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary pull-left" name="DeleteAcSubmit" id="DeleteAcSubmit" @click="deleteAc">Yes</button>
                <button type="button" class="btn pull-left mr-lt-10" data-dismiss="modal">No</button>
            </div>
            <div id="DeleteAcNotificationArea" class="hidden">
                <div class="alert alert-dismissible"></div>
            </div>
            <div class="form-overlay hidden" id="DeleteAcFormOverlay">
                <div class="large loader"></div>
                <div class="loader-text">Deleting AC Data</div>
            </div>
        </div>
    </div>
</div>