<div class="box-header">
    <div class="row">
        <div class="col-md-5">
            <h3 class="mr-tp-5 pd-tp-0 room-measure-title">Rooms
                <span data-toggle="tooltip" class="mr-lt-4" title="" data-original-title="Click here to add Room" id="AddRoomBtn" @click="addRoomModal">
                    <i class="fa fa-plus-square" aria-hidden="true"></i>
                </span>
            </h3>
        </div>
    </div>
</div>
<div class="roomdata-box" v-cloak>
    <!-- Legends -->
    <p class="caution measurement-caution" style="text-align: right;">
        <span class="text-center no-text-transform">All measurements are in Inches</span>&nbsp;
        <span class="pipe-color">|</span>&nbsp;
        <i class="fa fa-fw fa-plus-square text-black" aria-hidden="true"></i>&nbsp; 
        <span class="text-center no-text-transform">Add Item</span>&nbsp;
        <span class="pipe-color">|</span>&nbsp;
        <i class="fa fa-pencil-square text-black" aria-hidden="true"></i>&nbsp; 
        <span class="text-center no-text-transform">Edit Item</span>&nbsp;
        <span class="pipe-color">|</span>&nbsp;
        <i class="ion ion-images text-black" aria-hidden="true"></i>&nbsp; 
        <span class="text-center no-text-transform">View Room Attachments</span>&nbsp;
        <span class="pipe-color">|</span>&nbsp;
        <i class="ion ion-clipboard text-black" aria-hidden="true"></i>&nbsp; 
        <span class="text-center no-text-transform">View Notes</span>&nbsp;
        <span class="pipe-color">|</span>&nbsp;
        <i class="fa fa-trash text-black" aria-hidden="true"></i>&nbsp; 
        <span class="text-center no-text-transform">Delete Item</span>&nbsp;
    </p>
    <div class="alert alert-info alert-dismissable" id="NoRoomFoundAlert" v-if="!roomsNotFoundMessageHide">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">×</span>
        </button>
        <p>No Room Measurements found!. Click here to <a @click="addRoomModal" title="Add New Room" class="cursor-pointer">Add New Room</a> or Use above plus (+) icon.</p>
    </div>
    <div class="table-responsive" v-else>
        <table class="table table-striped table-bordered" id="RoomsTable">
            <thead class="bg-light-blue text-center">
                <tr>
                    <th width="25%" class="text-vertical-align text-center">
                        Room <small data-toggle="tooltip" class="cursor-pointer room-legends" data-original-title="W: Width,  L: Length, H: Height">(W × L × H)</small>
                    </th>
                    <th width="20%" class="text-vertical-align text-center">Windows
                        <small data-toggle="tooltip" class="cursor-pointer room-legends" data-original-title="W: Width, H: Height, HFF: Window height from floor">(W × H × HFF)</small>
                    </th>
                    <th width="12%" class="text-vertical-align text-center">Doors
                        <small data-toggle="tooltip" class="cursor-pointer room-legends" data-original-title="W: Width, H: Height">(W × H)</small>
                    </th>
                    <th width="30%" class="text-vertical-align text-center">Furnitures
                        <small data-toggle="tooltip" class="cursor-pointer room-legends" data-original-title="W: Width, H: Height, D: Depth">(W × H × D)</small>
                    </th>
                    <th width="6%" class="text-vertical-align text-center">AC<i class="fa fa-question-circle cursor-help info-icon mr-lt-2" data-toggle="tooltip" data-original-title="Add / Update Air Conditioners here"></i></th>
                    <th width="8%" class="text-vertical-align text-center">FSP<i class="fa fa-question-circle cursor-help info-icon mr-lt-2" data-toggle="tooltip" data-original-title="Add / Update Fire Sprinklers here"></i>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(room, index) in roomsViewData">
                    <td width="25%" class="text-vertical-align pd-tp-0 pd-bt-0">
                        <h5 class="mr-tp-0 mr-bt-0"><b>@{{ room.roomarea }}</b><br>
                            (@{{room.Width}} x @{{room.Length}} x @{{room.Height}})
                            <span class="pull-right">
                                <span data-toggle="tooltip" class="cursor-pointer" title="" data-original-title="Edit Room">
                                    <i class="fa fa-pencil-square" :id="room.id" @click.prevent="editRoomMeasurement($event)" aria-hidden="true"></i>
                                </span>
                                <span data-toggle="tooltip" class="attachment-gallery" title="" data-original-title="View Room Attachments" v-if="room.roomattachments.length != 0">
                                    <a
                                        :href="room.roomattachments[0].src" 
                                        :class="room.roomattachments[0].type" 
                                        @click.prevent="initializeRoomThumbnailsPopup(room.roomattachments)"
                                        >
                                        <i class="ion ion-images gallery-icon"></i>
                                    </a>
                                </span>
                                <span id="RoomNotesSection" class="mr-lt-2" v-if="(room.roomnotes.length > 0 && room.roomnotes[0].Id !== '')">
                                    <a href="javascript:void(0);" data-toggle="tooltip" data-original-title="View notes" @click.prevent="openNotesPopup(index)">
                                        <i class="ion ion-clipboard text-black"></i></a>    
                                    <room-notes :notes="room.roomnotes" :roomno="index"></room-notes>
                                </span>
                                <span data-toggle="tooltip" class="mr-lt-2 cursor-pointer" title="" data-original-title="Remove Room">
                                    <i :id="room.id" class="fa fa-trash" @click.prevent="deleteRoomConfirmation($event)" aria-hidden="true"></i>
                                </span>
                            </span>
                        </h5>
                    </td>
                    <td width="20%" class="text-vertical-align pd-bt-0">
                        <p class="text-center" v-if="room.windows.windows.length === 0"><small>N/A</small></p>
                        <p v-for="(window, windownumber) in room.windows.windows" v-else>
                            <strong>@{{windownumber+1}}: </strong>
                            <span>@{{window.w}} x @{{window.h}} x @{{window.whf}}</span>
                        </p>
                    </td>
                    <td width="12%" class="text-vertical-align pd-bt-0">
                        <p class="text-center" v-if="room.doors.doors.length === 0"><small>N/A</small></p> 
                        <p v-for="(door, doornumber) in room.doors.doors" v-else>
                            <strong>@{{doornumber+1}}: </strong>
                            <span>@{{door.w}} x @{{door.h}}</span>
                        </p>
                    </td>
                    <td width="30%" class="text-vertical-align pd-bt-0 pd-tp-0">
                        <p class="text-center furniture-noa" v-if="_.isEmpty(room.furnitures)"><small>N/A</small></p>
                        <p v-else>
                        <p class="text-center" v-if="room.furnitures.quantity < 1" style="margin-top: -8px;"><small>N/A</small></p>
                        <p v-for="(item, itemnumber) in room.furnitures.furnitures" v-else>
                            <b v-html="getDesignItem(item.item)"></b>
                            <span>@{{item.w}} x @{{item.h}} x @{{item.d}}</span>
                        </p>
                        </p>
                    </td>
                    <td width="6%" class="text-center text-vertical-align pd-tp-0 pd-bt-0">
                        <a id="AddAcBtn" data-toggle="tooltip" data-original-title="Add Ac" v-if="!room.acspecifications" @click="openAddAcModal(room.id)">
                            <i class="fa fa-plus-square" aria-hidden="true"></i>
                        </a>
                        <a v-else>
                            <span data-toggle="tooltip" class="cursor-pointer" title="" data-original-title="Edit Ac">
                                <i class="fa fa-pencil-square" @click.prevent="openEditAcModal(room.id)" aria-hidden="true"></i>
                            </span>
                            <span data-toggle="tooltip" class="mr-lt-3 cursor-pointer" title="" data-original-title="Delete Ac">
                                <i :id="room.id" class="fa fa-trash" @click.prevent="deleteAcConfirmation($event)" aria-hidden="true"></i>
                            </span>
                        </a>
                    </td>
                    <td width="8%" class="text-center text-vertical-align pd-tp-0 pd-bt-0">
                        <a id="AddFireSpBtn" data-toggle="tooltip" data-original-title="Add Fire Sprinkler(s)" v-if="!room.firespspecifications" @click="openAddFireSpModal(room.id)">
                            <i class="fa fa-plus-square mr-tp-1" aria-hidden="true"></i>
                        </a>
                        <a v-else>
                            <span data-toggle="tooltip" class="cursor-pointer" title="" data-original-title="Edit Fire Sprinkler(s)">
                                <i class="fa fa-pencil-square" @click.prevent="openEditFireSpModal(room.id)" aria-hidden="true"></i>
                            </span>
                            <span data-toggle="tooltip" class="mr-lt-3 cursor-pointer" title="" data-original-title="Delete Fire Sprinkler(s)">
                                <i :id="room.id" class="fa fa-trash" @click.prevent="deleteFireSpConfirmation($event)" aria-hidden="true"></i>
                            </span>
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>