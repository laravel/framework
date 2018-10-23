@extends('layouts/master_template')

@section('content')
<div class="row" id="EnquiryReportsPage">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-body table-responsive hidden" id="EnquiryListBox">
                <table class="table table-striped table-bordered" id="EnquiryReportTable">
                    <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                        <tr>
                            <th width="" class="text-center text-vertical-align pd-rt-8">#</th>
                            <th width="12%" class="text-center text-vertical-align">
                                Enquiry No<br>Enquiry Date
                            </th>
                            <th width="" class="text-center text-vertical-align">
                                Customer Name<br>Email<br>Mobile
                            </th>
                            <th width="20%" class="text-center text-vertical-align">
                                Project Name<br>
                                Unit Type<br>
                                Site Address
                            </th>
                            <th>Super Builtup Area</th>
                            <th>Status</th>
                            <th>Status Description</th>
                            <th>Is Awarded</th>
                        </tr>
                    </thead>
                </table>
                <div class="row text-center mr-bt-20 mr-tp-20 download-options">
                    <a 
                        :href="DownloadRoute+'/'+statusPdf+'/'+typePdf" id="DownloadPDF">
                        <button type="button" class="btn btn-primary mr-rt-25">
                            <i class="fa fa-file-pdf-o"></i> Download PDF
                        </button>
                    </a>
                    <a 
                        :href="DownloadRoute+'/'+statusExcel+'/'+typeExcel" id="DownloadExcel">
                        <button type="button" class="btn btn-primary">
                            <i class="fa fa-file-excel-o"></i> Download Excel
                        </button>
                    </a>
                </div>
            </div>
            <div class="callout callout-info mr-tp-15 mr-bt-15 hidden no-enquiries">
                You have not yet added status for enquiries.
            </div>
        </div>
    </div>
</div>
@endsection

@section('dynamicStyles')
<link href="{{ asset('/AdminLTE/plugins/datatables/dataTables.bootstrap.css') }}" rel="stylesheet"/>
@endsection

@section('dynamicScripts')
<script src="{{ asset('/AdminLTE/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="{{ asset('/js/enquiries/reports/list.js') }}"></script>
@endsection
