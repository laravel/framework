<div class="modal fade" tabindex="-1" role="dialog" id="DeleteRoomModal" data-deleteroom-url="{{ route("sitemeasurement.room.delete",["siteid" => "", "roomid" => ""]) }}">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title no-text-transform">Confirm</h4>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this Room ?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary pull-left" :data-roomid="RoomMeasurementId" id="DeleteRoomSubmit" @click="deleteRoom">Yes</button>
                <button type="button" class="btn pull-left mr-lt-10" data-dismiss="modal">No</button>
            </div>
            <div id="DeleteRoomNotificationArea" class="hidden">
                <div class="alert alert-dismissible"></div>
            </div>
            <div class="form-overlay hidden" id="DeleteRoomFormOverlay">
                <div class="large loader"></div>
                <div class="loader-text">Adding Room</div>
            </div>
        </div>
    </div>
</div>