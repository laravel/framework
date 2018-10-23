@foreach($currentRatecard->pricePackages as $pricePackage)
    <tr>
        <td style="vertical-align:middle">
            <b>{{ $pricePackage->name }}</b>&nbsp;&nbsp;
            <i class="fa fa-history reverse-icon" aria-hidden="true" title="Future Ratecards" data-toggle="modal" data-target="#FutureRatecards" data-price-package-id="{{ $pricePackage->id }}"></i>
        </td>
        <td class="text-right customer-rate-sibling" style="vertical-align:middle">
            {{ money_format("%!i", $pricePackage->currentRatecard->customerRate) }}
        </td>
        <td class="text-right vendor-rate-sibling" style="vertical-align:middle">
            {{ money_format('%!i', $pricePackage->currentRatecard->vendorRate) }}
        </td>
        <td>
            <input type="text" name="CustomerRate{{ $pricePackage->id }}" id="CustomerRate{{ $pricePackage->id }}" class="form-control" max="99999.00" autocomplete="off" data-msg-name="Customer Rate"/>
        </td>
        <td>
            <input type="text" name="VendorRate{{ $pricePackage->id }}" id="VendorRate{{ $pricePackage->id }}" class="form-control" max="99999.00"  autocomplete="off" data-msg-name="Vendor Rate"/>
        </td>
        <td class="text-center new-start-date-sibling" style="vertical-align:middle">
            {{ $pricePackage->currentRatecard->startDate }}
        </td>
        <td>
            <div class="has-feedback">
                <input type="text" name="StartDate{{ $pricePackage->id }}" id="StartDate{{ $pricePackage->id }}" class="form-control date-picker" readonly="true" data-msg-name="Start Date"/>
                <i class="fa fa-calendar form-control-feedback"></i>
            </div>
        </td>
    </tr>
@endforeach
