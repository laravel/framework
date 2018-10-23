@extends('layouts/master_template')

@section('content')
<div class="box box-primary">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-6 text-left" style="padding-top: 13px;">
                <h5><?= $ChecklistTimestamps; ?></h5>
            </div>
            <div class="col-md-6 text-right">
                <?= $CustomerSiteData ?>
            </div>
        </div>
    </div>
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-12">
                <h4 style="color:black" class="no-text-transform">Builder contact person at site for any coordination / emergency</h4>
                <h5>{{$BContactPName}}</h5>
                <h5>{{$BContactPNumber}}</h5>
            </div>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-12">
                <table class="table table-bordered">
                    <caption class="table-caption">
                        <h4 style="color:black">Checklist Information</h4>
                    </caption>
                    <tbody>
                        <tr>
                            <td colspan="3" width="50%">
                                <h5><b>1. Is Handover from builder done?</b></h5>
                            </td>
                            <td colspan="3" width="50%">
                                <h5>{{$HandoverDone}}</h5>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <h5><b>2. Has the society / builder NoC / permission been taken for interior work?</b></h5>
                            </td>
                            <td colspan="3"> 
                                <h5>{{$NoCForInteriorWork}}</h5>
                            </td>
                        </tr>
                        @if(!empty($downloadLinkForInterNoccopy))
                        <tr>
                            <td colspan="6">
                                <h5><?= $downloadLinkForInterNoccopy ?></h5>
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <td colspan="3">
                                <h5><b>3. In case of any civil / structural changes, is NoC / Permission from society / builder taken?</b></h5>
                            </td>
                            <td colspan="3">
                                <h5>{{$NoCForCivilWork}}</h5>
                            </td>
                        </tr>
                        @if(!empty($downloadLinkForCivilNocCopy))
                        <tr>
                            <td colspan="6">
                                <h5><?= $downloadLinkForCivilNocCopy ?></h5>
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <td colspan="3">
                                <h5><b>4. Is the security deposit, if any, paid to the society / Builder?</b></h5>
                            </td>
                            <td colspan="3">
                                <h5>{{$isSecDepositGiven}}</h5>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="6">
                                <h5><b>5. Work timings</b></h5>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" width="50%">
                                <h5>Working days: <?= $workingDays ?>.</h5>
                                <h5>{{$workOnPubHoliday}}</h5>
                            </td>
                            <td colspan="3" width="50%">
                                <h5>Work Start Time: {{$WorkStartTime}}.</h5>
                                <h5>Work End Time: {{$WorkEndTime}}.</h5>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <h5><b>6. Is power supply available 24x7 at work site?</b></h5>
                            </td>
                            <td colspan="3">
                                <h5>{{$PowerSupplyAvailble}}</h5>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <h5><b>7. Power shutdown time slot / day, if any</b></h5>
                            </td>
                            <td colspan="3"><h5><?= $PowerCutTime ?></h5></td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <h5><b>8. Is service / goods lift available for material movement?</b></h5>
                            </td> 
                            <td colspan="3">
                                <h5>{{$LiftAvForMatMovement}}</h5>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <h5><b>9. Do we need to take any permission for using goods / service for each use?</b></h5>
                            </td>
                            <td colspan="3">
                                <h5>{{$GoodUsePermission}}</h5>
                            </td>
                        </tr>
                        @if(!empty($ContactPersonName))
                        <tr> 
                            <td colspan="6">
                                <h5>{{$GoodsUsePerText}}</h5>
                                <h5>{{$ContactPersonName}}.</h5>
                                <h5>{{$ContactPersonNumber}}.</h5>
                            </td>                          
                        </tr>
                        @endif
                        <tr>
                            <td colspan="3">
                                <h5><b>10. Do we need security passes for the work staff?</b></h5>
                            </td>
                            @if(!empty($ProcessToGetPass))
                            <td colspan="3">
                                <h5>{{$ProcessToGetPass}}</h5>
                            </td>
                            @else
                            <td colspan="3">
                                <h5>{{$SecPassNeeded}}</h5>
                            </td>
                            @endif
                        </tr>
                        <tr>
                            <td colspan="3">
                                <h5><b>11. Do we need to submit ID proofs for all HECHPE staff and contractors at Security?</b></h5>
                            </td>
                            <td colspan="3">
                                <h5>{{$NeedToSubIdCard}}</h5>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <h5><b>12. Is water available at work site?</b></h5>
                            </td>
                            <td colspan="3">
                                <h5>{{$IsWaterAvaialable}}</h5>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <h5><b>13. Can workers stay overnight at the work site?</b></h5>
                            </td>
                            <td colspan="3">
                                <h5>{{$CanWorkStayAtNight}}</h5>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <h5><b>14. Can workers prepare food at work site?</b></h5>
                            </td>
                            <td colspan="3">
                                <h5>{{$CanWorkPrepareFood}}</h5>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <h5><b>15. Location for Restroom / Toilets for workers</b></h5>
                            </td>
                            <td colspan="3">
                                <h5><?= $WorkersRestRoom ?></h5>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <h5><b>16. Parking location for HECHPE Staff and Contractors</b></h5>
                            </td>
                            <td colspan="3">
                                <h5><?= $ParkingLocation ?></h5>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <h5><b>17. Garbage Disposal Process / Guidelines</b></h5>
                            </td>
                            <td colspan="3">
                                <h5><?= $GarbageDisGuidelines ?></h5>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <h5><b>18. Is there any other guidelines to be followed as per society / association?</h5>
                            </td>
                            <td colspan="3">
                                <h5>{{$SocietyGuidelines}}</h5>
                            </td>
                        </tr>
                        @if($isSocietyGuidExists === "Yes")
                        <tr>
                            <td colspan="6">
                                @if(!empty($SocietyGuidelinesText))
                                <h5><?= $SocietyGuidelinesText ?></h5>
                                @endif
                                @if(!empty($DownloadLinkForSocGuidelines))
                                <h5><?= $DownloadLinkForSocGuidelines ?></h5>
                                @endif
                            </td>
                        </tr>
                        @endif
                        @if(!auth()->user()->isCustomer())
                        <tr><td colspan="6"></td></tr> 
                        <tr>
                            <td colspan="6"><h4 class="no-text-transform" style="color: black">For HECHPE Spaces Office Use</h4>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="6">
                                <h5><b>All Items checked</b></h5>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <h5><?= $AllItemsChecked ?></h5>
                            </td>
                            <td colspan="3">
                                <h5>Checked Date: <?= $CheckedDate ?></h5>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <h5>Checked By: <?= $CheckedBy ?></h5>
                            </td>
                            <td colspan="3">
                                <h5>Signature: <small>N/A</small></h5>
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
                <small>* N/A: Data Not Available</small>
            </div>
        </div>
    </div>
</div>
@endsection

@section('dynamicStyles')
<link rel="stylesheet" href="{{ asset('css/workchecklist/checklist.css') }}">
@endsection