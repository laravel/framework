@extends('layouts/master_template')

@section('dynamicStyles')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/css/bootstrap-datepicker3.min.css"/>
<link href="{{ asset('/css/enquiries/enquiryaction.css') }}" rel="stylesheet"/>
@endsection

@section('content')
<div id="EnquiryActionsPage" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                <input type="submit" class="btn button-custom" value="Show All Actions" id="AllEnquiryActions" @click.prevent="ShowAllEnquiryAction">
                    <div class="pd-tp-14" v-if="filteredEnquiryActions.length < 1"> 
                        <div class="callout callout-info">
                            <p>
                                <i class="fa fa-fw fa-info-circle"></i>No Actions found.
                            </p>
                        </div>
                    </div>
                    <!-- Action Vue table component -->
                    <v-client-table :columns="columns" :data="filteredEnquiryActions" :options="options" v-else>
                        <span slot="Id" slot-scope="props">@{{props.index}}</span>
                        <span slot="Status" slot-scope="props" v-html="getActionStatus(props.row.Status)"></span>
                        <template slot="Operations" slot-scope="props">
                            <a class="mr-rt-3" data-toggle="tooltip" title="Edit" data-original-title="Edit" role="button" @click.prevent="editEnquiryAction(props.row.Id)">
                                <i class="fa fa-fw fa-pencil-square-o text-black"></i>
                            </a>
                            <a class="mr-rt-3" data-toggle="tooltip" data-original-title="Delete" role="button" @click.prevent="deleteEnquiryAction(props.row.Id)">
                                <span class="fa fa-fw fa-trash text-black"></span>
                            </a>
                        </template>
                    </v-client-table>
                </div>
            </div>
        </div>
    </div>
    <!-- Edit Enquiry Action Modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="EditEnquiryActionModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Edit Enquiry Action</h4>
                </div>
                <form action="{{route('enquiries.action.update', ["id" => ""])}}" method="POST" accept-charset="utf-8" id="EditEnquiryActionForm">
                    <div class="modal-body pd-bt-0">
                        <div class="form-group">
                            <label for="EditEnquiryStatusName">Action*</label>
                            <textarea name="EditEnquiryActionDescription" id="EditEnquiryActionDescription" class="form-control" rows="2" :value="selectedEnquiryActionData.Action" placeholder="Ex: Lost"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="EditAssignedTo">Assigned To</label>
                                    <select name="EditAssignedTo" id="EditAssignedTo" class="form-control">
                                        <option value="">Select a user</option>
                                        <option v-for ="(user, index) in Users" :value=index :selected="index==selectedEnquiryActionData.AssignedToId" >@{{ user }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="DueDate">Due Date</label>
                                    <div class="input-group">
                                        <div class="input-group-addon">
                                            <i class="fa fa-calendar"></i>
                                        </div>
                                        <input type="text" name="DueDate" id="DueDate" :value="selectedEnquiryActionData.DueDate" class="form-control date-picker-addtopn" placeholder="Ex: 01-Jan-2019" />

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="EditActionStatus">Status</label>
                                    <select name="EditActionStatus" id="EditActionStatus" class="form-control">
                                        <option value="">Select a Status</option>
                                        <option v-for ="(status, index) in ActionStatus" :value=index :selected="index==selectedEnquiryActionData.Status" >@{{ status }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary pull-left button-custom" id="EditEnquiryActionFormSubmitBtn">Update</button>
                        <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
                <div class="form-overlay" id="EditEnquiryActionFormOverlay" v-if="ShowUpdateEnquiryActionLoader">
                    <div class="large loader"></div>
                    <div class="loader-text">Updating Action</div>
                </div>
            </div>
            <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>
        </div>
    </div>

    <!--- Delete Enquiry Action Model-->
    <div class="modal fade" tabindex="-1" role="dialog" id="DeleteActionModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Confirm</h4>
                </div>
                <div class="modal-body">
                    Do you want to delete this Enquiry Action?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary pull-left" id="DeleteActionSubmit" @click="deleteAction(currentEnquiryActionId)">Yes</button>
                    <button type="button" class="btn pull-left mr-lt-10" data-dismiss="modal">No</button>
                </div>
                <div id="DeleteCombNotificationArea" class="hidden">
                    <div class="alert alert-dismissible"></div>
                </div>
                <div class="form-overlay" id="DeleteEnquiryActionFormOverlay" v-if="ShowDeleteActionLoader">
                    <div class="large loader"></div>
                    <div class="loader-text">Deleting Enquiry Action</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('dynamicScripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/js/bootstrap-datepicker.min.js"></script>
<script src="{{ asset('/js/common.js') }}"></script>
<script src="{{ asset('/js/enquiries/actions/list.js') }}"></script>
@endsection
