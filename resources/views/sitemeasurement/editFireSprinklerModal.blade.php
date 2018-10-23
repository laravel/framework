<div class="modal fade" tabindex="-1" role="dialog" id="EditFireSpModal">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4>Fire Sprinkler Details
                    <span data-toggle="tooltip" id="EditFireSprinkler" class="mr-lt-2" title="" data-original-title="Add more Fire Sprinklers" @click.prevent="addNewFireSprinklers">
                        <i class="fa fa-plus-square" aria-hidden="true"></i>
                    </span>
                </h4>
            </div>
            <div class="modal-body">
                <form action="{{ route('sitemeasurement.room.firesprinkler.update') }}" method="POST" accept-charset="utf-8" enctype="multipart/form-data" id="EditFireSpForm">    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="EditFSPTable">
                                    <thead style="border-top: 1px solid #f4f4f4;">
                                    <th class="text-center text-vertical-align" width="22%">Wall*<i class="fa fa-question-circle cursor-help help-icon mr-lt-2" data-toggle="tooltip" data-original-title="Specify the wall direction"></i></th>
                                    <th class="text-center text-vertical-align" width="22%">PFC*
                                        <i class="fa fa-question-circle cursor-help help-icon mr-lt-2" data-toggle="tooltip" data-original-title="Position from the Ceiling"></i>
                                    </th>
                                    <th class="text-center text-vertical-align" width="25%">PFLW*
                                        <i class="fa fa-question-circle cursor-help help-icon mr-lt-2" data-toggle="tooltip" data-original-title="Position from left side Wall"></i>
                                    </th>
                                    <th class="text-center text-vertical-align" width="25%">Attachments* <i class="fa fa-question-circle cursor-help help-icon mr-lt-2" data-toggle="tooltip" data-original-title="Upload new attachments will overwrite old ones"></i></th>
                                    <th  class="text-center text-vertical-align" width="6%"></th>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(sprinlker, key) in fireSprinklers">
                                            <td class="text-center text-vertical-align">
                                                <select class="form-control edit-firesp-direction" :name="'EditFireSpLocDir['+key+']'" :id="'EditFireSpLocDir['+(key)+']'" v-model="sprinlker.WallDirection">
                                                    <option value="">Select Wall</option>
                                                    <option value="North">North</option>
                                                    <option value="East">East</option>
                                                    <option value="South">South</option>
                                                    <option value="West">West</option>
                                                </select>
                                            </td>
                                            <td class="text-center text-vertical-align">
                                                <input type="text" class="form-control edit-firesp-pfc" :name="'EditFireSpPFC['+(key)+']'" :id="'EditFireSpPFC['+(key)+']'" v-model="sprinlker.PFC">
                                            </td>
                                            <td class="text-center text-vertical-align">
                                                <input type="text" class="form-control edit-firesp-pfl" :name="'EditFireSpPFL['+(key)+']'" :id="'EditFireSpPFL['+(key)+']'" v-model="sprinlker.PFLW">
                                            </td>
                                            <td class="text-vertical-align">
                                                <div class="row">
                                                    <div class="col-md-10 pd-rt-0" v-if="sprinlker.Attachments">
                                                        <span v-for="(file, index) in JSON.parse(sprinlker.Attachments)">
                                                            <img :src="CdnUrl+file.Path" class="img-thumbnail mr-rt-4 mr-tp-4"  :title="file.UserFileName" :alt="file.UserFileName"> 
                                                        </span>
                                                    </div> 
                                                    <div class="pd-0 mr-tp-18 col-md-2" v-if="sprinlker.Attachments">
                                                        <input type="file" :name="'EditFireSpAttachment_'+key+'[]'" :id="'EditFireSpAttachment_'+key" class="form-control hidden edit-fire-sp-file" accept="image/*" multiple="multiple" :data-value="sprinlker.Attachments"//>
                                                               <button type="button" class="btn btn-box-tool btn-attachmentupload text-blue pd-tp-0" :id="'EditAttachmentUploadBtn_'+key" @click="EditFSPAttachmentsHandler(key)" data-toggle="tooltip" title="" data-widget="chat-pane-toggle" data-original-title="Add Attachments" style="font-size: 16px;">
                                                            <i class="fa fa-paperclip"></i>
                                                        </button>
                                                    </div>
                                                    <div class="mr-tp-18 col-md-12" v-if="!sprinlker.Attachments">
                                                        <input type="file" :name="'EditFireSpAttachment_'+key+'[]'" :id="'EditFireSpAttachment_'+key" class="form-control hidden edit-fire-sp-file" accept="image/*" multiple="multiple" :data-value="sprinlker.Attachments"//>
                                                               <button type="button" class="btn btn-box-tool btn-attachmentupload text-blue pd-tp-0" :id="'EditAttachmentUploadBtn_'+key" @click="EditFSPAttachmentsHandler(key)" data-toggle="tooltip" title="" data-widget="chat-pane-toggle" data-original-title="Add Attachments" style="font-size: 16px;">
                                                            <i class="fa fa-paperclip"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center text-vertical-align"> 
                                                <span data-toggle="tooltip" :id="'DeleteFireSprinkler-'+(key)+''" class="edit-remove-sprikler" title="" data-original-title="Remove" @click.prevent="deleteFireSprinkler(key)" v-if="hideDeleteFireSpIcon(sprinlker)">
                                                    <i class="fa fa-minus-square" aria-hidden="true"></i>
                                                </span>
                                            </td>
                                        </tr>  
                                    </tbody>
                                </table> 
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <p>
                                <input type="submit" name="EditFireSpSubmitBtn" value="Update" class="btn btn-primary button-custom" id="EditFireSpSubmitBtn" />
                                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                            </p>
                        </div>
                    </div>
                </form>
            </div>
            <div id="EditFireSpNotificationArea" class="hidden">
                <div class="alert alert-dismissible"></div>
            </div>
            <div class="form-overlay hidden" id="EditFireSpFormOverlay">
                <div class="large loader"></div>
                <div class="loader-text">Updating FSP</div>
            </div>
        </div>
    </div>
</div>
