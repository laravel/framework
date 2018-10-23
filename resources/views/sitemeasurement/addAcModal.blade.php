<div class="modal fade" role="dialog" id="AddAcModal">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">AC Position Details</h4>
            </div>
            <form action="{{ route('sitemeasurement.room.ac.store') }}" method="POST" accept-charset="utf-8" enctype="multipart/form-data" id="AddAcForm">       
                <div class="modal-body pd-tp-2">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="WallDirection">Wall* <i class="fa fa-question-circle cursor-help help-icon" data-toggle="tooltip" data-original-title="Specify wall direction"></i></label>
                                <select class="form-control" name="WallDirection" id="WallDirection">
                                    <option value="">Select Wall</option>
                                    <option value="North">North</option>
                                    <option value="East">East</option>
                                    <option value="South">South</option>
                                    <option value="West">West</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="form-group mr-bt-0 wall-attachments">
                                <div class="input-group" data-toggle="tooltip" title="Click here to upload files">
                                    <label for="WallDirectionAttachments" class="cursor-pointer">Attachments*</label> 
                                    <label class="input-group-addon cursor-pointer upload-addon" for="WallDirectionAttachments">
                                        <i class="fa fa-paperclip"></i>
                                    </label>
                                </div>
                                <input type="file" name='WallDirectionAttachments[]' @change.prevent="onRoomUploadChange($event, TotalAcAttachments)" class="hidden" accept="image/*" id="WallDirectionAttachments" multiple="multiple" class="form-control"/>
                                <div>   
                                    <ac-attachments :ac-attachments-list="TotalAcAttachments" @deleteacattachment="deleteRoomFile"></ac-attachments>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="AddAcTable">
                                    <caption>
                                        <h4 style="color:#111;" class="mr-tp-0">Indoor Unit</h4>
                                    </caption>
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
                                                <input type="checkbox" name="PowerPoint" id="PowerPoint" class="checkbox" value="yes" v-model="IsPowerPointAvailable"/>
                                                <label for="PowerPoint" tabindex="0"></label>
                                                <label for="PowerPoint" class="text-normal cursor-pointer"></label>
                                            </td>
                                            <td class="text-center text-vertical-align">
                                                <input type="text" class="form-control power-point" name="PowerPointPFC" id="PowerPointPFC" :disabled="!IsPowerPointAvailable">
                                            </td>
                                            <td class="text-center text-vertical-align">
                                                <input type="text" class="form-control power-point" name="PowerPointPFL" id="PowerPointPFL" :disabled="!IsPowerPointAvailable">
                                            </td>
                                        </tr>  
                                        <tr>
                                            <td class="text-vertical-align">Drainage Point</td>
                                            <td class="text-center text-vertical-align">
                                                <input type="checkbox" name="DrainagePoint" id="DrainagePoint" class="checkbox" value="yes" v-model="IsDrainagePointAvailable"/>
                                                <label for="DrainagePoint" tabindex="0"></label>
                                                <label for="DrainagePoint" class="text-normal cursor-pointer"></label>
                                            </td>
                                            <td class="text-center text-vertical-align">
                                                <input type="text" class="form-control drainage-point" name="DrainagePointPFC" id="DrainagePointPFC" :disabled="!IsDrainagePointAvailable">
                                            </td>
                                            <td class="text-center text-vertical-align">
                                                <input type="text" class="form-control drainage-point" name="DrainagePointPFL" id="DrainagePointPFL" :disabled="!IsDrainagePointAvailable">
                                            </td>
                                        </tr>  
                                        <tr>
                                            <td class="text-vertical-align">Core Cutting</td>
                                            <td class="text-center text-vertical-align">
                                                <input type="checkbox" name="CoreCutting" id="CoreCutting" class="checkbox" value="yes" v-model="IsCoreCuttingAvailable"/>
                                                <label for="CoreCutting" tabindex="0"></label>
                                                <label for="CoreCutting" class="text-normal cursor-pointer"></label>
                                            </td>
                                            <td class="text-center text-vertical-align">
                                                <input type="text" class="form-control core-cutting" name="CoreCuttingPFC" id="CoreCuttingPFC" :disabled="!IsCoreCuttingAvailable">
                                            </td>
                                            <td class="text-center text-vertical-align">
                                                <input type="text" class="form-control core-cutting" name="CoreCuttingPFL" id="CoreCuttingPFL" :disabled="!IsCoreCuttingAvailable">
                                            </td>
                                        </tr>  
                                        <tr>
                                            <td class="text-vertical-align">Copper Piping</td>
                                            <td class="text-center text-vertical-align">
                                                <input type="checkbox" name="CopperCutting" id="CopperCutting" class="checkbox" value="yes" v-model="IsCopperCuttingAvailable"/>
                                                <label for="CopperCutting" tabindex="0"></label>
                                                <label for="CopperCutting" class="text-normal cursor-pointer"></label>
                                            </td>
                                            <td class="text-center text-vertical-align">
                                                <input type="text" class="form-control copper-cutting" name="CopperCuttingPFC" id="CopperCuttingPFC" :disabled="!IsCopperCuttingAvailable">
                                            </td>
                                            <td class="text-center text-vertical-align">
                                                <input type="text" class="form-control copper-cutting" name="CopperCuttingPFL" id="CopperCuttingPFL" :disabled="!IsCopperCuttingAvailable">
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
                                <textarea rows="2" class="form-control no-resize-input" name="OutDoorUnitLocation" id="OutDoorUnitLocation"></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group"> 
                                <label>Attachments</label>
                                <input type="file" name="OurDoorUnitAttachment[]" id="OurDoorUnitAttachment" class="form-control hidden fileupload" accept="image/*" multiple="multiple"/>
                                <button type="button" class="btn btn-box-tool pd-0 btn-attachmentupload text-blue" data-toggle="tooltip" title="" data-widget="chat-pane-toggle" data-original-title="Add Attachments">
                                    <i class="fa fa-paperclip"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <p>
                                <input type="submit" name="AddAcFormSubmit" value="Save" class="btn btn-primary button-custom" id="AddAcFormSubmit" />
                                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                            </p>
                        </div>
                    </div>
                    <div id="AddAcNotificationArea" class="hidden">
                        <div class="alert alert-dismissible"></div>
                    </div>
                    <small>* N/A: Data not available</small>     
                </div>
            </form>
        </div>
        <div class="form-overlay hidden" id="AddAcFormOverlay">
            <div class="large loader"></div>
            <div class="loader-text">Saving AC</div>
        </div>
    </div>
</div>