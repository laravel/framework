@extends('layouts/Pdfs/PDFTemplate')

@section('content')
        <div class="box box-primary">
        <div class="box-header with-border text-center pd-5"><b>RateCard Item Master Report</b></div>
            <div class="box-body pd-0">
              <table class="table table-striped table-bordered"> 
                    <thead class="bg-light-blue text-center">
                       <tr style="page-break-inside: avoid !important; font-size: 12px;"> 
                            <th class="rate-text-center" width="3%">#</th> 
                            <th class="rate-text-center">Name</th>
                            <th class="rate-text-center" width="7%">Unit</th>
                            <th class="rate-text-center">Customer Rate(&#8377;)</th> 
                            <th class="rate-text-center">Vendor Rate (&#8377;)</th> 
                            <th class="rate-text-center">Price Package</th>
                            <th class="rate-text-center" width="11%">Start Date</th>
                            <th class="rate-text-center">Created Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($HistoryRate as $Key => $Card)
                        <tr class="pd-2" style="page-break-inside: avoid !important;"> 
                            <td class="text-center text-vertical-align">{{ $Key + 1 }}</td>
                            <td>{{$Card->ItemName}}</td>
                            <td class="text-vertical-align">{{$Card->UnitName}}</td>
                            <td class="rate-text-center">{{ money_format('%!i', $Card->CustomerRate )}}</td>
                            <td class="rate-text-center">{{ money_format('%!i', $Card->VendorRate )}}</td>
                            <td class="text-vertical-align">{{$Card->Name}}</td>
                            <td class="text-vertical-align">{{Carbon\Carbon::parse($Card->StartDate)->format('d-M-Y')}}</td>
                            <td class="text-vertical-align">{{Carbon\Carbon::parse($Card->CreatedAt)->addHours(5)->addMinutes(30)->format("d-M-Y h:i A")}}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
@endsection