@extends('layouts/master_template')

@section('dynamicStyles')
<link rel="stylesheet" href="{{ URL::assetUrl("/css/magnific-popup.css")}}">
<link rel="stylesheet" href="{{ URL::assetUrl("/css/sitemeasurement/viewac.css")}}">
@endsection

@section('content')
<div id="ViewAcPage" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <div class="row">
                        <div class="col-md-12">
                            <button name="Back" title="Go back to View Measurement Page" class="btn btn-primary btn-custom fl-rt back-btn mr-rt-0" onclick="window.location ='{{$SiteMeasViewPageRoute}}'">
                                Back
                            </button>
                        </div>
                    </div>
                </div>
                <div class="callout callout-info" v-if="!AcData">
                    <p>No Ac data found.</p>
                </div>
                <div v-else>
                    <div class="box-header with-border">
                        <div class="row">
                            <div class="col-md-3">
                                <p><strong>Wall:</strong> @{{AcData.WallDirection}}</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-1">
                                <strong>Attachments: </strong>
                                <div class="image-link">
                                    <a :href="CdnUrl+JSON.parse(AcData.Attachments)[0].Path">
                                        <img :src="CdnUrl+JSON.parse(AcData.Attachments)[0].Path" alt="Image not available" class="fullimage-thumbnail cursor-zoom-in" :title="JSON.parse(AcData.Attachments)[0].UserFileName" @click.prevent="initializeThumbnailsPopup(AcData.Attachments, CdnUrl)">
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-header with-border">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-striped">
                                <caption>
                                    <h4 class="mr-tp-0">AC Details</h4>
                                </caption>
                                <thead>
                                    <tr>
                                        <th class="text-center text-vertical-align" width="33.33%"></th>
                                        <th class="text-center text-vertical-align" width="33.33%">PFC
                                            <i class="fa fa-question-circle cursor-help help-icon mr-lt-2" data-toggle="tooltip" data-original-title="Position from the Ceiling"></i>
                                        </th>
                                        <th class="text-center text-vertical-align" width="33.33%">PFLW
                                            <i class="fa fa-question-circle cursor-help help-icon mr-lt-2" data-toggle="tooltip" data-original-title="Position from left side Wall"></i>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-vertical-align">Power Point</td>
                                        <td class="text-center text-vertical-align" v-html="AcData.PowerPoint.IsAvailable ? AcData.PowerPoint.PFC: '<small>N/A</small>'"></td>
                                        <td class="text-center text-vertical-align" v-html="AcData.PowerPoint.IsAvailable ? AcData.PowerPoint.PFLW: '<small>N/A</small>'"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-vertical-align">Drainage Point</td>
                                        <td class="text-center text-vertical-align" v-html="AcData.DrainagePoint.IsAvailable ? AcData.DrainagePoint.PFC: '<small>N/A</small>'"></td>
                                        <td class="text-center text-vertical-align" v-html="AcData.DrainagePoint.IsAvailable ? AcData.DrainagePoint.PFLW: '<small>N/A</small>'"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-vertical-align">Core Cutting</td>
                                        <td class="text-center text-vertical-align" v-html="AcData.CoreCutting.IsAvailable ? AcData.CoreCutting.PFC: '<small>N/A</small>'"></td>
                                        <td class="text-center text-vertical-align" v-html="AcData.CoreCutting.IsAvailable ? AcData.CoreCutting.PFLW: '<small>N/A</small>'"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-vertical-align">Copper Cutting</td>
                                        <td class="text-center text-vertical-align" v-html="AcData.CopperCutting.IsAvailable ? AcData.CopperCutting.PFC: '<small>N/A</small>'"></td>
                                        <td class="text-center text-vertical-align" v-html="AcData.CopperCutting.IsAvailable ? AcData.CopperCutting.PFLW: '<small>N/A</small>'"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="box-header with-border">
                        <h5 style="font-size:15px;">Outdoor Unit</h5>
                        <div class="row">
                            <div class="col-md-12">
                                <strong class="pull-left mr-rt-2">Notes:</strong>
                                <p v-html="(AcData.OutdoorUnit.Notes) ? AcData.OutdoorUnit.Notes : '<small>N/A</small>'"></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-1">
                                <strong>Attachments: </strong>
                                <div class="image-link" v-if="AcData.OutdoorUnit.Attachments">
                                    <a :href="CdnUrl+JSON.parse(AcData.OutdoorUnit.Attachments)[0].Path">
                                        <img :src="CdnUrl+JSON.parse(AcData.OutdoorUnit.Attachments)[0].Path" alt="Image not available" class="fullimage-thumbnail cursor-zoom-in" :title="JSON.parse(AcData.OutdoorUnit.Attachments)[0].UserFileName" @click.prevent="initializeThumbnailsPopup(AcData.OutdoorUnit.Attachments, CdnUrl)">
                                    </a>
                                </div>
                                <p v-else><small>N/A</small></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button name="Back" title="Go back to View Measurement Page" class="btn btn-primary btn-custom fl-rt back-btn mr-rt-10 mr-bt-10" onclick="window.location ='{{$SiteMeasViewPageRoute}}'">
                                    Back
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl('/js/sitemeasurement/viewac.js') }}"></script>
@endsection