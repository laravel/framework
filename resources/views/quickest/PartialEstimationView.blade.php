<?php $i=1; ?>
@foreach($EstimationList as $QuickEst)
<tr>
    <td class="text-center">{{$i}}</td>
    <td>{{$QuickEst['ReferenceNumber']}}</td>
    <td>{!! $QuickEst['Enquiry'] !!}</td>
    <td>{!! $QuickEst['SiteAddress'] !!}</td>                                    
    <td>{{$QuickEst['UnitType']}}</td>
    <td class="text-center"><span class="SumAmount1">&#8377;{{ money_format('%!.0n', $QuickEst['SumAmount1']) }}</span></td>
    <td class="text-center"><span class="SumAmount1">&#8377;{{ money_format('%!.0n', $QuickEst['SumAmount2']) }}</span></td>
    <td class="text-center"><span class="SumAmount1">&#8377;{{ money_format('%!.0n', $QuickEst['SumAmount3']) }}</span></td>
    <td>{{ $QuickEst['WorkType'] }}</td>
    <td class="text-center">
        <span class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                <i class="fa fa-caret-square-o-down" aria-hidden="true"></i>
            </a>
            <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                <li>
                    <a href="{{route('quickestimate.show', ['quickestrefno' => $QuickEst['ReferenceNumber']])}}">
                        <i class="fa fa-eye" aria-hidden="true"></i> View Estimation
                    </a>
                </li>
                <li>
                    <a href="{{route('quickestimate.pdf', ['quickestrefno' => $QuickEst['ReferenceNumber']])}}">
                        <i class="fa fa-file-pdf-o" aria-hidden="true"></i> Download PDF
                    </a>
                </li>
                <li>
                    <a href="{{ route('quickestimate.duplicate', ['refno' => $QuickEst['ReferenceNumber'], 'cityid' => $QuickEst['CityId']]) }}">
                        <i class="fa fa-clone" aria-hidden="true"></i> Copy as New
                    </a>
                </li>
            </ul>
        </span>
    </td>
</tr>
<?php $i++; ?>
@endforeach