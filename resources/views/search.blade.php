@extends('layouts/master_template')

@section('content')
    <div class="row">
        <!-- left column -->
        <div class="col-md-12">
            <!-- general form elements -->
            <div class="box box-primary">
                <div class="box-header">
                    <form id="SearchPeopleForm" method="POST" action="searchpeople">
                        <div class="row hidden" id="NameRow">
                            <div class="form-group col-md-4 col-sm-5">
                                <label for="Firstname">First Name</label>
                                <input type="text" name="Firstname" id="Firstname" class="form-control" placeholder="Ex: John" />
                            </div>
                            <div class="form-group col-md-4 col-sm-5">
                                <label for="Lastname">Last Name</label>
                                <input type="text" name="Lastname" id="Lastname" class="form-control" placeholder="Ex: Doe" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-4 col-sm-5">
                                <label for="Mobile">Mobile Number</label>
                                <input type="text" name="Mobile" id="Mobile" class="form-control" placeholder="Ex: (898) 989-9898" autocomplete="off" />
                            </div>
                            <div class="form-group col-md-1 col-sm-2 SearchOrCond">- or -</div>
                            <div class="form-group col-md-4 col-sm-5">
                                <label for="Email">Email Address</label>
                                <input type="email" name="Email" id="Email" class="form-control" placeholder="Ex: user@example.com" autocomplete="off" />
                            </div>                            
                        </div>
                        <div class="row">
                            <div class="form-group col-md-8 col-sm-8">
                                <button type="submit" class="btn btn-primary" id="SearchPeopleButton">Search</button>
                                <button type="reset" class="btn btn-danger hidden" id="ResetRegistration">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="box-body table-responsive" id="SearchResultsContainer">
                    <table class="table table-striped hidden" id="SearchResults">
                        <caption class="SearchCaption"></caption>
                        <thead class="bg-light-blue text-center">
                            <tr>
                                <th>First name</th>
                                <th>Last name</th>
                                <th>Email</th>
                                <th colspan="2">Mobile</th>
                            </tr>
                        </thead>
                        <tbody id="SearchResultsBody"></tbody>
                    </table>
                    <div class="callout hidden" id="NotificationArea">
                        <h4>
                            <span class="fa" id="AlertIcon"></span>&nbsp;
                            <span class="alert-title"></span>
                        </h4>
                        <p class="alert-body"></p>
                    </div>
                </div>
            </div>
            <!-- /.box -->
        </div>
        <!-- /.box -->
    </div>
    <div class="modal fade" id="ListEnquiriesModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Enquiries list</h4>
                </div>
                <div class="modal-body table-responsive" id="EnquiriesModalBody">Loading...</div>
            </div>
        </div>
    </div>
@endsection

@section('dynamicStyles')
    <link href="{{ URL::assetUrl('/AdminLTE/plugins/jQueryUI/jquery-ui.min.css') }}" rel="stylesheet" />
@endsection

@section('dynamicScripts')
    <script src="{{ URL::assetUrl('/js/common.js') }}"></script>
    <script src="{{ URL::assetUrl('/js/SearchPeople.js') }}"></script>
    <script src="{{ URL::assetUrl('/AdminLTE/plugins/jQueryUI/jquery-ui.min.js') }}"></script>
@endsection
