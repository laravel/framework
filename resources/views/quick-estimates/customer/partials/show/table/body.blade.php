<tbody id="CreateQuickEstimateTableBody">
    <?php $index = 1;?>
    @foreach ($roomItems as $room)
        {{-- Room section header --}}
        <tr class="bg-info text-center room-items">
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
                    <i class="fa fa-rupee"></i>
                    <span class="text-bold">{{ $pricePackage->amount() }}</span>
                </td>
            @endforeach
            <td width="15%"></td>
        </tr>
        {{-- Loop over items collection in room object --}}
        @foreach ($room->items as $item)
            <tr>
                <td class="text-center text-vertical-align items-index" width="5%">{{ $index++ }}</td>
                <td class="text-vertical-align" width="30%">
                    {{-- Estimation item description --}}
                    {{ $item->description }} ({{ $units->where("id", $item->unitId)->first()->name }})
                    {{-- Estimation item comments --}}
                    @if ($item->comments->isNotEmpty())
                        <span class="text-aqua comments-tooltip pd-lt-5" data-comments="{{ $item->comments->toJson() }}">
                            <i class="fa fa-info-circle text-light-blue" aria-hidden="true"></i>
                        </span>
                    @endif
                    {{-- Estimation item notes --}}
                    @if ($item->isNotesNotEmpty())
                        <span class="text-danger notes-tooltip pd-lt-5" title="{{ $item->notes }}">
                            <i class="fa fa-exclamation-triangle text-black" aria-hidden="true"></i>
                        </span>
                    @endif
                    {{-- Estimation item reference images --}}
                    @if ($item->images->isNotEmpty())
                        {{-- Loop over multiple reference images --}}
                        @foreach ($item->images as $image)
                            <span class="text-danger cursor-pointer reference-images pd-lt-5" data-url="{{ $image }}">
                                <i class="fa fa-image text-black" aria-hidden="true"></i>
                            </span>
                        @endforeach
                    @endif
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
                <td class="text-center text-vertical-align" width="15%">{{ $item->customNotes }}</td>
            </tr>
        @endforeach
    @endforeach
</tbody>
