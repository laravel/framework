<div class="modal fade" role="dialog" id="AddRoomModal">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header pd-bt-0">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="Room">Room*</label>
                            <select name="Room" id="Room" class="form-control"> 
                                <option value="">Select a Room</option>
                                <optgroup label="Rooms from Quick Estimation">
                                    <option value="" v-if="QERooms.length === 0"><small>N/A</small>
                                </option>   
                                <option v-for="qeroom in QERooms" v-bind:value="qeroom.Id" v-else>
                                    @{{ qeroom.Name }}
                                </option>   
                                </optgroup>
                                <optgroup label="Other Rooms">
                                    <option value="" v-if="rooms.length === 0"><small>N/A</small>
                                </option>   
                                <option v-for="room in rooms"  v-bind:value="room.Id" v-else>
                                    @{{ room.Name }}
                                </option>
                                </optgroup>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <form action="{{ route('sitemeasurement.room.store') }}" method="POST" accept-charset="utf-8" enctype="multipart/form-data" id="AddRoomForm">       
                <div class="modal-body">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <div id="AddRoomSection" :class="{ hidden: isRoomBlockHide }">
                        <div class="row">
                            <div class="col-md-12 text-right" style="margin-top: -20px;">
                                <p class="caution mr-bt-4">
                                    <span>All measurements are in Inches</span> 
                                    <span class="pipe-color">|</span>
                                    <span>Measurement including frame of the door & frame of the window</span> 
                                    <span class="pipe-color">|</span> 
                                    <span>Ht:Height, W: Width, H: Height, L: Length, HFF: Window height from floor, D: Depth</span>
                                </p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 table-responsive" id="AddRoomTableResponsive">
                                <table class="table table-striped table-bordered" id="AddEditRoomsTable">
                                    <thead style="border-top: 1px solid #f4f4f4" class="text-center">
                                        <tr>
                                            <th width="20%" class="text-center" style="width: 242px;">Size*</th>
                                            <th width="25%" class="text-center" style="width: 310px;"><span class="mr-rt-3">No of Windows</span>                         
                                                <select name="WindowQnty" id="WindowQnty" v-model="defaultWindowQuantity">          
                                                    <option v-for="quantity in windowquanity" v-bind:value="quantity-1">
                                                        @{{ quantity-1 }}
                                                    </option>
                                                </select>
                                            </th>
                                            <th width="25%" class="text-center" style="width: 218px;"><span class="mr-rt-3">No of Doors</span>
                                                <select name="DoorQnty" id="DoorQnty" v-model="defaultDoorQuantity">          
                                                    <option v-for="door in doorsquantity" v-bind:value="door-1">
                                                        @{{ door-1}}
                                                    </option>
                                                </select> 
                                            </th>
                                            <th width="30%" class="text-center" style="width: 310px;"><span class="mr-rt-3">No of Furnitures</span>
                                                <select name="FurntQnty" id="FurntQnty" v-model="defaultFurnitureQnty">          
                                                    <option v-for="item in furnituresquantity" v-bind:value="item-1">
                                                        @{{ item-1}}
                                                    </option>
                                                </select> 
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <table id="RoomsizeTable">
                                                    <tr>
                                                        <td> 
                                                            <strong class="room-input-fontsize">Width</strong>
                                                            <input type="text" name="RoomWidth" class="form-control " id="RoomWidth" max="999.00" autocomplete="off" placeholder="W"/>
                                                        </td>

                                                        <td>
                                                            <strong class="room-input-fontsize">Length</strong> 
                                                            <input type="text" name="RoomLength" class="form-control " id="RoomLength" max="999.00" autocomplete="off" placeholder="L"/>
                                                        </td>

                                                        <td>
                                                            <strong class="room-input-fontsize">Ceiling Ht</strong> 
                                                            <input type="text" name="RoomHeight" class="form-control " id="RoomHeight" max="999.00" autocomplete="off" placeholder="H"/>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td>
                                                <table id="windowsTable">
                                                    <tbody v-if="windowQuantity < 1">
                                                        <tr><td></td><td></td><td width="50%"><small><i>No windows selected.</i></small></td><td></td></tr>
                                                    </tbody>
                                                    <tbody id="windowsTableBody" v-else>
                                                        <tr>
                                                            <td width="23%"></td>

                                                            <td>
                                                                <strong class="room-input-fontsize">Width</strong>     
                                                            </td>

                                                            <td>
                                                                <strong class="room-input-fontsize">Height</strong>      
                                                            </td>

                                                            <td>
                                                                <strong class="room-input-fontsize">HFF</strong>
                                                            </td>

                                                        </tr> 
                                                        <tr v-for="window in windowQuantity">
                                                            <td width="23%">
                                                                <strong class="room-input-fontsize">Window @{{ window }}</strong>
                                                            </td>

                                                            <td>
                                                                <input type="text" :name="'WindowWidth[' + (window-1) + ']'" class="form-control input-sm" :id="'WindowWidth[' + (window-1) + ']'" max="999.00" autocomplete="off" placeholder="W"/>
                                                            </td>

                                                            <td>
                                                                <input type="text" :name="'WindowHeight[' + (window-1) + ']'" class="form-control input-sm" :id="'WindowHeight[' + (window-1) + ']'" max="999.00" autocomplete="off" placeholder="H"/>
                                                            </td>

                                                            <td>
                                                                <input type="text" :name="'WindowHFF[' + (window-1) + ']'" class="form-control input-sm" :id="'WindowHFF[' + (window-1) + ']'" max="999.00" autocomplete="off" placeholder="HFF"/>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                            <td>
                                                <table id="DoorsTable">
                                                    <tbody v-if="doorQuantity < 1">
                                                        <tr><td></td><td width="50%"><small><i>No doors selected.</i></small></td><td></td></tr>
                                                    </tbody>
                                                    <tbody v-else>
                                                        <tr>
                                                            <td style="width: 23%;">
                                                                <strong class="room-input-fontsize"></strong>
                                                            </td>

                                                            <td>
                                                                <strong class="room-input-fontsize">Width</strong>         
                                                            </td>

                                                            <td>
                                                                <strong class="room-input-fontsize">Height</strong>
                                                            </td>
                                                        </tr>
                                                        <tr v-for="door in doorQuantity">
                                                            <td style="width: 23%;">
                                                                <b>Door @{{ door }}</b>
                                                            </td>

                                                            <td>
                                                                <input type="text" :name="'DoorWidth[' + (door-1) + ']'" class="form-control input-sm" :id="'DoorWidth[' + (door-1) + ']'" max="999.00" autocomplete="off" placeholder="W"/>
                                                            </td>

                                                            <td>
                                                                <input type="text" :name="'DoorHeight[' + (door-1) + ']'" class="form-control input-sm" :id="'DoorHeight[' + (door-1) + ']'" max="999.00" autocomplete="off" placeholder="H"/>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                            <td>
                                                <table id="FurnituresTable">
                                                    <tbody v-if="totalSelectedFurnitures < 1">
                                                        <tr>
                                                            <td></td>
                                                            <td width="50%">
                                                                <small><i>No furnitures selected.</i></small>
                                                            </td>
                                                            <td></td>
                                                        </tr>
                                                    </tbody>
                                                    <tbody v-else>
                                                        <tr>
                                                            <td width="30%">

                                                            </td>
                                                            <td>
                                                                <strong class="room-input-fontsize">Width</strong>     
                                                            </td>

                                                            <td>
                                                                <strong class="room-input-fontsize">Height</strong>      
                                                            </td>

                                                            <td>
                                                                <strong class="room-input-fontsize">Depth</strong>
                                                            </td>
                                                        </tr> 
                                                        <tr v-for="furniture in totalSelectedFurnitures">
                                                            <td width="30%">
                                                                <select :name="'DesignItems[' + (furniture-1) + ']'" :id="'DesignItems[' + (furniture-1) + ']'" class="form-control designitem-dropdown pd-0 pd-rt-5" aria-required="true" aria-invalid="false">
                                                                    <option value="">Select Item</option>
                                                                    <option v-for="item in DesignItems" :value="item.Id">@{{ item.Name }}</option>
                                                                </select>
                                                            </td>

                                                            <td>
                                                                <input type="text" :name="'FurnWidth[' + (furniture-1) + ']'" class="form-control input-sm" :id="'FurnWidth[' + (furniture-1) + ']'" max="999.00" autocomplete="off" placeholder="W"/>
                                                            </td>

                                                            <td>
                                                                <input type="text" :name="'FurnHeight[' + (furniture-1) + ']'" class="form-control input-sm" :id="'FurnHeight[' + (furniture-1) + ']'" max="999.00" autocomplete="off" placeholder="H"/>
                                                            </td>

                                                            <td>
                                                                <input type="text" :name="'FurnDepth[' + (furniture-1) + ']'" class="form-control input-sm" :id="'FurnDepth[' + (furniture-1) + ']'" max="999.00" autocomplete="off" placeholder="D"/>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>  
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <p class="text-right">
                            <small><b>Note:</b></small>
                            <small class="text-info">Only add furniture which is affecting the paintable area</small>
                        </p>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mr-bt-0">
                                    <div class="input-group" data-toggle="tooltip" title="Click here to upload files">
                                        <label for="UploadRoomPics" class="cursor-pointer">Upload Room Pictures</label> 
                                        <label class="input-group-addon cursor-pointer upload-addon" for="UploadRoomPics">
                                            <i class="fa fa-paperclip"></i>
                                        </label>
                                    </div>
                                    <input type="file" name='UploadRoomPics[]' @change.prevent="onRoomUploadChange($event, totalRoomAttachments)" class="hidden" accept="image/*" id="UploadRoomPics" multiple="multiple" class="form-control"/>
                                </div>
                            </div>
                        </div>
                        <div class="row" :class="{hidden : totalRoomAttachments.length === 0  }">
                            <div class="col-md-12">
                                <room-attachments :room-attachments-list="totalRoomAttachments" v-show="showRoomsAttachmentsBlock" @deleteroomattachment="deleteRoomFile"></room-attachments>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-1">
                                <div class="form-group mr-bt-5"> 
                                    <label></label>
                                    <b>Notes</b>
                                </div>
                            </div>
                        </div>
                        <div class="row" v-for="(block, blockNumber) in notesCategoryblocks">
                            <div class="col-md-3">
                                <div class="form-group">                          
                                    <select :name="'NoteCategory[' + block + ']'" :id="'NoteCategory[' + block + ']'" class="form-control note-category">          
                                        <option value="">Select Category</option>
                                        <option v-for="category in filteredNoteCategories" :value="category.Id" v-if="checkNoteCategoryConditions(block, category)">
                                            @{{ category.Name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <input type="text" :name="'NoteDescription['+ block + ']'" :id="'NoteDescription['+ block + ']'" class="form-control note-description" placeholder="Ex: 24 Inch Depth Plywood Box with Shutters"/>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <input type="file" :name="'UploadNoteAttachmnts_' + block + '[]'" :id="'UploadNoteAttachmnts_' + block" class="form-control notes-upload"  accept="image/*" multiple="multiple"/>
                                </div>
                            </div>
                            <div class="col-md-1 mr-tp-5 pd-lt-0 add-notes-icons" v-if="blockNumber === 0">
                                <span data-toggle="tooltip" id="AddCategoryIcon" @click.prevent="addNotesBlock" class="add-category-icon" :class="{ hidden: isAddNoteIconHide}" title="" data-original-title="Add more notes"><i class="fa fa-plus-square" aria-hidden="true"></i></span>
                                <span data-toggle="tooltip" id="RemoveCategoryIcon" class="remove-category-icon" @click.prevent="removeNotesBlock()" title="" data-original-title="Remove"><i class="fa fa-trash" aria-hidden="true"></i></span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 text-center">
                                <p>
                                    <input type="submit" name="AddRoomFormSubmit" value="Save" class="btn btn-primary button-custom" id="AddRoomFormSubmit" />
                                    <input type="button" name="CancelBtn" value="Cancel" class="btn button-custom" id="CancelBtn" @click="closeAddRoomModal"/>
                                </p>
                            </div>
                        </div>
                        <div id="AddRoomNotificationArea" class="hidden">
                            <div class="alert alert-dismissible"></div>
                        </div>
                        <small>* N/A: Data not available</small>     
                    </div>
                </div>
            </form>
            <div class="form-overlay hidden" id="AddRoomFormOverlay">
                <div class="large loader"></div>
                <div class="loader-text">Adding Room</div>
            </div>
        </div>
    </div>
</div>