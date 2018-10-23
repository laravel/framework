<?php $count = 1; ?>
@foreach($users as $index => $user)
@foreach($user->quickEstimates as $quickEstimate)
<tr>
    <td width="3%" class="text-vertical-align">{{ $count }}</td>
    <td width="10%" class="text-vertical-align">{{ $user->name }}</td>
    <td width="10%" class="text-vertical-align">{{ $quickEstimate->referenceNumber }}</td>
    <td width="10%" class="text-vertical-align">
        <div>{{ $quickEstimate->enquiry["referenceNumber"] }}</div>
        <div>{{ $quickEstimate->enquiry["enquiryName"] }}</div>
    </td>
    <td width="18%" class="text-vertical-align">
        <div><b>{{ $quickEstimate->enquiry["projectName"] }}</b></div>
        <div>{{ $quickEstimate->enquiry["builderName"] }}</div>
        <div>{{ $quickEstimate->siteAddress }}</div>
    </td>
    <td width="8%" class="text-vertical-align">{{ $quickEstimate->enquiry["unitType"] }}</td>
    <td width="8%" class="text-vertical-align">{{ $quickEstimate->enquiry["workType"] }}</td>
    <td width="10%" class="text-vertical-align">{{ $quickEstimate->pricePackages[2]->price }}</td>
    <td width="10%" class="text-vertical-align">{{ $quickEstimate->pricePackages[1]->price }}</td>
    <td width="10%" class="text-vertical-align">{{ $quickEstimate->pricePackages[0]->price }}</td>
    <td width="3%" class="text-vertical-align">
        <span class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                <i class="fa fa-caret-square-o-down" aria-hidden="true"></i>
            </a>
            <ul class="dropdown-menu" aria-labelledby="result-dropdown">
                <li>
                    <a href="{{ route('users.enquiries.quick-estimates.show', [
                        'user' => $user->id,
                        'enquiry' => $quickEstimate->enquiry["id"],
                        'estimate' => $quickEstimate->id,
                    ]) }}">
                        <i class="fa fa-eye" aria-hidden="true"></i> View Estimation
                    </a>
                </li>
                <li>
                    <a href="{{ route('users.enquiries.quick-estimates.download', [
                        'user' => $user->id,
                        'enquiry' => $quickEstimate->enquiry["id"],
                        'estimate' => $quickEstimate->id,
                        'Type' => "PDF",
                    ]) }}">
                        <i class="fa fa-file-pdf-o" aria-hidden="true"></i> Download PDF
                    </a>
                </li>
                <li>
                    <a href="{{ route('users.enquiries.quick-estimates.copy', [
                        'user' => $user->id,
                        'enquiry' => $quickEstimate->enquiry["id"],
                        'estimate' => $quickEstimate->id,
                    ]) }}">
                        <i class="fa fa-clone" aria-hidden="true"></i> Copy as New
                    </a>
                </li>
            </ul>
        </span>
    </td>
</tr>
<?php $count++; ?>
@endforeach
@endforeach
