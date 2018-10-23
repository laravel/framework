
@if(count($categoryItems) < 1)
<div class="callout callout-info mr-tp-15 mr-bt-15">
    No materials found for provided input.
</div>
@else
<div class="pd-4">
    <small class="pull-right" style="font-size: 75%;">
        <i class="fa fa-info-circle text-light-blue" aria-hidden="true"></i>
        Name: can be Design Name, Shade Name, Product Code based on Category, 
        Number: can be Design Code, Shade Code, Design Number based on Category
    </small>
</div>
<div class="table-responsive mr-tp-10" v-else>
    <table class="table table-bordered no-footer" id="ItemsTable">
        <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue">
                <tr>
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
                <tr class="bg-info">
                    <td class="rate-text-center" colspan="8">
                        <b>{{ $category["Name"] }}</b>
                    </td>  
                </tr>
                @endif
                @foreach($category["items"] as $key => $item)
                <tr>
                    <td class="rate-text-center" width="4%">{{ $key+ 1 }}</td> 
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
<div class="row text-center mr-bt-20 mr-tp-20">
    @if(($isCategorySelected && $isRoomSelected) || (!$isCategorySelected))
    <a href="{{ route('finalized.items.download', ['projectid' => $project, 'roomid' => $room, 'categoryid' => $catgory]) }}" id="DownloadPDF">
        <button type="button" class="btn btn-primary mr-rt-25">
            <i class="fa fa-file-pdf-o"></i> Download PDF
        </button>
    </a>
    @else
    <a href="{{ route('catgory.finalized.items.pdf', ['projectid' => $project, 'categoryid' => $catgory]) }}" id="DownloadPDF">
        <button type="button" class="btn btn-primary mr-rt-25">
            <i class="fa fa-file-pdf-o"></i> Download PDF
        </button>
    </a>
    @endif
</div>
@endif
