@extends('layouts/master_template')

@section('dynamicStyles')
<link rel="stylesheet" href="{{ asset('/plugins/select2/select2.min.css') }}">
<link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
@endsection

@section('content')
<div id="RecommendationsReportsPage" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <form 
                        id="RecommendationsReportForm" 
                        method="POST" 
                        action="{{ route('category.recommendations.finalized.items') }}">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="Project">Project*</label>
                                    <select 
                                        name="Project" 
                                        id="Project" 
                                        class="form-control" 
                                        data-api-end-point="{{ route("category.recommendations.qerooms", ["id" => '']) }}" 
                                        >
                                        <option value="">Select</option>
                                        @foreach($projects as $project)
                                        <option 
                                            value="{{ $project["id"] }}" 
                                            data-quick-estimation-id="{{ $project["quickestimateid"] }}"
                                            >{{ $project["name"] }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="Room">Room</label>
                                    <select 
                                        name="Room" 
                                        id="Room" 
                                        class="form-control"
                                        > 
                                        <option value="">Select</option> 
                                        <option v-for="room in rooms" :value="room.Id">
                                            @{{ room.Name }}
                                        </option>   
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="Category">Category</label>
                                    <select 
                                        name="Category" 
                                        id="Category" 
                                        class="form-control"
                                        > 
                                        <option value="">Select</option>
                                        @foreach($category as $categ)
                                        <option 
                                            value="{{ $categ->id }}"
                                            data-category-slug="{{ $categ->slug }}"
                                            >{{ $categ->name }}
                                        </option>
                                        @endforeach 
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row mr-tp-10">
                            <div class="col-md-4">
                                <input type="submit" class="btn btn-primary button-custom mr-bt-10" value="Search" id="SearchSubmit" />
                                <input type="reset" class="btn button-custom mr-bt-10" value="Clear" id="SearchReset" />
                            </div>
                        </div>
                    </form>
                </div>
                <div class="box-body no-padding hidden pd-bt-10" id="MaterialsListBox">
                    
                </div>
                <div 
                    class="overlay" style="height: 123%;" 
                    :class="{hidden: projectFormOverlay}"
                    >
                    <div class="large loader"></div>
                    <div class="loader-text">@{{overLayMessage}}</div>
                </div>
                <overlay-notification 
                    :form-over-lay="formOverLay" 
                    :notification-icon="notificationIcon" 
                    :notification-message="notificationMessage" 
                    @clearmessage="clearOverLayMessage()" 
                    >   
                </overlay-notification>
            </div>
        </div>
    </div>
</div>
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/jquery.dataTables.min.js")}}"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="{{ asset("js/common.js") }}"></script>
<script src="{{ asset('/js/categoryrecommendations/reports.js') }}"></script>
@endsection