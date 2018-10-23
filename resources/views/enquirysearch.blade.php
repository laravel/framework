@extends('layouts/master_template')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <form id="EnquirySearchForm" method="POST" action="searchpeople">
                        <div class="row">
                            <div class="form-group col-md-5 col-sm-8">
                                <label for="EnquirySearchBox">Search</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="(898) 989-9898 or user@example.com" autofocus="true" id="EnquirySearchBox" name="EnquirySearchBox" />
                                    <span class="input-group-btn">
                                        <button type="submit" id="SearchEnquiries" class="btn btn-primary btn-flat">Search</button>
                                    </span>
                                </div>
                                <span id="SearchHelpBlock" class="help-block">Enter atleast 4 characters to get autocomplete suggestions.</span>
                            </div>
                        </div>
                    </form>
                    <div id="CalloutsArea"></div>
                    <div class="table-responsive hidden" id="EnquiriesList">
                        <table class="table table-bordered">
                            <caption class="SearchCaption">Search results for the given Search Term - <u id="EnquirySearchTerm"></u></caption>
                            <thead id="EnquiriesListHeader" class="bg-light-blue text-center">
                                <tr>
                                    <th width="13%">Customer Name</th>
                                    <th width="5%">Mobile</th>
                                    <th width="12%">Email</th>
                                    <th width="22%">Builder Name</th>
                                    <th width="18%">Project Name</th>
                                    <th width="2%" class="text-center">Unit</th>
                                    <th width="25%">Site Address</th>
                                    <th width="3%"></th>
                                </tr>
                            </thead>
                            <tbody id="SearchResults"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="form-loader hidden" id="EnquirySearchFormLoader">Searching Enquiries...</div>
            <div id="NotificationArea"></div>
        </div>
    </div>
@endsection

@section('dynamicStyles')
    <link href="{{ URL::assetUrl('/AdminLTE/plugins/jQueryUI/jquery-ui.min.css') }}" rel="stylesheet" />
@endsection

@section('dynamicScripts')
    <script src="{{ URL::assetUrl('/js/common.js') }}"></script>
    <script src="{{ URL::assetUrl('/js/search/EnquirySearch.js') }}"></script>
    <script src="{{ URL::assetUrl('/AdminLTE/plugins/jQueryUI/jquery-ui.min.js') }}"></script>
@endsection
