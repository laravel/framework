@if(!isset($roomsOnly))
<tr class="bg-blue text-center">
    <th class="text-center" width="4%">#</th>
    <th class="text-center" width="30%">Items</th>
    <th class="text-center" width="3%">Pay</th>
    <th class="text-center cursor-help" width="3%" title="Select, if this item is required for estimation">Req?</th>
    <th class="text-center cursor-help" width="5%" title="Required Quantity, if applicable">Nos</th>
    <th class="text-center cursor-help" width="5%" title="Width of the Item, if applicable">W</th>
    <th class="text-center cursor-help" width="5%" title="Height/Length of the Item, if applicable">H/L</th>
    <th class="text-center cursor-help" width="5%" title="Depth of the Item, if applicable">D</th>
    <th class="text-center brand-bg-color" width="10%">Amount</th>
    <th class="text-center hechpe-bg-color" width="10%">Amount</th>
    <th class="text-center market-bg-color" width="10%">Amount</th>
    <th class="text-center" width="10%">Notes</th>
</tr>
@endif
@if(!empty($Rooms))
<?php $Cnt = 1; ?>
@foreach ($Rooms as $Room)
@if(!in_array($Room['Id'],$DefaultRooms))
<tr class="bg-info text-center room-items">
    <td width="4%"></td>
    <td width="30%" id="{{$Room['Id']}}" class="rooms"><span class="pull-left pd-lt-10">{{$Room['Name']}}</span></td>
    <td width="26%" colspan="6"><span class="pull-right mr-rt-10">Section Subtotal &nbsp;<i class="fa fa-arrow-right smallarrow" aria-hidden="true"></i></span></td>
    <td width="10%" class="brand-bg-color"></td>
    <td width="10%" class="hechpe-bg-color"></td>
    <td width="10%" class="market-bg-color"></td>
    <td width="10%"></td>
</tr>
@foreach ($Room['EstimationItems'] as $Key => $EstimationItem)
<tr>
    <td class="text-center text-vertical-align items-index" width="4%">{{ $Cnt++ }}</td>
    <td class="text-vertical-align" width="30%">
        <input type="hidden" name="{{ $EstimationItem["Id"] }}-RoomId" value="{{ $Room["Id"] }}"/>
        {{ $EstimationItem['Description'] }}
        @if(!empty($EstimationItem['Comment']))
        <span class="text-aqua comments-tooltip" data-toggle="tooltip" title="{{ $EstimationItem['Comment'] }}"><i class="fa fa-info-circle text-light-blue" aria-hidden="true"></i></span>
        @endif
        @if(!empty($EstimationItem['ItemNote']))
        <span class="text-danger notes-tooltip" data-toggle="tooltip" title="{{ $EstimationItem['ItemNote'] }}"><i class="fa fa-exclamation-triangle text-black" aria-hidden="true"></i></span>
        @endif
        @if($EstimationItem['Image'] !== 'false')
        <span class="text-danger CursorPointer PopOver" data-toggle="popover" data-placement="right"  data-html="true" data-content="<div class='media'><a href='#'><img src='{{$EstimationItem['Image']}}' class='media-object img-responsive' alt='Sample Image'></a></div>"><i class="fa fa-image text-black" aria-hidden="true"></i></span>
        @endif
    </td>
    <td class="text-center text-vertical-align" width="3%">
        @if(!empty($EstimationItem['PaymentBy']))
        <span class="comments-tooltip" data-toggle="tooltip" title="{{ $EstimationItem['PaymentBy']['Description'] }}">
            <img src="{{ URL::CDN($EstimationItem['PaymentBy']['ImagePath'])}}" alt="{{ $EstimationItem['PaymentBy']['ShortCode'] }}">
        </span>
        @endif
    </td>
    <td class="text-center text-vertical-align" width="3%">
        <input type="checkbox" name="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Required" id="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Required" {{ ($EstimationItem['IsPreSelected'] == 1) ? 'checked' : '' }} {{ ($EstimationItem['IsPreSelected'] == 1 && $EstimationItem['IsDeselectable'] == 0) ? 'disabled' : '' }}  class="checkbox" data-room="{{$Room['Id']}}"/>
        <label for="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Required" tabindex="0"></label>
    </td>
    <td class="text-center text-vertical-align" width="5%">
        <input type="text" name="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Quantity" id="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Quantity" class="form-control input-sm text-center" value="{{ $EstimationItem['DefaultQuantity']}}" {{($EstimationItem['IsQuantityEditable'] == 1) ? '' : 'disabled'}} data-roomid="{{$Room['Id']}}"/>
    </td>
    <td class="text-center text-vertical-align" width="5%">
        <input type="text" name="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Width" id="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Width" class="form-control input-sm text-center" value="{{$EstimationItem['Width']}}" {{($EstimationItem['IsDimEditable'] == 1) ? '' : 'disabled'}} data-roomid="{{$Room['Id']}}"/>
    </td>
    <td class="text-center text-vertical-align" width="5%">
        <input type="text" name="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Height" id="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Height" class="form-control input-sm text-center" value="{{ $EstimationItem['Height'] }}" {{ ($EstimationItem['IsDimEditable'] == 1) ? '' : 'disabled' }} data-roomid="{{$Room['Id']}}"/>
    </td>
    <td class="text-center text-vertical-align" width="5%">{{ $EstimationItem['Depth'] }}</td>
    @foreach ($PricePackages as $PricePackage)
    <td class="text-center text-vertical-align item-rates" width="10%">
        {{ $EstimationItem['PricePackage'][$PricePackage['Id']]['CustomerRate'] }}
    </td>
    @endforeach
    <td class="text-center text-vertical-align" width="10%">
        <textarea name="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Notes" rows="1" class="form-control input-sm user-notes" style="resize:none" placeholder="Notes"></textarea>
    </td>
</tr>
@endforeach
@endif
@endforeach
@foreach ($Rooms as $Room)
@if(in_array($Room['Id'],$DefaultRooms))
<tr class="bg-info text-center room-items">
    <td width="4%"></td>
    <td width="30%" id="{{$Room['Id']}}" class="rooms"><span class="pull-left pd-lt-10">{{$Room['Name']}}</span></td>
    <td width="26%" colspan="6"><span class="pull-right mr-rt-10">Section Subtotal &nbsp;<i class="fa fa-arrow-right smallarrow" aria-hidden="true"></i></span></td>
    <td width="10%" class="brand-bg-color"></td>
    <td width="10%" class="hechpe-bg-color"></td>
    <td width="10%" class="market-bg-color"></td>
    <td width="10%"></td>
</tr>
@foreach ($Room['EstimationItems'] as $Key => $EstimationItem)
<tr>
    <td class="text-center text-vertical-align items-index" width="4%">{{ $Cnt++ }}</td>
    <td class="text-vertical-align" width="30%">{{ $EstimationItem['Description'] }}
        @if(!empty($EstimationItem['Comment']))
        <span class="text-aqua comments-tooltip" data-toggle="tooltip" title="{{ $EstimationItem['Comment'] }}"><i class="fa fa-info-circle text-light-blue" aria-hidden="true"></i></span>
        @endif
        @if(!empty($EstimationItem['ItemNote']))
        <span class="text-danger notes-tooltip" data-toggle="tooltip" title="{{ $EstimationItem['ItemNote'] }}"><i class="fa fa-exclamation-triangle text-black" aria-hidden="true"></i></span>
        @endif
        @if($EstimationItem['Image'] !== 'false')
        <span class="text-danger CursorPointer PopOver" data-toggle="popover" data-placement="right" data-html="true" data-content="<div class='media'><a href='#'><img src='{{$EstimationItem['Image']}}' class='media-object img-responsive' alt='Sample Image'></a></div>"><i class="fa fa-image text-black" aria-hidden="true"></i></span>
        @endif
    </td>
    <td class="text-center text-vertical-align" width="3%">
        @if(!empty($EstimationItem['PaymentBy']))
        <span class="comments-tooltip" data-toggle="tooltip" title="{{ $EstimationItem['PaymentBy']['Description'] }}">
            <img src="{{ URL::CDN($EstimationItem['PaymentBy']['ImagePath'])}}" alt="{{ $EstimationItem['PaymentBy']['ShortCode'] }}">
        </span>
        @endif
    </td>
    <td class="text-center text-vertical-align" width="3%">
        <input type="checkbox" name="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Required" id="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Required" {{ ($EstimationItem['IsPreSelected'] == 1) ? 'checked' : '' }} {{ ($EstimationItem['IsPreSelected'] == 1 && $EstimationItem['IsDeselectable'] == 0) ? 'disabled' : '' }}  class="checkbox" data-room="{{$Room['Id']}}"/>
        <label for="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Required" tabindex="0"></label>
    </td>
    <td class="text-center text-vertical-align" width="5%">
        <input type="text" name="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Quantity" id="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Quantity" class="form-control input-sm text-center" value="{{ $EstimationItem['DefaultQuantity']}}" {{($EstimationItem['IsQuantityEditable'] == 1) ? '' : 'disabled'}} data-roomid="{{$Room['Id']}}"/>
    </td>
    <td class="text-center text-vertical-align" width="5%">
        <input type="text" name="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Width" id="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Width" class="form-control input-sm text-center" value="{{$EstimationItem['Width']}}" {{($EstimationItem['IsDimEditable'] == 1) ? '' : 'disabled'}} data-roomid="{{$Room['Id']}}"/>
    </td>
    <td class="text-center text-vertical-align" width="5%">
        <input type="text" name="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Height" id="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Height" class="form-control input-sm text-center" value="{{ $EstimationItem['Height'] }}" {{ ($EstimationItem['IsDimEditable'] == 1) ? '' : 'disabled' }} data-roomid="{{$Room['Id']}}"/>
    </td>
    <td class="text-center text-vertical-align" width="5%">{{ $EstimationItem['Depth'] }}</td>
    @foreach ($PricePackages as $PricePackage)
    <td class="text-center text-vertical-align item-rates" width="10%">
        {{ $EstimationItem['PricePackage'][$PricePackage['Id']]['CustomerRate'] }}
    </td>
    @endforeach
    <td class="text-center text-vertical-align" width="10%">
        <textarea name="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Notes" rows="1" class="form-control input-sm user-notes" style="resize:none" placeholder="Notes"></textarea>
    </td>
</tr>
@endforeach
@endif
@endforeach
@else
<tr class="bg-info text-center text-vertical-align">
    <td colspan="12" class="text-center">No Items Found</td>
</tr>
@endif
<script>
  $('.PopOver').popover({
        placement : 'right',
        trigger : 'manual',
        html : true
    }).on("mouseenter", function () {
        var _this = this;
        $(this).popover("show");
        $(this).siblings(".popover").on("mouseleave", function () {
            $(_this).popover('hide');
        });
    }).on("mouseleave", function () {
        var _this = this;
        setTimeout(function () {
            if (!$(".popover:hover").length) {
                $(_this).popover("hide")
            }
        }, 300);
    });
</script>