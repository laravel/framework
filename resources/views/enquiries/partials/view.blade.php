<div class="modal-header">
    <div class="pull-right">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -8px;">
            <span aria-hidden="true">&times;</span>
        </button>
        <img id="enq-view-logo" style="height: 28px; width: 125px; margin-top: -0.2em; margin-right: 10px;" src="{{ URL::CDN($PDFSettings['Header']['Logo'])}}" alt="">
    </div>
</div>
<div class="modal-body">
    <div class="row">
        <div class="col-md-6 col-sm-6 text-left EnquiryInfo" style="padding-top: 13px;">
            <h4>{{ $ReferenceNumber }}</h4>
            <h5>{{ $EnquiryName }}</h5>
            <?= $EnquiryTimestamps; ?>
        </div>
        <div class="col-md-6 col-sm-6 text-right">
            <h3>{{$CustomerName}}</h3>
            <h5>{{$CustomerMobile}} | {{$CustomerEmail}}</h5>
            <?= $CustomerAddress; ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <table class="table table-bordered">
                <caption class="table-caption">
                    <h4 style="color:black">Site Information</h4>
                </caption>
                <tbody>
                    <tr>
                    <td colspan="3" width="50%"><?= $SiteAddress; ?></td>
                    <td colspan="3" width="50%"><?= $SiteInformation; ?></td>
                    </tr>
                    <tr>
                    <td colspan="6">
                        <h5><b>Project Plan: </b><?= $ProjectPlanFileName; ?></h5>
                    </td>
                    </tr>
                    <tr>
                    <td colspan="6">
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
                    <td colspan="6">
                        <h5><b>Important Dates</b></h5>
                    </td>
                    </tr>  
                    <tr>
                    <td colspan="2" width="33.33%"><h5>Expected Start Date: {{$ExpectedStartDate}}</h5></td>
                    <td colspan="2" width="33.33%"><h5>{{$IsHandoverDone}}</h5></td>
                    <td colspan="2" width="33.33%"> <h5>{{$IsDateFlexible}}</h5></td>
                    </tr>
                    <tr>
                    <td colspan="2" width="33.33%"><h5>Expected Completion Date: {{$ExpectedEndDate}}</h5></td>
                    <td colspan="2" width="33.33%">
                        @if($HandoverDone == 0 && isset($ExpectedHandoverDate))
                        <h5> Expected Handover Date: {{$ExpectedHandoverDate}}</h5>
                        @endif
                    </td>
                    <td colspan="2" width="33.33%"><h5>Vaastu Date: {{$VaastuDate}}</h5></td>
                    </tr>
                    <tr>
                    <td colspan="6"></td>
                    </tr>
                    <tr>
                    <td colspan="6">
                        <h5><b>Comments: </b>{{$Comments}}</h5>
                    </td>
                    </tr>
                    <tr>
                    <td colspan="3">
                        <h5>HECHPE Office Visit Date: {{$OfficeVisitDate}}</h5>
                    </td>
                    <td colspan="3">
                        <h5>Site Visit Date: {{$SiteVisitDate}}</h5>
                    </td>
                    </tr>
                    @if(auth()->user()->isManager() || auth()->user()->isSales())
                    <tr>
                    <td colspan="6"><h5><b>Notes and Actions</b></h5></td>
                    </tr>
                    @if($Notes->isNotEmpty())
                    @foreach($Notes as $key => $note)
                    <tr>
                    <td colspan="6">
                    <span><strong>{{ucwords($note->Users->Person->FirstName. " ". $note->Users->Person->LastName)}}</strong></span>
                    <span class="text-muted pull-right">{{Carbon\Carbon::parse($note->CreatedAt)->addHours(5)->addMinutes(30)->format("d-M-Y h:i A")}}</span>
                    <p class="EnquiryAction">{{$note->Description}}</p>
                    @if($note->Type == 2)
                    <span><strong>Due Date: </strong>{{Carbon\Carbon::parse($note->DueDate)->format("d-M-Y")}}</span><br>
                    <span><strong>Assigned To: </strong>{{ucwords($note->AssignedToUser->Person->FirstName. " ". $note->AssignedToUser->Person->LastName)}}</span><br>
                    <span><strong>Status: </strong>
                        @if($note->Status)
                        <span class="label label-{{$StatusLabel[$note->Status]}}">{{$Status[$note->Status]}}</span>
                        @else
                        <small>N/A</small>
                        @endif
                    </span>
                    @endif
                    </td>
                    </tr>
                    @endforeach
                    @else
                    <tr>
                    <td colspan="6">N/A</td>
                    </tr>
                    @endif
                    @endif
                </tbody>
            </table>
            <small>* N/A: Data Not Available</small>
        </div>
    </div>
</div>
