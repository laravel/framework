@extends('layouts/master_template')

@section('dynamicStyles')
  <link rel="stylesheet" href="{{ URL::assetUrl('/css/custome.css') }}" media="screen" title="no title" charset="utf-8">
@endsection

@section('content')
  <div class="row">
    <!-- left column -->
    <div class="col-md-12">
      <!-- general form elements -->
      <div class="box box-primary">
        <!-- form start -->
        <form role="form" id="ChangePassword" method="post">
          <div class="box-body">
            {{ csrf_field() }}
            <div class="form-group row">
                <div class="col-md-4">
                    <label for="CurrentPassword">Current password</label>
                    <input type="password" class="form-control" name="CurrentPassword" id="CurrentPassword" placeholder="Enter your current password">
                </div>  
            </div>
            <div class="form-group row">
                <div class="col-md-4">
                    <label for="NewPassword">New password</label>
                    <input type="password" class="form-control" name="NewPassword" id="NewPassword" placeholder="Enter a new password">
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-4">
                    <label for="ConfirmPassword">Confirm password</label>
                    <input type="password" class="form-control" name="ConfirmPassword" id="ConfirmPassword" placeholder="Confirm the new password">
                </div>
            </div>
          </div>
          <!-- /.box-body -->
          <div class="box-footer">
            <button type="submit" class="btn btn-primary" id="updatePassword">Update password</button>
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
  <script src="{{ URL::assetUrl('/js/common.js') }}"></script>
  <script src="{{ URL::assetUrl('/js/changePassword.js') }}"></script>
@endsection
