@extends('layouts/master_template')

@section('content')
    <div class="row">
        <!-- left column -->
        <div class="col-md-12">
            <!-- general form elements -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <a href="{{route('enquiry', ['id' => $FormUniqueKey])}}" title="" class="btn btn-primary btn-sm pull-right" id="NewEnquiry"><i class="fa fa-plus" aria-hidden="true">
                        </i>&nbsp;&nbsp;Make a New Enquiry
                    </a>
                </div>
                @include('layouts/partials/UserEnqueriesList')
            </div>
            <!-- /.box -->
        </div>
        <!-- /.box -->
    </div>
@endsection

@section('dynamicStyles')
<link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/jquery.dataTables.min.js")}}"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="{{ URL::assetUrl('/js/EnquiryList.js') }}"></script>
@endsection
