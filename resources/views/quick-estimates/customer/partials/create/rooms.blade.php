<div id="ChangeRoomsModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document" style="width:960px">
        <form id="ChangeRoomsForm" method="GET" action="{{ $changeRoomsRoute }}">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title no-capitalize">
                        <i class="fa fa-pencil-square-o" aria-hidden="true"></i>
                        <span class="mr-lt-3">Change Rooms</span>
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12" v-if="tempSelectedRooms.length > 0">
                            <div class="callout callout-info mr-tp-5 mr-bt-15">
                                <i class="fa fa-lightbulb-o" aria-hidden="true"></i>
                                <span class="mr-lt-6">Please select the rooms for which you want to do interiors.</span>
                            </div>
                        </div>
                        <div class="col-md-12" v-else>
                            <div class="callout callout-danger mr-tp-5 mr-bt-15">
                                <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
                                <span class="mr-lt-6">Please select atleast one room for which you want to do interiors.</span>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label>Rooms*</label>
                            <span class="mr-lt-16">
                                <span class="rooms-count" v-if="tempSelectedRooms.length == 0">No rooms selected.</span>
                                <span class="rooms-count" v-else-if="tempSelectedRooms.length == 1">1 room selected.</span>
                                <span class="rooms-count" v-else>@{{ tempSelectedRooms.length }} rooms selected.</span>
                            </span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <template v-for="room in rooms">
                                <input
                                    type="checkbox"
                                    name="Rooms[]"
                                    :value="room.id"
                                    v-model="tempSelectedRooms"
                                    :id="createUpdateRoomName(room.id)"
                                    class="input-checkbox-tile room hidden"
                                />
                                <label
                                    :for="createUpdateRoomName(room.id)"
                                    class="pd-lt-6 pd-tp-6 text-hover"
                                >@{{ room.name }}</label>
                            </template>
                        </div>
                        <div class="col-md-12">
                            <div class="callout callout-warning mr-tp-15 mr-bt-0">
                                <i class="fa fa-warning" aria-hidden="true"></i>
                                <span class="mr-lt-6">If you <u>remove/de-select a room</u>, you <u>might lose your item selections of that room</u> when you click change.</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group mr-bt-0 pull-left">
                                <button type="submit" id="ChangeRoomsFormSubmit" class="btn btn-primary button-custom" :disabled="tempSelectedRooms.length == 0">Change</button>
                                <button type="reset" id="ChangeRoomsFormReset" class="btn btn-default">Undo Changes</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-overlay hidden" id="ChangeRoomsFormOverlay">
                    <div class="large loader"></div>
                    <div class="loader-text">Fetching Items...</div>
                </div>
            </div>
        </form>
        <div id="ChangeRoomsFormNotificationArea"></div>
    </div>
</div>
