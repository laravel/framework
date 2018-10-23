@extends('layouts/master_template')

@section('content')

<div class="box box-primary">
    <div class="box-header with-border">
        <div class="row">
            <div  class="col-md-12 text-right">
                @if(auth()->user()->isManager())
                <a href="{{ route('search.enquiries') }}" class="enquiry-view-back-btn">
                    <button type="button" class="btn btn-primary">
                        <i class="fa fa-arrow-left" aria-hidden="true"></i> Back
                    </button>
                </a>
               @elseif(auth()->user()->isCustomer())
                <a href="{{ route('enquiries.index') }}" class="enquiry-view-back-btn">
                    <button type="button" class="btn btn-primary">
                        <i class="fa fa-arrow-left" aria-hidden="true"></i> Back
                    </button>
                </a>
                @endif
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 text-left EnquiryInfo" style="padding-top: 13px;">
                <h5><b>{{ $EnquiryNo }} </b></h5>
                <h5>{{ $EnquiryName}}</h5>
                <h5>Created at &nbsp;{{ $EnquiryDateInfo["CreatedDate"]}}</h5>
                <h5>Submitted at&nbsp; {{ $EnquiryDateInfo["SubmittedAt"]}}</h5>
            </div>
            <div class="col-md-6 text-right">
                <h3>{{$CustomerName}}</h3>
                <h5>{{$CustomerMobile}} | {{$CustomerEmail}}</h5>
                <?= $CustomerAddress; ?>
            </div>
        </div>
    </div>
    <div class="box-body">
        <table class="table table-bordered">
            <caption class="table-caption">
                <h4 style="color:black">Site Information</h4>
            </caption>
            <tbody>
                <tr>
                    <td width="50%"><?= $SiteAddress; ?></td>
                    <td width="50%"><?= $SiteInformation; ?></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <h5><b>Project Plan: </b><?= $ProjectPlanFileName; ?></h5>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <h5><b>Additional Details</b></h5>
                    </td>
                </tr>
                <tr>
                    <td>
                        <h5>Expected Start Date: {{$ExpectedStartDate}}</h5>
                    </td>
                    <td>
                        <h5>Expected Completion Date: {{$ExpectedEndDate}}</h5>
                    </td>
                </tr>
                <tr>
                    <td>
                        <h5>Is Handover Done: {{$IsHandoverDone}}</h5>
                    </td>
                    <td>
                        @if($IsHandoverDone === "No")
                        <h5>Expected Handover Date: {{$ExpectedHandoverDate}}</h5>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>
                        <h5>Are Start/Completion Dates Flexible?: {{$IsDateFlexible}}</h5>
                    </td>
                    <td>
                        <h5>Vaastu Date: {{$VaastuDate}}</h5>
                    </td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <h5><b>Comments: </b>{{$Comments}}</h5>
                    </td>
                </tr>
                <tr>
                    <td>
                        <h5>HECHPE Office Visit Date: {{$OfficeVisitDate}}</h5>
                    </td>
                    <td>
                        <h5>Site Visit Date: {{$SiteVisitDate}}</h5>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="box-footer">
        <div class="pull-left pd-5">
            <small>N/A: Not Available</small>
        </div>
        <div class="pull-right">
            @if(auth()->user()->isManager())
            <a href="{{ route('search.enquiries') }}" class="enquiry-view-back-btn">
                <button type="button" class="btn btn-primary">
                    <i class="fa fa-arrow-left" aria-hidden="true"></i> Back
                </button>
            </a>
             @elseif(auth()->user()->isCustomer())
            <a href="{{ route('enquiries.index') }}" class="enquiry-view-back-btn">
                <button type="button" class="btn btn-primary">
                    <i class="fa fa-arrow-left" aria-hidden="true"></i> Back
                </button>
            </a>
            @endif
        </div>
    </div>
</div>
@endsection

