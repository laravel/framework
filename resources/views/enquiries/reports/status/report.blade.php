@extends('layouts/master_template')

@section('dynamicStyles')

@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            @if($Enquiries->isEmpty())
            <div class="box-header with-border">
                <div class="callout callout-info mr-8">
                    <p>You have not yet added status for enquiries.</p>
                </div>
            </div>
            @else
            <div class="box-body"> 
                <div class="mr-bt-9">
                    @foreach($Status as $status)
                    <a target="_blank" href="{{ route('enquiry.reports.by.status', ['status' => $status == "" ? 'NA' : $status["id"]]) }}">
                        <span 
                            class="label label-default cursor-pointer" 
                            style="background-color: #9a9a9a; color: #fff;"
                            > {!! !$status ? 'N/A' : $status["name"] !!}</span>
                    </a>
                    @endforeach
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead style="border-top: 1px solid #f4f4f4;" class="bg-light-blue text-center">
                            <tr>
                                <th width="50%" class="text-vertical-align text-center">Status</th>
                                <th width="50%" class="text-vertical-align text-center">Count</th>
                            </tr>
                        </thead>
                        <tbody style="border-top: 1px solid #f4f4f4;">
                            @foreach($Enquiries as $status)
                            @if($status["status"] != "InActive")
                            <tr>
                                <td class="text-vertical-align">
                                    <a target="_blank" href="{{ route('enquiry.reports.by.status', ['status' => $status["status"] =="" ? 'NA' : $status["status"]["id"]]) }}">
                                        {!! !$status["status"] ? '<small>N/A</small>' : $status["status"]["name"] !!}
                                    </a>
                                </td>
                                <td class="text-vertical-align text-center">
                                    <a target="_blank" href="{{ route('enquiry.reports.by.status', ['status' => $status["status"] =="" ? 'NA' : $status["status"]['id']]) }}">
                                        {{ $status["count"] }}
                                    </a>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="row text-center mr-bt-20 mr-tp-20">
                    <a href="{{ route('enquiry.reports.download', ['type' => 'pdf']) }}" id="DownloadPDF">
                        <button type="button" class="btn btn-primary mr-rt-25">
                            <i class="fa fa-file-pdf-o"></i> Download PDF
                        </button>
                    </a>
                    <a href="{{ route('enquiry.reports.download', ['type' => 'excel']) }}" id="DownloadExcel">
                        <button type="button" class="btn btn-primary">
                            <i class="fa fa-file-excel-o"></i> Download Excel
                        </button>
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('dynamicScripts')

@endsection