@extends('layouts/master_template')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <form id="QuickEstimatesSearchForm" method="POST" action="{{ route('search.quickestimates') }}">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="FirstName">First Name</label>
                                <input type="text" name="FirstName" id="FirstName" class="form-control" placeholder="Ex: John"/>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="LastName">Last Name</label>
                                <input type="text" name="LastName" id="LastName" class="form-control" placeholder="Ex: Doe"/>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="Mobile">Mobile</label>
                                <input type="text" name="Mobile" id="Mobile" class="form-control" placeholder="Ex: (898) 989-9898"/>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="Email">Email</label>
                                <input type="text" name="Email" id="Email" class="form-control" placeholder="Ex: user@example.com"/>
                            </div>
                        </div>
                    </div>
                     <div id="AdditionalOptionsAccordion">
                        <div class="panel">
                            <a data-toggle="collapse" data-parent="#AdditionalOptionsAccordion" href="#AdditionalOptions" class="btn pd-lt-0" aria-expanded="true">
                                <h4 id="AddlOptToggle">
                                    <i class="fa fa-plus-square-o" aria-hidden="true"></i>&nbsp;&nbsp;Additional Options
                                </h4>
                            </a>
                            <div id="AdditionalOptions" class="panel-collapse collapse" aria-expanded="false">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="QuickEstimationName">Quick Estimation Name</label>
                                            <input type="text" name="QuickEstimationName" id="QuickEstimationName" class="form-control" placeholder="Ex: My Ramky Towers Quick Estimate"/>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="ReferenceNumber">Reference Number</label>
                                            <input type="text" name="ReferenceNumber" id="ReferenceNumber" class="form-control" placeholder="Ex: QE12345678"/>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="FromDate">From</label>
                                            <div class="input-group">
                                                <div class="input-group-addon">
                                                    <i class="fa fa-calendar"></i>
                                                </div>
                                                <input type="text" name="FromDate" id="FromDate" class="form-control date-picker-addtopn" placeholder="Ex: 01-Jan-2017" readonly="true" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="ToDate">To</label>
                                            <div class="input-group">
                                                <div class="input-group-addon">
                                                    <i class="fa fa-calendar"></i>
                                                </div>
                                                <input type="text" name="ToDate" id="ToDate" class="form-control date-picker-addtopn" placeholder="Ex: 01-Jan-2017" readonly="true" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <input type="submit" class="btn btn-primary button-custom mr-bt-10" value="Search" id="QuickEstimatesSearchFormSubmit"/>
                            <input type="reset" class="btn button-custom mr-bt-10" value="Clear" id="QuickEstimatesSearchFormReset"/>
                            <input type="submit" class="btn button-custom mr-bt-10" value="All Estimates" id="QuickEstimatesSearchAllEstimates"/>
                        </div>
                    </div>
                </form>
            </div>
            <div class="box-body hidden table-responsive" id="SearchResultsBody">
                <table class="table table-striped table-bordered">
                    <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                        <tr>
                            <th width="3%" class="text-center text-vertical-align pd-rt-8">#</th>
                            <th width="10%" class="text-center text-vertical-align">Customer Name</th>
                            <th width="10%" class="text-center text-vertical-align">Reference Number</th>
                            <th width="10%" class="text-center text-vertical-align">Enquiry</th>
                            <th width="18%" class="text-center text-vertical-align">Site Address</th>
                            <th width="8%" class="text-center text-vertical-align">Unit Type</th>
                            <th width="8%" class="text-center text-vertical-align">Work Type</th>
                            <th width="10%" class="text-center text-vertical-align">All Branded</th>
                            <th width="10%" class="text-center text-vertical-align">HECHPE Select</th>
                            <th width="10%" class="text-center text-vertical-align">Market Standard</th>
                            <th width="3%" class="text-center text-vertical-align"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <div class="callout callout-info mr-tp-15 mr-bt-15 hidden">No Quick Estimates found for the given search parameters.</div>
            </div>
            <div class="form-overlay hidden" id="QuickEstimatesSearchFormOverlay">
                <div class="large loader"></div>
                <div class="loader-text">Fetching results...</div>
            </div>
        </div>
        <div id="NotificationArea" class="hidden">
            <div class="alert alert-dismissible hidden">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <p class="body"></p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('dynamicStyles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/css/bootstrap-datepicker3.min.css" rel="stylesheet"/>
<link href="{{ asset('/AdminLTE/plugins/datatables/dataTables.bootstrap.css') }}" rel="stylesheet"/>
<link href="{{ asset('/css/search/quickestimates.css') }}" rel="stylesheet"/>
@endsection

@section('dynamicScripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/js/bootstrap-datepicker.min.js"></script>
<script src="{{ asset('/AdminLTE/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="{{ asset('/js/common.js') }}"></script>
<script src="{{ asset('/js/search/quickestimates.js') }}"></script>
@endsection
