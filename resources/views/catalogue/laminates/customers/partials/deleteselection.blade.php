<div class="modal fade" tabindex="-1" role="dialog" id="DeleteCombModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title no-text-transform">Confirm</h4>
            </div>
            <div class="modal-body">
                Do you want to delete this Selection?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary pull-left" id="DeleteComSubmit" @click="deleteCombination(SelectedCombinationId)">Yes</button>
                <button type="button" class="btn pull-left mr-lt-10" data-dismiss="modal">No</button>
            </div>
            <div id="DeleteCombNotificationArea" class="hidden">
                <div class="alert alert-dismissible"></div>
            </div>
            <div class="form-overlay" id="DeleteCombinationFormOverlay" v-if="ShowDeleteComLoader">
                <div class="large loader"></div>
                <div class="loader-text">Deleting Combination</div>
            </div>
        </div>
    </div>
</div>