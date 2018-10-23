@extends('layouts/master_template')

@section('dynamicStyles')
<link rel="stylesheet" href="{{ URL::assetUrl("/css/magnific-popup.css")}}">
<link rel="stylesheet" href="{{ URL::assetUrl("/css/sitemeasurement/firesprinkler.css")}}">
@endsection

@section('content')
<div id="ViewFireSprinklersPage">
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
                <div class="callout callout-info" v-if="Sprinklers.length === 0">
                    <p>No Fire Sprinklers found.</p>
                </div>
                <div class="box-body no-padding" v-else>
                    <div class="table-responsive pd-10">
                        <table class="table table-bordered table-hover table-striped">
                            <caption>
                                <h4 class="mr-tp-0">{{$RoomArea}}</h4>
                            </caption>
                            <thead>
                                <tr>
                                    <th class="text-center text-vertical-align" width="25%">Wall<i class="fa fa-question-circle cursor-help help-icon mr-lt-2" data-toggle="tooltip" data-original-title="Specified wall direction"></i></th>
                                    <th class="text-center text-vertical-align" width="25%">PFC
                                        <i class="fa fa-question-circle cursor-help help-icon mr-lt-2" data-toggle="tooltip" data-original-title="Position from the Ceiling"></i>
                                    </th>
                                    <th class="text-center text-vertical-align" width="25%">PFLW
                                        <i class="fa fa-question-circle cursor-help help-icon mr-lt-2" data-toggle="tooltip" data-original-title="Position from left side Wall"></i>
                                    </th>
                                    <th class="text-center text-vertical-align" width="25%">Attachments</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(sprinkler, index) in Sprinklers">
                                    <td class="text-center text-vertical-align">@{{sprinkler.WallDirection}}</td>
                                    <td class="text-center text-vertical-align">@{{sprinkler.PFC}}</td>
                                    <td class="text-center text-vertical-align">@{{sprinkler.PFLW}}</td>
                                    <td class="text-center text-vertical-align" >
                                        <div class="image-link">
                                            <a :href="CdnUrl+JSON.parse(sprinkler.Attachments)[0].Path">
                                                <img :src="CdnUrl+JSON.parse(sprinkler.Attachments)[0].Path" alt="Image not available" class="fullimage-thumbnail cursor-zoom-in" :title="JSON.parse(sprinkler.Attachments)[0].UserFileName" @click.prevent="initializeThumbnailsPopup(sprinkler.Attachments, CdnUrl)">
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
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
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl('/js/sitemeasurement/firesprinkler.js') }}"></script>
@endsection