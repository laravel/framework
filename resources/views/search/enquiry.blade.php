@extends('layouts/master_template')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <form id="EnquirySearchForm" method="POST" action="{{ route('search.enquiries') }}">
                    @if(count($errors->all()) > 0)
                    <div class="alert alert-danger">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <h4 class="no-text-transform">Resolve these Errors!</h4>
                        <ul class="errors-list">
                            @foreach($errors->all() as $error)
                            <li>{{$error}}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="FirstName">First Name</label>
                                <input type="text" name="FirstName" id="FirstName" class="form-control" placeholder="Ex: John" value="{{ old('FirstName') }}"/>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="LastName">Last Name</label>
                                <input type="text" name="LastName" id="LastName" class="form-control" placeholder="Ex: Doe" value="{{ old('LastName') }}"/>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="Mobile">Mobile</label>
                                <input type="text" name="Mobile" id="Mobile" class="form-control" placeholder="Ex: (898) 989-9898" value="{{ old('Mobile') }}"/>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="Email">Email</label>
                                <input type="text" name="Email" id="Email" class="form-control" placeholder="Ex: user@example.com" value="{{ old('Email') }}"/>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="EnquiryName">Enquiry Name</label>
                                <input type="text" name="EnquiryName" id="EnquiryName" class="form-control" placeholder="Ex: Enquiry for AET" value="{{ old('EnquiryName') }}"/>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="EnquiryStatus">Enquiry Status</label>
                                <select name="EnquiryStatus" id="EnquiryStatus" class="form-control">
                                    <option value="">Select Enquiry Status</option>
                                    @foreach($EnquiryStatus as $status)
                                    <option value="{{ $status->Id }}">{{ $status->Name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="EnquiryShortCode">Project Code</label>
                                <input type="text" name="EnquiryShortCode" id="EnquiryShortCode" class="form-control" placeholder="Ex: SUB: RRO: HY" value="{{ old('EnquiryShortCode') }}"/>
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
                                            <label for="EnquiryNumber">Enquiry Number</label>
                                            <input type="text" name="EnquiryNumber" id="EnquiryNumber" class="form-control" placeholder="Ex: ENQ12345678"/>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="EnquiryDate">Enquiry Date</label>
                                            <div class="input-group">
                                                <div class="input-group-addon">
                                                    <i class="fa fa-calendar"></i>
                                                </div>
                                                <input type="text" name="EnquiryDate" id="EnquiryDate" class="form-control date-picker" placeholder="Ex: 01-Jan-2017" readonly="true" />
                                                <div class="input-group-btn">
                                                    <button type="button" class="btn btn-addon dropdown-toggle" id="EnquiryDateButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <i class="fa fa-filter"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-right" id="EnquiryDateFilters">
                                                        <li class="active" data-filter-name="eq">
                                                            <a href="#">
                                                                <b class="mr-rt-6">=</b> equal to
                                                            </a>
                                                        </li>
                                                        <li data-filter-name="lt">
                                                            <a href="#">
                                                                <b class="mr-rt-6">&lt;</b> less than
                                                            </a>
                                                        </li>
                                                        <li data-filter-name="gt">
                                                            <a href="#">
                                                                <b class="mr-rt-6">&gt;</b> greater than
                                                            </a>
                                                        </li>
                                                        <li data-filter-name="le">
                                                            <a href="#">
                                                                <b class="mr-rt-6">&le;</b> less than or equal to
                                                            </a>
                                                        </li>
                                                        <li data-filter-name="ge">
                                                            <a href="#">
                                                                <b class="mr-rt-6">&ge;</b> greater than or equal to
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="BuilderName">Builder Name</label>
                                            <input type="text" name="BuilderName" id="BuilderName" class="form-control" placeholder="Ex: HP Homes" value="{{ old('Mobile') }}"/>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="ProjectName">Project Name</label>
                                            <input type="text" name="ProjectName" id="ProjectName" class="form-control" placeholder="Ex: Sarika Heights" value="{{ old('Email') }}"/>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="SiteCity">City</label>
                                            <select name="SiteCity" id="SiteCity" class="form-control">
                                                <option value="">Select City</option>
                                                @foreach($WorkingCities as $CityId => $CityName)
                                                <option value="{{ $CityId }}">{{ $CityName }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="Unit">Unit</label>
                                            <select name="Unit" id="Unit" class="form-control">
                                                <option value="">Select Unit</option>
                                                @foreach($Units as $unit)
                                                <option value="{{ $unit->Id }}">{{ $unit->Name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="OfficeVisitDate">Office Visit Date</label>
                                            <div class="input-group">
                                                <div class="input-group-addon">
                                                    <i class="fa fa-calendar"></i>
                                                </div>
                                                <input type="text" name="OfficeVisitDate" id="OfficeVisitDate" class="form-control date-picker-addtopn" placeholder="Ex: 01-Jan-2017" readonly="true" />
                                                <div class="input-group-btn">
                                                    <button type="button" class="btn btn-addon dropdown-toggle" id="OffVisitDateButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <i class="fa fa-filter"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-right" id="OfficeVisitDateFilters">
                                                        <li class="active" data-filter-name="eq">
                                                            <a href="#">
                                                                <b class="mr-rt-6">=</b> equal to
                                                            </a>
                                                        </li>
                                                        <li data-filter-name="lt">
                                                            <a href="#">
                                                                <b class="mr-rt-6">&lt;</b> less than
                                                            </a>
                                                        </li>
                                                        <li data-filter-name="gt">
                                                            <a href="#">
                                                                <b class="mr-rt-6">&gt;</b> greater than
                                                            </a>
                                                        </li>
                                                        <li data-filter-name="le">
                                                            <a href="#">
                                                                <b class="mr-rt-6">&le;</b> less than or equal to
                                                            </a>
                                                        </li>
                                                        <li data-filter-name="ge">
                                                            <a href="#">
                                                                <b class="mr-rt-6">&ge;</b> greater than or equal to
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="SiteVisitDate">Site Visit Date</label>
                                            <div class="input-group">
                                                <div class="input-group-addon">
                                                    <i class="fa fa-calendar"></i>
                                                </div>
                                                <input type="text" name="SiteVisitDate" id="SiteVisitDate" class="form-control date-picker-addtopn" placeholder="Ex: 01-Jan-2017" readonly="true" />
                                                <div class="input-group-btn">
                                                    <button type="button" class="btn btn-addon dropdown-toggle" id="SiteVisitDateButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <i class="fa fa-filter"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-right" id="SiteVisitDateFilters">
                                                        <li class="active" data-filter-name="eq">
                                                            <a href="#">
                                                                <b class="mr-rt-6">=</b> equal to
                                                            </a>
                                                        </li>
                                                        <li data-filter-name="lt">
                                                            <a href="#">
                                                                <b class="mr-rt-6">&lt;</b> less than
                                                            </a>
                                                        </li>
                                                        <li data-filter-name="gt">
                                                            <a href="#">
                                                                <b class="mr-rt-6">&gt;</b> greater than
                                                            </a>
                                                        </li>
                                                        <li data-filter-name="le">
                                                            <a href="#">
                                                                <b class="mr-rt-6">&le;</b> less than or equal to
                                                            </a>
                                                        </li>
                                                        <li data-filter-name="ge">
                                                            <a href="#">
                                                                <b class="mr-rt-6">&ge;</b> greater than or equal to
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="FromDate">Enquiry Date From</label>
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
                                            <label for="ToDate">Enquiry Date To</label>
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
                        <div class="col-md-4">
                            <input type="submit" class="btn btn-primary button-custom mr-bt-10" value="Search" id="EnquirySearchFormSubmit" />
                            <input type="reset" class="btn button-custom mr-bt-10" value="Clear" id="EnquirySearchFormReset" />
                        </div>
                    </div>
                </form>
            </div>
            <div class="box-body hidden table-responsive" id="SearchResultsBody">
                <table class="table table-striped table-bordered" id="SearchResults">
                    <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                        <tr>
                        <th class="text-center text-vertical-align pd-rt-8">#</th>
                        <th class="text-center text-vertical-align">
                            Enquiry No<br>Enquiry Date
                        </th>
                        <th class="text-center text-vertical-align">
                            Customer Name<br>Email<br>Mobile
                        </th>
                        <th class="text-center text-vertical-align">
                            Project Name<br>
                            Unit Type<br>
                            Site Address
                        </th>
                        <th class="text-center text-vertical-align">Super Builtup Area</th>
                        <th class="text-center text-vertical-align">Status</th>
                        <th class="text-center text-vertical-align">Status Description</th>
                        <th class="text-center text-vertical-align">Is Awarded</th>
                        <th class="text-center text-vertical-align"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <div class="callout callout-info mr-tp-15 mr-bt-15 hidden">No Enquiries found for the given search parameters.</div>
            </div>
            <div class="form-overlay hidden" id="EnquirySearchFormOverlay">
                <div class="large loader"></div>
                <div class="loader-text">Fetching Results...</div>
            </div>
        </div>
        <div id="NotificationArea"></div>
    </div>
</div>
<div class="modal fade" id="EnquiryViewModal" tabindex="-1" role="dialog" aria-labelledby="EnquiryViewTitle">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content printable-area">
            <div class="modal-body"></div>
        </div>
    </div>
</div>
@endsection

@section('dynamicStyles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/css/bootstrap-datepicker3.min.css" rel="stylesheet"/>
<link href="{{ asset('/AdminLTE/plugins/datatables/dataTables.bootstrap.css') }}" rel="stylesheet"/>
<link href="{{ asset('/css/search/enquiries.css') }}" rel="stylesheet"/>
@endsection

@section('dynamicScripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/js/bootstrap-datepicker.min.js"></script>
<script src="{{ asset('/AdminLTE/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="{{ asset('/js/common.js') }}"></script>
<script src="{{ asset('/js/search/enquiries.js') }}"></script>
@endsection
