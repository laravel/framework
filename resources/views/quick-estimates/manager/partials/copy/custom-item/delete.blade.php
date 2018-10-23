<div id="DeleteCustomItemModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title no-capitalize" id="HeadTitle">Delete confirmation?</h4>
            </div>
            <div class="modal-body">
                Are you sure that you would like to delete <b>@{{ currentCustomItemDescription }}</b> from the custom items?
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary pull-left pd-rt-20 pd-lt-20 mr-rt-8" id="DeleteCustomItemSubmit" @click="deleteCurrentCustomItem">Yes</button>
                <button type="button" class="btn btn-default pull-left pd-rt-20 pd-lt-20" data-dismiss="modal">No</button>
            </div>
        </div>
    </div>
</div>
