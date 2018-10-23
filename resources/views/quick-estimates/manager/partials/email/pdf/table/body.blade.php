<tbody id="CreateQuickEstimateTableBody"  style="page-break-inside:avoid!important;">
    <?php $index = 1;?>
    @foreach ($roomItems as $room)
        {{-- Room section header --}}
        <tr class="bg-info text-center room-items" style="page-break-inside:avoid!important;">
            <td width="5%"></td>
            <td width="20%" class="rooms">
                <span class="pull-left pd-lt-10">{{ $room->name }}</span>
            </td>
            <td width="18%" colspan="3">
                <span class="pull-right mr-rt-10">Section Subtotal &nbsp;
                    <i class="fa fa-arrow-right smallarrow" aria-hidden="true"></i>
                </span>
            </td>
            @foreach ($room->pricePackages as $pricePackage)
                <td width="10%" class="{{ $pricePackage->class }}">
                    <span class="text-bold">&#8377; {{ $pricePackage->amount() }}</span>
                </td>
            @endforeach
            <td width="27%"></td>
        </tr >
        {{-- Loop over items collection in room object --}}
        @foreach ($room->items as $item)
            <tr style="page-break-inside:avoid!important;">
                <td class="text-center text-vertical-align items-index" width="5%">{{ $index++ }}</td>
                <td class="text-vertical-align" width="20%">
                    {{-- Estimation item description --}}
                    {{ $item->description }}
                </td>
                <td class="text-center text-vertical-align" width="3%">
                    @if ($item->isPaymentByNotEmpty())
                        <span class="payment-tooltip cursor-pointer" title="{{ $item->paymentBy->description }}">
                            <img src="{{ $item->paymentBy->image }}" alt="{{ $item->paymentBy->shortcode }}"/>
                        </span>
                    @endif
                </td>
                <td class="text-center text-vertical-align pd-lt-3 pd-rt-3" width="5%">{{ $item->quantity }}</td>
                <td class="text-center text-vertical-align" width="10%">{{ $item->width }} x {{ $item->height }} x {{ $item->depth }}</td>
                @foreach ($item->pricePackages as $pricePackage)
                    <td class="text-center text-vertical-align item-rates" width="10%">
                        {{ $pricePackage->amount() }}
                    </td>
                @endforeach
                <td class="text-vertical-align" width="27%">
                    {{-- Estimation item comments --}}
                    @if ($item->comments->isNotEmpty())
                        @foreach ($item->comments as $comment)
                        <p style="margin: 0px !important; font-size: 10px !important;">{{ $comment }}</p>
                        @endforeach
                    @endif
                    {{-- Estimation item notes --}}
                    @if ($item->isNotesNotEmpty())
                        <p style="margin: 0px !important; font-size: 10px !important;">{{ $item->notes }}</p>
                    @endif
                    {{-- Customer notes --}}
                        <p style="margin: 0px !important; font-size: 10px !important;">{{ $item->customNotes }}</p>
                </td>
            </tr>
        @endforeach
    @endforeach
    <tr class="bg-primary bg-blue pd-2" style="page-break-inside:avoid!important;">
        <th colspan="5" class="text-right text-vertical-align" width="43%">
            <span class="pull-right mr-rt-10">Total</span>
        </th>
        @foreach ($estimate->totals as $total)
            <th class="text-center text-vertical-align amount-text {{ $total->class }}" width="10%">
                <span class="text-bold" style="font-size:14px;">&#8377; {{ $total->amount() }}</span>
                <div class="text-bold">{{ $total->name }}</div>
            </th>
        @endforeach
        <th width="27%"></th>
    </tr>
</tbody>
