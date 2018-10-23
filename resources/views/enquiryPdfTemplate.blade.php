@extends('layouts/Pdfs/PDFTemplate')

@section('content')
<div class="box box-primary pd-5">
    <div class="row">
        <div class="col-xs-6 text-left EnquiryInfo" style="padding-top: 13px;">
            <h5><b>{{ $EnquiryNo }} </b></h5>
            <h5>{{ $EnquiryName}}</h5>
            <h5>Created on&nbsp; {{ $EnquiryDateInfo["CreatedDate"]}}</h5>
            <h5>Submitted on&nbsp; {{ $EnquiryDateInfo["UpdatedDate"]}}</h5>
        </div>
        <div class="col-xs-6 text-right">
            <h3>{{$CustomerName}}</h3>
            <h5>{{$CustomerMobile}} | {{$CustomerEmail}}</h5>
            <?= $CustomerAddress; ?>
        </div>
    </div>
    <div class="box-body">
        <table class="table table-bordered" id="EnquiryPdf">
            <caption class="table-caption">
                <h4 style="color:black">Site Information</h4>
            </caption>
            <tbody>
                <tr>
                    <td colspan="3" width="50%"><?= $SiteAddress; ?></td>
                    <td colspan="3" width="50%"><?= $SiteInformation; ?></td>
                </tr>
                <tr>
                    <td colspan="6" class="cell-height">
                        <h5><b>Project Plan: </b><?= $ProjectPlanFileName; ?></h5>
                    </td>
                </tr>
                <tr>
                    <td colspan="6"  class="cell-height">
                        <h5><b>Site Working Rules</b></h5>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" width="50%">
                        <h5>{{$WorkOnSunday}}</h5>
                        <h5>{{$WorkOnPublicHolidays}}</h5>
                        <h5>{{$CanStayOnsite}}</h5>
                    </td>
                    <td colspan="3" width="50%">
                        <h5>Work Start Time: {{$WorkStartTime}}</h5>
                        <h5>Work End Time: {{$WorkEndTime}}</h5>
                    </td>
                </tr>
                <tr>
                    <td colspan="6">
                        <h5>Any comments related to Onsite Working ?: {{$OnsiteWorkComment}}</h5>
                    </td>
                </tr>
                <tr>
                    <td colspan="6"  class="cell-height">
                        <h5><b>Important Dates</b></h5>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" width="33.33%" class="cell-height"><h5>Expected Start Date: {{$ExpectedStartDate}}</h5></td>
                    <td colspan="2" width="33.33%" class="cell-height"><h5>{{$IsHandoverDone}}</h5></td>
                    <td colspan="2" width="33.33%" class="cell-height"> <h5>{{$IsDateFlexible}}</h5></td>
                </tr>
                <tr>
                    <td colspan="2" width="33.33%" class="cell-height"><h5>Expected Completion Date: {{$ExpectedEndDate}}</h5></td>
                    <td colspan="2" width="33.33%" class="cell-height">
                        @if($HandoverDone == 0 && isset($ExpectedHandoverDate))
                        <h5> Expected Handover Date: {{$ExpectedHandoverDate}}</h5>
                        @endif
                    </td>
                    <td colspan="2" width="33.33%" class="cell-height"><h5>Vaastu Date: {{$VaastuDate}}</h5></td>
                </tr>
                <tr>
                    <td colspan="6">
                        <h5><b>Comments: </b>{{$Comments}}</h5>
                    </td>
                </tr>
                <tr>
                    <td  class="cell-height" colspan="3" style="height: 4px;padding: 0px;padding-left: 11px;">
                        <h5>HECHPE Office Visit Date: {{$OfficeVisitDate}}</h5>
                    </td>
                    <td colspan="3" style="height: 4px;padding: 0px;padding-left: 11px;">
                        <h5>Site Visit Date: {{$SiteVisitDate}}</h5>
                    </td>
                </tr>
            </tbody>
        </table>
        <small>* N/A: Data Not Available</small>
    </div>
</div>
@endsection