<tbody id="CreateQuickEstimateTableBody">
    @foreach ($roomItems as $room)
        {{-- Room section header --}}
        <tr class="bg-info text-center room-items" style="page-break-inside:avoid!important;">
            <td width="5%"></td>
            <td width="30%" class="rooms">
                <span class="pull-left pd-lt-10">{{ $room->name }}</span>
            </td>
            <td width="20%" colspan="3">
                <span class="pull-right mr-rt-10">Section Subtotal &nbsp;
                    <i class="fa fa-arrow-right smallarrow" aria-hidden="true"></i>
                </span>
            </td>
            @foreach ($room->pricePackages as $pricePackage)
                <td width="10%" class="{{ $pricePackage->class }}">
                    <span class="text-bold">&#8377; {{ $pricePackage->amount() }}</span>
                </td>
            @endforeach
            <td width="15%"></td>
        </tr>
        {{-- Loop over items collection in room object --}}
        @foreach ($room->items as $item)
            <tr>
                <td class="text-center text-vertical-align items-index" width="5%">{{ $loop->iteration }}</td>
                <td class="text-vertical-align" width="30%">
                    {{-- Estimation item description --}}
                    {{ $item->description }} ({{ $units->where("id", $item->unitId)->first()->name }})
                </td>
                <td class="text-center text-vertical-align" width="5%">
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
                <td class="text-vertical-align" width="15%">
                    {{-- Estimation item comments --}}
                    @if ($item->comments->isNotEmpty())
                        @foreach ($item->comments as $comment)
                            <small>{{ $comment }}</small><br/>
                        @endforeach
                    @endif
                    {{-- Estimation item notes --}}
                    @if ($item->isNotesNotEmpty())
                        <small>{{ $item->notes }}</small><br/>
                    @endif
                    {{-- Customer notes --}}
                    <span>{{ $item->customNotes }}</span>
                </td>
            </tr>
        @endforeach
    @endforeach
</tbody>
