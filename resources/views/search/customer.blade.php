@extends('layouts/master_template')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary" id="App">
                <div class="box-header with-border">
                    <form id="SearchCustomersForm" method="POST" action="{{route('customers.search')}}">
                        {{csrf_field()}}
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
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="FirstName">First Names</label>
                                    <input type="text" name="FirstName" id="FirstName" class="form-control" placeholder="Ex: John" value="{{ old('FirstName') }}"/>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="LastName">Last Name</label>
                                    <input type="text" name="LastName" id="LastName" class="form-control" placeholder="Ex: Doe" value="{{ old('LastName') }}"/>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="Mobile">Mobile</label>
                                    <input type="text" name="Mobile" id="Mobile" class="form-control" placeholder="Ex: (898) 989-9898" value="{{ old('Mobile') }}"/>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="Email">Email</label>
                                    <input type="text" name="Email" id="Email" class="form-control" placeholder="Ex: user@example.com" value="{{ old('Email') }}"/>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <input type="submit" class="btn btn-primary button-custom mr-bt-10" value="Search" id="SearchCustomersFormSubmit" />
                                <input type="reset" class="btn button-custom mr-bt-10" value="Clear" id="SearchCustomersFormReset" />
                            </div>
                        </div>
                    </form>
                </div>
                <div class="box-body hidden table-responsive" id="SearchResultsBody">
                    <table class="table table-striped table-bordered dataTable" id="SearchResults">
                        <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                            <tr>
                                <th width="8%" class="text-center text-vertical-align pd-rt-8 text-center">S.No</th>
                                <th width="25%" class="text-center text-vertical-align">First Name</th>
                                <th width="22%" class="text-center text-vertical-align">Last Name</th>
                                <th width="25%" class="text-center text-vertical-align">Email</th>
                                <th width="14%" class="text-center text-vertical-align">Mobile</th>
                                <th width="6%"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="form-overlay hidden" id="SearchCustomersFormOverlay">
                    <div class="large loader"></div>
                    <div class="loader-text">Fetching Results...</div>
                </div>
            </div>
            <div id="NotificationArea">
                <app-notification :notification="notify" v-if="notify.status"></app-notification>
            </div>
        </div>
    </div>
@endsection

@section('dynamicStyles')
    <link href="{{ asset('/AdminLTE/plugins/datatables/dataTables.bootstrap.css') }}" rel="stylesheet"/>
    <link href="{{asset('/css/search/customers.css')}}" rel="stylesheet"/>
@endsection

@section('dynamicScripts')
    <script src="{{ asset('/AdminLTE/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
    <script src="{{asset('/js/common.js')}}"></script>
    <script src="https://unpkg.com/vue/dist/vue.js"></script>
    <script src="{{asset('/js/search/customers.js')}}"></script>
@endsection
