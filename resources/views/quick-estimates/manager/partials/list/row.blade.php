<tr>
    <td width="4%" class="text-vertical-align text-center">{{ $loop->iteration }}</td>
    <td width="9%" class="text-vertical-align text-center">{{ $estimate->referenceNumber }}</td>
    <td width="10%" class="text-vertical-align text-center">{{ $estimate->enquiryReferenceNumber }}</td>
    <td width="20%" class="text-vertical-align text-center">{{ $estimate->address }}</td>                                    
    <td width="9%" class="text-vertical-align text-center">{{ $estimate->unitType }}</td>
    @foreach ($estimate->pricePackages as $pricePackage)
        <td width="12%" class="text-vertical-align text-center">
            <i class="fa fa-rupee" aria-hidden="true"></i>
            <span>{{ $pricePackage->amount() }}</span>
        </td>
    @endforeach
    <td width="8%" class="text-vertical-align text-center">{{ $estimate->workType }}</td>
    <td width="4%" class="text-center text-vertical-align">
        <span class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                <i class="fa fa-caret-square-o-down" aria-hidden="true"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-right">
                @include("quick-estimates.manager.partials.list.dropdown")
            </ul>
        </span>
    </td>
</tr>
