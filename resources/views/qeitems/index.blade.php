@extends('layouts/master_template')

@section('content')
    <div class="row">
        <div class="col-md-12">     
            <div class="box box-primary">
                <div class="box-header with-border">
                    <form id="QEItemsForm" method="GET" action="">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="Room">Room</label>
                                    <select name="Room" id="Room" class="form-control">
                                        <option value="">Select Room</option>
                                        @foreach ($rooms as $room)
                                            @if ($room->id == $currentRoom)
                                                <option value="{{ $room->id }}" selected="selected">{{ $room->name }}</option>
                                            @else
                                                <option value="{{ $room->id }}">{{ $room->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="RatecardItem">Ratecard Item</label>
                                    <select name="RatecardItem" id="RatecardItem" class="form-control">
                                        <option value="">Select Ratecard</option>
                                        @foreach ($ratecardItems as $item)
                                            @if ($item->id == $currentRatecardItem)
                                                <option value="{{ $item->id }}" selected="selected">{{ $item->name }}</option>
                                            @else
                                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row mr-tp-10">
                            <div class="col-md-4">
                                <input type="submit" class="btn btn-primary button-custom mr-bt-10" value="Search" id="QEItemsFormSubmit"/>
                                <a href="{{ route("qe-items.qeitemsearch") }}" id="QEItemsFormReset">
                                    <button type="button" class="btn button-custom mr-bt-10">Clear</button>
                                </a>
                            </div>
                        </div>
                    </form>
                    <div class="overlay hidden" id="QEItemsFormLoader">
                        <div class="large loader"></div>
                        <div class="loader-text">Fetching Results...</div>
                    </div>
                </div>
                <div class="box-body mr-tp-10 no-padding table-responsive">
                    <div class="box-header ">
                        <h3 class="box-title no-text-transform">Master Report</h3>
                    </div>
                    <table class="table table-bordered" id="QEItemsList">
                        <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                            <tr>
                                <th width="8%" class="text-center text-vertical-align pd-rt-8">S.No</th>
                                <th width="15%" class="text-center text-vertical-align">Rooms</th>
                                <th width="15%" class="text-center text-vertical-align">Description</th>
                                <th width="20%" class="text-center text-vertical-align">Ratecard Item</th>
                                <th width="26%" class="text-center text-vertical-align">Mapped Item/Items</th>
                                <th width="10%" class="text-center text-vertical-align">Status</th>
                                <th width="6%" class="text-center text-vertical-align">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as  $index=>$item)
                                <tr>
                                    <td class="text-center text-vertical-align">{{ $loop->iteration }}</td>
                                    <td class="text-center text-vertical-align">{{ $item->rooms }}</td>
                                    <td class="text-center text-vertical-align">{{ $item->description }}</td>
                                    <td class="text-center text-vertical-align">{{ $item->itemName }}</td>
                                    <td class="text-center text-vertical-align">{{ isset($mappeditems[$index]) ? $mappeditems[$index]["ItemName"] :  "N/A" }}</td>
                                    <td class="text-center text-vertical-align">
                                        @if ($item->isActive())
                                            <span class="label label-success">Active</span>
                                        @else
                                            <span class="label label-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-center text-vertical-align">
                                        <a data-toggle="tooltip" title="" data-original-title="Edit" href="{{ route('qe-items.edit', ['id' => $item->id]) }}" role="button" class="mr-rt-3">
                                            <i class="fa fa-fw fa-pencil-square-o text-black"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="row text-center mr-bt-20 mr-tp-20">
                        <a href="{{ $downloadPDFUrl }}" id="DownloadPDF">
                            <button type="button" class="btn btn-primary mr-rt-25">
                                <i class="fa fa-file-pdf-o"></i> Download PDF
                            </button>
                        </a>
                        <a href="{{ $downloadSpreadsheetUrl }}" id="DownloadExcel">
                            <button type="button" class="btn btn-primary">
                                <i class="fa fa-file-excel-o"></i> Download Excel
                            </button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section("dynamicStyles")
    <link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
    <link href="{{ asset("css/vendor/select2.min.css") }}" rel="stylesheet"/>
@endsection

@section("dynamicScripts")
    <script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/jquery.dataTables.min.js")}}"></script>
    <script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
    <script src="{{ asset("js/vendor/select2.min.js") }}"></script>
    <script src="{{ asset("js/qeitems/list.min.js") }}"></script>
@endsection

