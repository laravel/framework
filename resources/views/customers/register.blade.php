@extends('layouts/master_template')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <form id="CustomerRegistrationForm" method="POST" action="{{ route('customers.register') }}">
                        {{ csrf_field() }}
                        @if(count($errors->all()) > 0)
                        <div class="callout callout-danger">
                            <h4 class="no-text-transform">Resolve these Errors!</h4>
                            <ul class="errors-list mr-bt-0">
                                @foreach($errors->all() as $error)
                                <li>{{$error}}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group has-feedback">
                                    <label for="FirstName">First Name*</label>
                                    <input type="text" name="FirstName" id="FirstName" class="form-control" placeholder="Ex: John" value="{{ old('FirstName') }}"/>
                                    <i class="fa fa-user form-control-feedback" aria-hidden="true"></i>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group has-feedback">
                                    <label for="LastName">Last Name*</label>
                                    <input type="text" name="LastName" id="LastName" class="form-control" placeholder="Ex: Doe" value="{{ old('LastName') }}"/>
                                    <i class="fa fa-user form-control-feedback" aria-hidden="true"></i>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group has-feedback">
                                    <label for="Mobile">Mobile*</label>
                                    <input type="text" name="Mobile" id="Mobile" class="form-control" placeholder="Ex: 8989899898" value="{{ old('Mobile') }}" data-entity-existence-url="{{ route('check.mobile') }}"/>
                                    <i class="fa fa-phone form-control-feedback" aria-hidden="true"></i>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group has-feedback">
                                    <label for="Email">Email*</label>
                                    <input type="text" name="Email" id="Email" class="form-control" placeholder="Ex: user@example.com" value="{{ old('Email') }}" data-entity-existence-url="{{ route('check.email') }}"/>
                                    <i class="fa fa-envelope form-control-feedback" aria-hidden="true"></i>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <input type="submit" class="btn btn-primary button-custom mr-bt-10" value="Register" id="CustomerRegistrationFormSubmit" />
                                <input type="reset" class="btn button-custom mr-bt-10" value="Clear" id="CustomerRegistrationFormReset" />
                            </div>
                        </div>
                    </form>
                </div>
                <div class="form-overlay hidden" id="CustomerRegistrationFormOverlay">
                    <div class="large loader"></div>
                    <div class="loader-text">Registering Customer...</div>
                </div>
            </div>
            @if(session()->has("message"))
            <div id="NotificationArea">
                <div class="callout callout-{{ session('message.type') }}">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h4 class="title no-text-transform">{{ session('message.title') }}</h4>
                    <p class="body">{{ session('message.body') }}</p>
                </div>
            </div>
            @elseif(session()->has("error"))
            <div id="NotificationArea">
                <div class="alert alert-danger">
                    <p class="body">{{ session("error") }}</p>
                </div>
            </div>
            @else
            <div id="NotificationArea" class="hidden">
                <div class="callout callout-success">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h4 class="title no-text-transform"></h4>
                    <p class="body"></p>
                </div>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <p class="body"></p>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection

@section('dynamicStyles')
    <link href="{{ asset('/css/customers/register.css') }}" rel="stylesheet"/>
@endsection

@section('dynamicScripts')
    <script src="{{ asset('/js/common.js')}}"></script>
    <script src="{{ asset('/js/customers/register.js') }}"></script>
@endsection
