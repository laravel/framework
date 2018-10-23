@extends('layouts/Pdfs/PDFTemplate')

@section('content')
    <div class="box box-primary">
        <div class="box-body no-padding">
            <table class="table table-bordered">
                <thead class="bg-light-blue text-center">
                    <tr style="page-break-inside: avoid !important;">
                        <th width="8%" class="text-center text-vertical-align pd-rt-8">S.No</th>
                        <th width="15%" class="text-center text-vertical-align">Rooms</th>
                        <th width="15%" class="text-center text-vertical-align">Description</th>
                        <th width="40%" class="text-center text-vertical-align">Ratecard Item</th>
                        <th width="10%" class="text-center text-vertical-align">Mapped Item/Items</th>
                        <th width="12%" class="text-center text-vertical-align">Status</th>
                    </tr>
                </thead>
                <tbody style="border-top: 1px solid #f4f4f4">
                    @foreach ($items as $index => $item)
                        <tr>
                            <td width="6%" class="text-center text-vertical-align">{{ $loop->iteration }}</td>
                            <td width="15%" class="text-center text-vertical-align">{{ $item->rooms }}</td>
                            <td width="15%" class="text-center text-vertical-align">{{ $item->description }}</td>
                            <td width="35%" class="text-center text-vertical-align">{{ $item->itemName }}</td>
                            <td width="15%" class="text-center text-vertical-align">{{ isset($mappeditems[$index]) ? $mappeditems[$index]["ItemName"] :  "N/A" }}</td>
                            <td width="10%" class="text-center text-vertical-align">
                                @if ($item->isActive())
                                    <span class="label label-success">Active</span>
                                @else
                                    <span class="label label-danger">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
