@extends('layouts/master_template')

@section('dynamicStyles')
<link rel="stylesheet" href="{{ URL::assetUrl('/AdminLTE/plugins/datepicker/datepicker3.css') }}" media="screen" title="no title" charset="utf-8">
@endsection

@section('content')
<div class="row">
    <!-- left column -->
    <div class="col-md-12">
        <!-- general form elements -->
        <div class="box box-primary">
            <!-- form start -->
            <form role="form" id="UpdateProfile" method="POST">
                <div class="box-body">
                    {{ csrf_field() }}
                    <div class="form-group row">
                        <div class="col-md-3">
                            <label for="FirstName">First name *</label>
                            <input type="text" class="form-control" name="FirstName" id="FirstName" placeholder="First Name" value="{{$firstName}}">
                        </div>
                        <div class="col-md-2">
                            <label for="MiddleName">Middle name</label>
                            <input type="text" class="form-control" name="MiddleName" id="MiddleName" placeholder="Middle Name" value="{{$middleName}}">
                        </div>
                        <div class="col-md-3">
                            <label for="LastName">Last name *</label>
                            <input type="text" class="form-control" name="LastName" id="LastName" placeholder="Last Name" value="{{$lastName}}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-3">
                            <label>Gender</label><br>
                            @if($defaultGender == "None")
                            <input type="radio" name="Gender" value="{{$defaultGender}}" checked="checked"/>
                            <input type="radio" name="Gender" value="Male" id="GenderMale"/>
                            <label for="GenderMale" tabindex="0"></label>Male&nbsp;&nbsp;&nbsp;
                            <input type="radio" name="Gender" value="Female" id="GenderFemale"/>
                            <label for="GenderFemale" tabindex="-1"></label>Female
                            @elseif($defaultGender == "Male")
                            <input type="radio" name="Gender" value="Male" id="GenderMale" checked="checked"/>
                            <label for="GenderMale" tabindex="0"></label>Male&nbsp;&nbsp;&nbsp;
                            <input type="radio" name="Gender" value="Female" id="GenderFemale"/>
                            <label for="GenderFemale" tabindex="-1"></label>Female
                            @else
                            <input type="radio" name="Gender" value="Male" id="GenderMale"/>
                            <label for="GenderMale" tabindex="0"></label>Male&nbsp;&nbsp;&nbsp;
                            <input type="radio" name="Gender" value="Female" id="GenderFemale" checked="checked"/>
                            <label for="GenderFemale" tabindex="-1"></label>Female
                            @endif
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-3">
                            <label for="DateOfBirth">Date of birth</label>
                            <div class="input-group">
                                <div class="input-group-addon"><i class="fa fa-calendar"></i></div>
                                <input type="text" class="form-control" name="DateOfBirth" id="DateOfBirth" placeholder="Select your date of birth" value="{{$dateOfBirth}}" data-date-end-date="0d"/>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-3">
                            <label for="ProfilePicture">Select profile picture</label>
                            <input type="file" class="form-control" name="ProfilePicture" id="ProfilePicture">
                        </div>
                    </div>
                </div>
                <!-- /.box-body -->
                <div class="box-footer">
                    <button type="submit" class="btn btn-primary" id="submitProfile">Update profile</button>
                </div>
            </form>
            <div class="alert alert-dismissible"></div>
        </div>
        <!-- /.box -->
    </div>
    <!-- /.box -->
</div>
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl('/AdminLTE/plugins/datepicker/bootstrap-datepicker.js') }}"></script>
<script src="{{ URL::assetUrl('/js/common.js') }}"></script>
<script src="{{ URL::assetUrl('/js/profile.js') }}"></script>
@endsection
