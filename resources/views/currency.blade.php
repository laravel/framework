@extends('layouts/master_template')

@section('content')
  <div class="row">
    <div class="col-xs-12">
      <div class="box" id="currency-box">
        <div class="box-header" id="currency-box-header"></div>
        <div class="alert hidden alert-dismissible" id="tableFormAlert">
          <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <span><i class="icon"></i><strong id="alert-title"></strong></span>
          <span id="alert-data"></span>
        </div>
        <div class="box-body" id="currency-box-body">
          <table id="currency" class="table table-bordered table-striped table-responsive"></table>
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
  <script src="{{ URL::assetUrl('/js/currency.js') }}"></script>
@endsection

@section('modalSection')
  <div class="modal fade" tabindex="-1" role="dialog" id="popupEditModal">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title"></h4>
        </div>
        <div class="modal-body"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
      </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
  </div><!-- /.modal -->
@endsection
