<div class="modal fade" role="dialog" id="EditRoomModal">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="{{ route('sitemeasurement.room.update', ["Id" => ""]) }}" method="POST" accept-charset="utf-8" enctype="multipart/form-data" id="EditRoomForm" v-on:submit.prevent>       
                <div class="modal-body">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <div class="row select2-roomarea">
                        <div class="col-md-2">
                            <h4 class="box-title no-text-transform">@{{ currentRoomArea }}</h4>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <p class="caution mr-bt-4">
                                <span>All measurements are in Inches</span> 
                                <span class="pipe-color">|</span>
                                <span>Measurement including frame of the door & frame of the window</span> 
                                <span class="pipe-color">|</span> 
                                <span>Ht:Height, W: Width, H: Height, L: Length, HFF: Window height from floor, D: Depth</span>
                            </p>
                        </div>
                    </div>
                    <div id="EditRoomSection">
                        <div class="row">
                            <div class="col-md-12 table-responsive" id="EditRoomResponsiveTable">
                                <table class="table table-striped table-bordered" id="EditRoomsTable">
                                    <thead style="border-top: 1px solid #f4f4f4" class="text-center">
                                        <tr>
                                            <th class="text-center" style="width: 242px;">Size*</th>
                                            <th class="text-center" style="width: 310px;"><span class="mr-rt-3">No of Windows</span>                         
                                                <select name="EditWindowQuantitySelect" id="EditWindowQuantitySelect" v-model="windowSelected">          
                                                    <option v-for="quantity in windowquanity" v-bind:value="quantity-1">
                                                        @{{ quantity-1 }}
                                                    </option>
                                                </select>
                                            </th>
                                            <th class="text-center" style="width: 218px;"><span class="mr-rt-3">No of Doors</span>
                                                <select name="EditDoorsQuantitySelect" id="EditDoorsQuantitySelect" v-model="doorSelected">          
                                                    <option v-for="door in doorsquantity" v-bind:value="door-1">
                                                        @{{ door-1 }}
                                                    </option>
                                                </select>
                                            </th>
                                            <th class="text-center" style="width: 310px;"><span class="mr-rt-3">No of Furnitures</span>
                                                <select name="EditFurnituresQuantitySelect" id="EditFurnituresQuantitySelect" v-model="furnituresSelected">          
                                                    <option v-for="furniture in furnituresquantity" v-bind:value="furniture-1">
                                                        @{{ furniture-1 }}
                                                    </option>
                                                </select>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <table id="EditRoomsizeTable">
                                                    <tr>
                                                        <td> 
                                                            <strong class="room-input-fontsize">Width</strong>
                                                            <input type="text" name="EditRoomWidth" class="form-control " id="EditRoomWidth" v-model="roomSpecifications.Width" max="999" autocomplete="off" placeholder="W"/>
                                                        </td>

                                                        <td>
                                                            <strong class="room-input-fontsize">Length</strong>
                                                            <input type="text" name="EditRoomLength" class="form-control " id="EditRoomLength"  v-model="roomSpecifications.Length" max="999" autocomplete="off" placeholder="L"/>
                                                        </td>

                                                        <td>
                                                            <strong class="room-input-fontsize">Ceiling Ht</strong>
                                                            <input type="text" name="EditRoomHeight" class="form-control " id="EditRoomHeight"  v-model="roomSpecifications.Height" max="999" autocomplete="off" placeholder="H"/>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td>
                                                <table id="EditwindowsTable">
                                                    <thead></thead>
                                                    <tbody v-if="editRoomWindows.length === 0">
                                                        <tr><td></td><td></td><td width="50%"><small><i>No windows selected.</i></small></td><td></td></tr>
                                                    </tbody>
                                                    <tbody id="EditwindowsTableBody" v-else>
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
                                                        <tr v-for="(window, windownumber) in editRoomWindows">
                                                            <td width="23%">
                                                                <strong class="room-input-fontsize">Window @{{ windownumber + 1 }}</strong>
                                                            </td>

                                                            <td>   
                                                                <input type="text" :name="'EditWindowWidth[' + windownumber + ']'" class="form-control input-sm1" :id="'EditWindowWidth[' + windownumber + ']'" max="999.00" v-model="window.w" autocomplete="off" placeholder="W"/>
                                                            </td>

                                                            <td>  
                                                                <input type="text" :name="'EditWindowHeight[' + windownumber + ']'" class="form-control input-sm1" :id="'EditWindowHeight[' + windownumber + ']'" max="999.00" v-model="window.h" autocomplete="off" placeholder="H"/>
                                                            </td>

                                                            <td> 
                                                                <input type="text" :name="'EditWindowHFF[' + windownumber + ']'" class="form-control input-sm1" :id="'EditWindowHFF[' + windownumber + ']'" max="999.00" v-model="window.whf" autocomplete="off" placeholder="HFF"/>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table> 
                                            </td>
                                            <td>
                                                <table id="EditDoorsTable">
                                                    <thead></thead>
                                                    <tbody v-if="editRoomDoors.length === 0">
                                                        <tr><td></td><td></td><td width="50%"><small><i>No doors selected.</i></small></td><td></td></tr>
                                                    </tbody>
                                                    <tbody id="EditDoorsTableBody" v-else>
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
                                                        <tr v-for="(door, doornumber) in editRoomDoors">
                                                            <td style="width: 23%;">
                                                                <strong class="room-input-fontsize">Door @{{ doornumber + 1 }}</strong>
                                                            </td>

                                                            <td>
                                                                <input type="text" :name="'EditDoorWidth[' + doornumber + ']'" class="form-control input-sm1" :id="'EditDoorWidth[' + doornumber + ']'" max="999.00" v-model="door.w" autocomplete="off" placeholder="W"/>
                                                            </td>

                                                            <td>
                                                                <input type="text" :name="'EditDoorHeight[' + doornumber + ']'" class="form-control input-sm1" :id="'EditDoorHeight[' + doornumber + ']'" max="999.00" v-model="door.h" autocomplete="off" placeholder="H"/>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                            <td>
                                                <table id="EditFurtnitureTable">
                                                    <thead></thead>
                                                    <tbody v-if="editRoomFurnitures.length === 0">
                                                        <tr>
                                                            <td></td><td></td>
                                                            <td width="50%">
                                                                <small><i>No furnitures selected.</i></small>
                                                            </td>
                                                            <td></td>
                                                        </tr>
                                                    </tbody>
                                                    <tbody id="EditFurtnitureTableBody" v-else>
                                                        <tr>
                                                            <td width="23%"></td>

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
                                                        <tr v-for="(furniture, index) in editRoomFurnitures">
                                                            <td width="30%">
                                                                <select :name="'EditDesignItems[' + index + ']'" :id="'EditDesignItems[' + index + ']'" v-model="furniture.item" class="form-control designitem-dropdown pd-0 pd-rt-5" aria-required="true" aria-invalid="false">
                                                                    <option value="">Select Item</option>
                                                                    <option v-for="item in DesignItems" :value="item.Id" :selected="furniture.item == item.Id">@{{ item.Name }}</option>
                                                                </select>
                                                            </td>

                                                            <td>   
                                                                <input type="text" :name="'EditFurnitureWidth[' + index + ']'" class="form-control input-sm1" :id="'EditFurnitureWidth[' + index + ']'" max="999.00" v-model="furniture.w" autocomplete="off" placeholder="W"/>
                                                            </td>

                                                            <td>  
                                                                <input type="text" :name="'EditFurnitureHeight[' + index + ']'" class="form-control input-sm1" :id="'EditFurnitureHeight[' + index + ']'" max="999.00" v-model="furniture.h" autocomplete="off" placeholder="H"/>
                                                            </td>

                                                            <td> 
                                                                <input type="text" :name="'EditFurnitureDepth[' + index + ']'" class="form-control input-sm1" :id="'EditFurnitureDepth[' + index + ']'" max="999.00" v-model="furniture.d" autocomplete="off" placeholder="D"/>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table> 
                                            </td>
                                        </tr>  
                                    </tbody>
                                </table>
                                <p class="text-right" style="margin-top: -13px;">
                                    <small><b>Note:</b></small>
                                    <small class="text-info">Only add furniture which is affecting the paintable area</small>
                                </p>
                            </div>
                        </div>
                        <div class="row ">
                            <div class="col-md-4">
                                <div class="form-group mr-bt-0">
                                    <div class="input-group" data-toggle="tooltip" title="Click here to upload files">
                                        <label for="UploadEditRoomPics" class="cursor-pointer">Upload Room Pictures</label>
                                        <label class="input-group-addon cursor-pointer upload-addon" for="UploadEditRoomPics">
                                            <i class="fa fa-paperclip"></i>
                                        </label>
                                    </div>
                                    <input type="file" name='UploadEditRoomPics[]' @change.prevent="onUploadChange($event, totalEditRoomAttachments, newEditRoomAttachments)" class="hidden" accept="image/*" id="UploadEditRoomPics" multiple="multiple" class="form-control"/>
                                </div>
                            </div>
                        </div>
                        <div class="row ">
                            <div class="col-md-12">
                                <edit-room-attachments :edit-room-attachments-list="totalEditRoomAttachments" v-show="showEditRoomAttachmentsBlock" @deleteroomattachment="deleteFiles"></edit-room-attachments>
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
                        <div class="row" v-for="(Block, blockNo) in roomMeasurementNotes">
                            <div class="col-md-3">
                                <div class="form-group">                          
                                    <select :name="'EditNoteCategory[' + blockNo + ']'" :id="'EditNoteCategory[' + blockNo + ']'" class="form-control editroom-note-category" v-model="Block.Id">          
                                        <option value="" v-if="Block.Id === ''">Select Category</option>
                                        <option v-for="category in editRoomNoteCategories" :value="category.Id" :selected="category.Id==Block.Id" v-if="checkEditRoomNoteCategoryConditions(blockNo, category)">
                                            @{{ category.Name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <input type="text" :name="'EditNoteDescription['+ blockNo + ']'" :id="'EditNoteDescription['+ blockNo + ']'" v-model="Block.description" class="form-control note-description" placeholder="Ex: 24 Inch Depth Plywood Box with Shutters"/>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <input type="file" :name="'EditUploadNoteAttachmnt_' + blockNo + '[]'" :id="'EditUploadNoteAttachmnt_' + blockNo" class="form-control editroom-notes-upload" accept="image/*" multiple="multiple"/>
                                </div>
                            </div>
                            <div class="col-md-1 mr-tp-5 pd-lt-0 add-notes-icons" v-if="blockNo === 0">
                                <span data-toggle="tooltip" id="EditRoomAddCategoryIcon" @click.prevent="addMoreNotes" class="edit-room-add-category-icon" :class="{ hidden: showEditRoomAddNoteIcon}" title="" data-original-title="Add more notes"><i class="fa fa-plus-square" aria-hidden="true"></i></span>
                                <span data-toggle="tooltip" id="EditRoomRemoveCategoryIcon"  :class="{ hidden: isEditRoomNoteRemoveIconHide }" class="edit-room-remove-category-icon" @click.prevent="removeEditRoomNotes" title="" data-original-title="Remove"><i class="fa fa-trash" aria-hidden="true"></i></span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 text-center">
                                <p>
                                    <input type="submit" name="EditRoomFormSubmit" value="Update" class="btn btn-primary button-custom" id="EditRoomFormSubmit" />
                                    <input type="button" name="EditRoomCancelBtn" value="Cancel" @click.prevent="closeEditRoomModal" class="btn button-custom" id="EditRoomCancelBtn"/>
                                </p>
                            </div>
                        </div>
                        <div id="EditRoomNotificationArea" class="hidden">
                            <div class="alert alert-dismissible"></div>
                        </div>
                        <small>* N/A: Data not available</small>  
                    </div>
                </div>
            </form>
            <div class="form-overlay hidden" id="EditRoomMFormOverlay">
                <div class="large loader"></div>
                <div class="loader-text">Updating Room</div>
            </div>
        </div>
    </div>
</div>