@extends('layouts/master_template')

@section('content')
  <div class="row">
    <div class="col-xs-12">
      <div class="box" id="country-box">
        <div class="box-header">
          <h3 class="box-title" id="country-box-title">Country Data Table</h3>
        </div><!-- /.box-header -->
        <div class="alert alert-dismissable hidden" id="tableFormAlert">
          <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
          <span><i class="icon"></i><strong id="alert-title"></strong></span>
          <span id="alert-data"></span>
        </div>
        <div class="box-body">
          <table id="country" class="table table-bordered table-hover"></table>
        </div><!-- /.box-body -->
      </div><!-- /.box -->
    </div><!-- /.col -->
  </div><!-- /.row -->
@endsection

@section('dynamicStyles')
  <link rel="stylesheet" href="{{ URL::assetUrl('/AdminLTE/plugins/datatables/dataTables.bootstrap.css') }}" media="screen" title="no title" charset="utf-8">
  <link rel="stylesheet" href="{{ URL::assetUrl('/css/custome.css') }}" media="screen" title="no title" charset="utf-8">
@endsection

@section('dynamicScripts')
  <script src="{{ URL::assetUrl('/AdminLTE/plugins/datatables/jquery.dataTables.min.js') }}"></script>
  <script src="{{ URL::assetUrl('/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js') }}"></script>
  <script src="{{ URL::assetUrl('/js/datatables_generic.js') }}"></script>
  <script src="{{ URL::assetUrl('/js/country.js') }}"></script>
@endsection
