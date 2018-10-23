@extends('layouts/Pdfs/PDFTemplate')

@section('content')
<div class="box box-primary">
    <div class="box-header with-border text-center br-1 pd-5">
        <b>Finalized Materials Report</b>
    </div>
    <div class="box-header with-border" style="text-align: right">
        <small style="font-size: 65%;">
            <span style="color: #3c8dbc;">&#8505;</span>
            Name: can be Design Name, Shade Name, Product Code based on Category, 
            Number: can be Design Code, Shade Code, Design Number based on Category
        </small>
    </div>
    <div class="box-body pd-0">
        <table class="table table-bordered">
            <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue">
                <tr style="page-break-inside: avoid !important;">
                    <th class="rate-text-center pd-10">#</th>
                    <th class="rate-text-center">Items</th>
                    <th class="rate-text-center">Brand</th>
                    <th class="rate-text-center">Sub Brand</th>
                    <th class="rate-text-center">Name</th>
                    <th class="rate-text-center">Number</th>
                    <th class="rate-text-center">Finalized By</th>
                </tr>
            </thead>
            <tbody style="border-top: 1px solid #f4f4f4">
                @foreach($categoryItems as $category)
                    @if(!$isCategorySelected)
                    <tr class="bg-info pd-2" style="page-break-inside: avoid !important;">
                        <td class="rate-text-center" colspan="8">
                            <b>{{ $category["Name"] }}</b>
                        </td>  
                    </tr>
                    @endif
                    @foreach($category["items"] as $key => $item)
                    <tr style="page-break-inside: avoid !important;">
                        <td class="rate-text-center" width="3%">{{ $key+ 1 }}</td> 
                        <td class="text-vertical-align" width="11%">{{ $item["RoomArea"] }}</td>  
                        <td class="text-vertical-align" width="11%">{{ $item["Brand"] }}</td> 
                        <td class="text-vertical-align" width="12%">{{ $item["SubBrand"] }}</td>  
                        <td class="text-vertical-align" width="11%">{{ $item["Name"] }}</td> 
                        <td class="text-vertical-align" width="11%">{!! $item["Number"] !!}</td>  
                        <td class="text-vertical-align" width="13%">{{ $item["ShortlistedBy"] }}</td> 
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
