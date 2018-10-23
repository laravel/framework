@extends('layouts/Pdfs/PDFTemplate')

@section('content')
<div class="box box-primary">
    <div class="box-header with-border text-center pd-5">
        <b>Enquiry Reports</b>
    </div>
    <div class="box-body no-padding">
        <table class="table table-bordered">
            <thead style="border-top: 1px solid #f4f4f4;" class="bg-light-blue">
                <tr style="page-break-inside: avoid !important;">
                    <th width="50%" class="text-vertical-align text-center">Status</th>
                    <th width="50%" class="text-vertical-align text-center">Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($Enquiries as $status)
                @if($status["status"] != "InActive")
                <tr class="pd-2" style="page-break-inside: avoid !important;">
                    <td class="text-vertical-align">
                        {!! !$status["status"] ? '<small>N/A</small>' : $status["status"]["name"] !!}
                    </td>
                    <td class="text-vertical-align text-center">{{ $status["count"] }}</td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
