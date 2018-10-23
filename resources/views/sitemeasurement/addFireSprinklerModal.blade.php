<div class="modal fade" tabindex="-1" role="dialog" id="AddFireSpModal">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Fire Sprinkler Details
                    <span data-toggle="tooltip" id="AddFireSprinkler" class="mr-lt-2" title="" data-original-title="Add more Fire Sprinklers" @click.prevent="addFireSprinkler">
                        <i class="fa fa-plus-square" aria-hidden="true"></i>
                    </span>
                </h4>
            </div>
            <div class="modal-body">
                <form action="{{ route('sitemeasurement.room.firesprinkler.store') }}" method="POST" accept-charset="utf-8" enctype="multipart/form-data" id="AddFireSpForm">       
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive pd-tp-5">
                                <table class="table table-striped table-bordered" id="AddFSPTable">
                                    <thead style="border-top: 1px solid #f4f4f4;">
                                    <th class="text-center text-vertical-align" width="28%">Wall*
                                        <i class="fa fa-question-circle cursor-help help-icon mr-lt-2" data-toggle="tooltip" data-original-title="Specify the wall direction"></i>
                                    </th>
                                    <th class="text-center text-vertical-align" width="21%">PFC*
                                        <i class="fa fa-question-circle cursor-help help-icon mr-lt-2" data-toggle="tooltip" data-original-title="Position from the Ceiling"></i>
                                    </th>
                                    <th class="text-center text-vertical-align" width="21%">PFLW*
                                        <i class="fa fa-question-circle cursor-help help-icon mr-lt-2" data-toggle="tooltip" data-original-title="Position from left side Wall"></i>
                                    </th>
                                    <th class="text-center text-vertical-align" width="20%">Attachments*</th>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(sprinlker, key) in FireSprinklers">
                                            <td class="text-center text-vertical-align">
                                                <select class="form-control firesp-direction" :name="'FireSpLocDir['+key+']'" :id="'FireSpLocDir['+(key)+']'" v-model="sprinlker.FireSpLocDir">
                                                    <option value="">Select Wall</option>
                                                    <option value="North">North</option>
                                                    <option value="East">East</option>
                                                    <option value="South">South</option>
                                                    <option value="West">West</option>
                                                </select>
                                            </td>
                                            <td class="text-center text-vertical-align">
                                                <input type="text" class="form-control firesp-pfc" :name="'FireSpPFC['+(key)+']'" :id="'FireSpPFC['+(key)+']'" v-model="sprinlker.FireSpPFC">
                                            </td>
                                            <td class="text-center text-vertical-align">
                                                <input type="text" class="form-control firesp-pfl" :name="'FireSpPFL['+(key)+']'" :id="'FireSpPFL['+(key)+']'" v-model="sprinlker.FireSpPFL">
                                            </td>
                                            <td class="text-vertical-align text-center">
                                                <input type="file" :name="'FireSpAttachment_'+key+'[]'" :id="'FireSpAttachment_'+key" class="form-control hidden fire-sp-file" accept="image/*" multiple="multiple"/>
                                                <button type="button" class="btn btn-box-tool pd-0 btn-attachmentupload text-blue" :id="'AttachmentUploadBtn_'+key" @click="AddFSPAttachmentsHandler(key)" data-toggle="tooltip" title="" data-widget="chat-pane-toggle" data-original-title="Add Attachments">
                                                    <i class="fa fa-paperclip"></i>
                                                </button>
                                                <span data-toggle="tooltip" :id="'RemoveFireSprinkler-'+(key)+''" class="remove-sprikler mr-lt-2 pull-right" title="" data-original-title="Remove" @click.prevent="removeFireSprinkler(key)" v-if="key > 0">
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
                                <input type="submit" name="CreateFireSpSubmitBtn" value="Save" class="btn btn-primary button-custom" id="CreateFireSpSubmitBtn" />
                                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                            </p>
                        </div>
                    </div>
                </form>
            </div>
            <div id="AddFireSpNotificationArea" class="hidden">
                <div class="alert alert-dismissible"></div>
            </div>
            <div class="form-overlay hidden" id="AddFireSpFormOverlay">
                <div class="large loader"></div>
                <div class="loader-text">Saving FSP</div>
            </div>
        </div>
    </div>
</div>
