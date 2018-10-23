@extends('layouts.master_template')

@section('content')
    <div class="box box-primary">
        <div class="box-body">
            @if ($projects->isEmpty())
                <div class="callout callout-info mr-tp-10 mr-bt-10">
                    <span>No projects found. Contact administrator to create a project.</span>
                </div>
            @elseif ($designItems->isEmpty())
                <div class="callout callout-info mr-tp-10 mr-bt-10">
                    <span>No design items found. Contact administrator to create a design item.</span>
                </div>
            @else
                <form method="POST" accept-charset="utf-8" action="{{ route("ideas.store") }}" id="CreateIdeaForm">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="Project">Project*</label>
                                <select name="Project" id="Project" class="form-control" data-api-end-point="{{ route("ideas.create.rooms") }}">
                                    <option value="">Select</option>
                                    @foreach($projects as $project)
                                        <option value="{{ $project["id"] }}" data-quick-estimation-id="{{ $project["quickEstimationId"] }}">{{ $project["name"] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 hidden hidden-idea-element">
                            <div class="form-group">
                                <label for="Room">Room*</label>
                                <select name="Room" id="Room" class="form-control" style="width:100%"></select>
                            </div>
                        </div>
                        <div class="col-md-3 hidden hidden-idea-element">
                            <div class="form-group">
                                <label for="DesignItem">Item*</label>
                                <select name="DesignItem" id="DesignItem" class="form-control" style="width:100%" data-api-end-point="{{ route("ideas.previous") }}">
                                    <option value="">Select</option>
                                    @foreach ($designItems as $designItem)
                                        <option value="{{ $designItem->id }}">{{ $designItem->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 hidden hidden-idea-element hidden-versioned-element">
                            <div class="form-group">
                                <label for="Attachments">Reference Images/Pdf's if Any?</label>
                                <input type="file" name="Attachments[]" id="Attachments" class="form-control" accept="image/*" multiple="multiple"/>
                            </div>
                        </div>
                    </div>
                    <div class="row hidden hidden-idea-element">
                        <div class="col-md-12 hidden" id="DesignVersionInfo">
                            <div class="callout callout-info mr-tp-10 mr-bt-10">
                                <span>
                                    A design has already been created, you can add new Ideas in design section.
                                    <a href="#" id="DesignVersionHref">Click here</a> to add new Idea / Note.
                                </span>
                            </div>
                        </div>
                        <div class="col-md-12 hidden-versioned-element">
                            <div class="form-group">
                                <label for="Idea">Idea / Note*</label>
                                <textarea class="form-control" id="Idea" name="Idea" style="resize:none" rows="4"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row hidden hidden-idea-element hidden-versioned-element">
                        <div class="col-md-12 margin-top-6 margin-bottom-7">
                            <button type="submit" class="btn btn-primary pd-rt-20 pd-lt-20 mr-rt-8" id="CreateIdeaFormSubmit">Save</button>
                            <button type="reset" class="btn btn-default" id="CreateIdeaFormReset">Undo changes</button>
                            <button type="button" class="btn btn-default pull-right hidden" id="ReloadPreviousIdeas">
                                <i class="fa fa-repeat pd-rt-7" aria-hidden="true"></i>Refresh
                            </button>
                        </div>
                    </div>
                </form>
                <div id="CreateIdeaFormNotificationArea" class="notification-area mr-tp-15"></div>
                <ul class="timeline hidden" id="PreviousIdeas"></ul>
                <div class="overlay hidden" id="CreateIdeaFormOverlay">
                    <div class="large loader"></div>
                    <div class="loader-text">Fetching Project Rooms...</div>
                </div>
            @endif
        </div>
    </div>
    <div class="modal fade" id="ConfirmationModal" tabindex="-1" role="dialog" >
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Confirm</h4>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete the Idea?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary pull-left modal-confirmation">Yes</button>
                    <button type="button" class="btn pull-left mr-lt-10" data-dismiss="modal">No</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('dynamicStyles')
    <link rel="stylesheet" type="text/css" href="{{ asset("plugins/select2/select2.min.css") }}"/>
    <link rel="stylesheet" type="text/css" href="{{ asset("plugins/magnific-popup/magnific-popup.min.css") }}"/>
@endsection

@section('dynamicScripts')
    <script src="{{ asset("js/common.js") }}"></script>
    <script src="{{ asset("plugins/select2/select2.min.js") }}"></script>
    <script src="{{ asset("plugins/magnific-popup/magnific-popup.min.js") }}"></script>
    <script src="{{ asset("js/ideas/create/customer.min.js") }}"></script>
@endsection