<div class="modal fade" role="dialog" id="EditAcModal">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">       
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">AC Position Details</h4>
            </div>
            <div class="modal-body pd-tp-2">
                <form action="{{ route('sitemeasurement.room.ac.update') }}" method="POST" accept-charset="utf-8" enctype="multipart/form-data" id="EditAcForm">  
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="EditWallDirection">Wall* <i class="fa fa-question-circle cursor-help help-icon" data-toggle="tooltip" data-original-title="Specify wall direction"></i></label>
                                <select class="form-control" name="EditWallDirection" id="EditWallDirection" :value="filteredAcData.WallDirection">
                                    <option value="">Select Wall</option>
                                    <option value="North">North</option>
                                    <option value="East">East</option>
                                    <option value="South">South</option>
                                    <option value="West">West</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="form-group mr-bt-0">
                                <div class="input-group" data-toggle="tooltip" title="Click here to upload files">
                                    <label for="EditWallDirectionAttachments" class="cursor-pointer">Attachments*</label> 
                                    <label class="input-group-addon cursor-pointer upload-addon" for="EditWallDirectionAttachments">
                                        <i class="fa fa-paperclip"></i>
                                    </label>
                                </div>
                                <input type="file" name='EditWallDirectionAttachments[]' @change.prevent="onUploadChange($event, TotalEditAcAttachments, NewEditAcAttachments)" class="hidden" accept="image/*" id="EditWallDirectionAttachments" multiple="multiple" class="form-control"/>
                                <div>
                                    <edit-ac-attachments :edit-ac-attachments-list="TotalEditAcAttachments" v-show="ShowEditAcAttachmentsBlock" @deleteacattachment="deleteFiles"></edit-ac-attachments>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="EditAcTable">
                                    <caption><h4 style="color:#111;" class="mr-tp-0">Indoor Unit</h4></caption>
                                    <thead style="border-top: 1px solid #f4f4f4;">
                                    <th class="text-vertical-align" width="25%"></th>
                                    <th class="text-center text-vertical-align" width="25%">Available</th>
                                    <th class="text-center text-vertical-align" width="25%">PFC
                                        <i class="fa fa-question-circle cursor-help help-icon mr-lt-2" data-toggle="tooltip" data-original-title="Position from the Ceiling"></i>
                                    </th>
                                    <th class="text-center text-vertical-align" width="25%">PFLW
                                        <i class="fa fa-question-circle cursor-help help-icon mr-lt-2" data-toggle="tooltip" data-original-title="Position from left side Wall"></i>
                                    </th>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="text-vertical-align">Power Point</td>
                                            <td class="text-center text-vertical-align">
                                                <input type="checkbox" name="EditPowerPoint" id="EditPowerPoint" class="checkbox" value="yes" v-model="filteredAcData.PowerPoint.IsAvailable" @change="initializePowerPointCheckbox"/>
                                                <label for="EditPowerPoint" tabindex="0"></label>
                                                <label for="EditPowerPoint" class="text-normal cursor-pointer"></label>
                                            </td>
                                            <td class="text-center text-vertical-align">
                                                <input type="text" class="form-control edit-power-point" name="EditPowerPointPFC" id="EditPowerPointPFC" :value="filteredAcData.PowerPoint.PFC" :disabled="!filteredAcData.PowerPoint.IsAvailable">
                                            </td>
                                            <td class="text-center text-vertical-align">
                                                <input type="text" class="form-control edit-power-point" name="EditPowerPointPFL" id="EditPowerPointPFL" :value="filteredAcData.PowerPoint.PFLW" :disabled="!filteredAcData.PowerPoint.IsAvailable">
                                            </td>
                                        </tr>  
                                        <tr>
                                            <td class="text-vertical-align">Drainage Point</td>
                                            <td class="text-center text-vertical-align">
                                                <input type="checkbox" name="EditDrainagePoint" id="EditDrainagePoint" class="checkbox" value="yes" v-model="filteredAcData.DrainagePoint.IsAvailable" @change="initializeDrainagePointCheckbox"/>
                                                <label for="EditDrainagePoint" tabindex="0"></label>
                                                <label for="EditDrainagePoint" class="text-normal cursor-pointer"></label>
                                            </td>
                                            <td class="text-center text-vertical-align">
                                                <input type="text" class="form-control edit-drainage-point" name="EditDrainagePointPFC" id="EditDrainagePointPFC" :value="filteredAcData.DrainagePoint.PFC" :disabled="!filteredAcData.DrainagePoint.IsAvailable">
                                            </td>
                                            <td class="text-center text-vertical-align">
                                                <input type="text" class="form-control edit-drainage-point" name="EditDrainagePointPFL" id="EditDrainagePointPFL" :value="filteredAcData.DrainagePoint.PFLW" :disabled="!filteredAcData.DrainagePoint.IsAvailable">
                                            </td>
                                        </tr>  
                                        <tr>
                                            <td class="text-vertical-align">Core Cutting</td>
                                            <td class="text-center text-vertical-align">
                                                <input type="checkbox" name="EditCoreCutting" id="EditCoreCutting" class="checkbox" value="yes" v-model="filteredAcData.CoreCutting.IsAvailable" @change="initializeCoreCuttingCheckbox"/>
                                                <label for="EditCoreCutting" tabindex="0"></label>
                                                <label for="EditCoreCutting" class="text-normal cursor-pointer"></label>
                                            </td>
                                            <td class="text-center text-vertical-align">
                                                <input type="text" class="form-control edit-core-cutting" name="EditCoreCuttingPFC" id="EditCoreCuttingPFC" :value="filteredAcData.CoreCutting.PFC" :disabled="!filteredAcData.CoreCutting.IsAvailable">
                                            </td>
                                            <td class="text-center text-vertical-align">
                                                <input type="text" class="form-control edit-core-cutting" name="EditCoreCuttingPFL" id="EditCoreCuttingPFL" :value="filteredAcData.CoreCutting.PFLW" :disabled="!filteredAcData.CoreCutting.IsAvailable">
                                            </td>
                                        </tr>  
                                        <tr>
                                            <td class="text-vertical-align">Copper Piping</td>
                                            <td class="text-center text-vertical-align">
                                                <input type="checkbox" name="EditCopperCutting" id="EditCopperCutting" class="checkbox" value="yes" v-model="filteredAcData.CopperCutting.IsAvailable" @change="initializeCopperPipingCheckbox"/>
                                                <label for="EditCopperCutting" tabindex="0"></label>
                                                <label for="EditCopperCutting" class="text-normal cursor-pointer"></label>
                                            </td>
                                            <td class="text-center text-vertical-align">
                                                <input type="text" class="form-control edit-copper-cutting" name="EditCopperCuttingPFC" id="EditCopperCuttingPFC" :value="filteredAcData.CopperCutting.PFC" :disabled="!filteredAcData.CopperCutting.IsAvailable">
                                            </td>
                                            <td class="text-center text-vertical-align">
                                                <input type="text" class="form-control edit-copper-cutting" name="EditCopperCuttingPFL" id="EditCopperCuttingPFL" :value="filteredAcData.CopperCutting.PFLW" :disabled="!filteredAcData.CopperCutting.IsAvailable">
                                            </td>
                                        </tr>  
                                    </tbody>
                                </table> 
                            </div>
                        </div>
                    </div>
                    <h4 style="color:#111;">Outdoor Unit</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group"> 
                                <label>Notes</label>
                                <textarea rows="2" class="form-control no-resize-input" name="EditOutDoorUnitLocation" id="EditOutDoorUnitLocation" :value="filteredAcData.OutdoorUnit.Notes"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group"> 
                                <label>Attachments</label>
                                <input type="file" name="EditOurDoorUnitAttachment[]" id="EditOurDoorUnitAttachment" class="form-control hidden fileupload" accept="image/*" multiple="multiple"/>
                                <button type="button" class="btn btn-box-tool pd-0 btn-attachmentupload text-blue" data-toggle="tooltip" title="" data-widget="chat-pane-toggle" data-original-title="Add Attachments">
                                    <i class="fa fa-paperclip"></i>
                                </button>
                                <i class="fa fa-question-circle cursor-help help-icon mr-lt-2" data-toggle="tooltip" data-original-title="Upload new attachments will overwrite old ones"></i>
                                <p v-if="filteredAcData.OutdoorUnit.Attachments">
                                    <span v-for="(file, index) in JSON.parse(filteredAcData.OutdoorUnit.Attachments)">
                                        <img :src="CdnUrl+file.Path" class="img-thumbnail mr-rt-4"  :title="file.UserFileName" :alt="file.UserFileName"> 
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <p>
                                <input type="submit" name="EditAcFormSubmit" value="Update" class="btn btn-primary button-custom" id="EditAcFormSubmit" />
                                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                            </p>
                        </div>
                    </div>
                    <div id="EditAcNotificationArea" class="hidden">
                        <div class="alert alert-dismissible"></div>
                    </div>
                    <small>* N/A: Data not available</small> 
                </form>
            </div>
            <div class="form-overlay hidden" id="EditAcFormOverlay">
                <div class="large loader"></div>
                <div class="loader-text">Updating AC</div>
            </div>
        </div>
    </div>
</div>