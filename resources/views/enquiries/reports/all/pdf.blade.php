@extends('layouts/Pdfs/PDFTemplate')

@section('content')
<div class="box box-primary">
    <div class="box-header with-border text-center pd-5">
        <b>Enquiry Status Reports</b>
    </div>
    <div class="box-body no-padding">
        <table class="table table-striped table-bordered">
            <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                <tr style="page-break-inside: avoid !important; font-size: 12px;">
                    <th class="text-center text-vertical-align pd-rt-8">#</th>
                    <th width="12%" class="text-center text-vertical-align">
                        Enquiry No<br>Enquiry Date
                    </th>
                    <th class="text-center text-vertical-align">
                        Customer Name<br>Email<br>Mobile
                    </th>
                    <th width="20%" class="text-center text-vertical-align">
                        Project Name<br>
                        Unit Type<br>
                        Site Address
                    </th>
                    <th class="text-center text-vertical-align">Super Builtup Area</th>
                    <th class="text-center text-vertical-align">Status</th>
                    <th class="text-center text-vertical-align">Status Description</th>
                    <th class="text-center text-vertical-align">Is Awarded</th>
                </tr>
            </thead>
            <tbody>
                @foreach($Enquiries as $index => $result)
                <tr class="pd-2" style="page-break-inside: avoid !important;">
                    <td width="" class="text-vertical-align text-center">{{ $index + 1 }}</td>
                    <td width="" class="text-vertical-align">
                        {{ $result["EnquiryReferenceNumber"] }}<br>
                        {{ \Carbon\Carbon::parse($result["EnquiryCreatedAt"])->addHours(5)->addMinutes(30)->format("d-M-Y") }}
                    </td>
                    <td width="" class="text-vertical-align">
                        {{ $result["FirstName"] . " " . $result["LastName"] }}<br>
                        {{ $result["Email"] }}<br>
                        {{ $result["Mobile"] }}
                    </td>
                    <td width="" class="text-vertical-align">
                        <b>{!! $result["ProjectName"] ? $result["ProjectName"] : "<small>N/A</small>" !!}</b><br>
                        {!! $result["UnitType"] !!}<br>
                        {!! $result["Address"] ? $result["Address"] :  "<small>N/A</small>" !!}
                    </td>
                    <td width=""class="text-vertical-align">
                        {!! $result["SuperBuiltUpArea"] ? $result["SuperBuiltUpArea"]: "<small>N/A</small>" !!}
                    </td>
                    <td width="" class="text-vertical-align">
                        {!! $result["EnquiryStatus"] !!}
                    </td>
                    <td width="" class="text-vertical-align">
                        @if(!empty($result["StatusDescription"]))
                        @foreach($result["StatusDescription"] as $desc)
                        <p>
                            <strong>{{ $loop->index+1 }}.</strong> {{ $desc->desc }}
                        </p>
                        @endforeach
                        @else
                        <small>N/A</small>
                        @endif
                    </td>
                    <td width="" class="text-vertical-align">
                        {!! $result["IsAwarded"] !!}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
