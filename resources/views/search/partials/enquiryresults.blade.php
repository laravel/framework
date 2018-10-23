@foreach($results as $index => $result)
    <tr>
        <td width="" class="text-vertical-align text-center">{{ $index + 1 }}</td>
        <td width="" class="text-vertical-align">
            {{ $result["EnquiryReferenceNumber"] }}<br>
            {{ $result["EnquiryCreatedAt"] }}
        </td>
        <td width="" class="text-vertical-align">
            {{ $result["FirstName"] . " " . $result["LastName"] }}<br>
            {{ $result["Email"] }}<br>
            {{ $result["Mobile"] }}
        </td>
        <td width="" class="text-vertical-align">
            <b>{!! $result["ProjectName"] ? $result["ProjectName"] : "<small>N/A</small>" !!}</b><br>
            {!! $result["UnitType"] !!}<br>
            {!! $result["Address"] ? $result["Address"] :  "<small>N/A</small>" !!}
        </td>
        <td width=""class="text-vertical-align">
            {!! $result["SuperBuiltUpArea"] ? $result["SuperBuiltUpArea"]: "<small>N/A</small>" !!}
        </td>
        <td width="" class="text-vertical-align">
            {!! $result["EnquiryStatus"] !!}
        </td>
        <td width="" class="text-vertical-align">
            @if(!empty($result["StatusDescription"]))
            @foreach($result["StatusDescription"] as $desc)
            <p>
                <b>{{ $loop->index+1 }}.</b> {{ $desc->desc }}
            </p>
            @endforeach
            @else
                <small>N/A</small>
            @endif
        </td>
        <td width="" class="text-vertical-align">
            {!! $result["IsAwarded"] !!}
        </td>
        <td width="" class="text-center text-vertical-align" style="overflow:visible">
            <span class="dropdown">
                <a class="dropdown-toggle" data-toggle="dropdown" href="" role="button" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-caret-square-o-down" aria-hidden="true"></i>
                </a>
                <ul class="dropdown-menu" aria-labelledby="result-dropdown">
                    <li>
                        <a href="{{ route('enquiry', ['id' => $enquiryKeys[$result['CustomerId']]]) }}">
                            <i class="fa fa-plus" aria-hidden="true"></i> New Enquiry
                        </a>
                    </li>
                    <li role="separator" class="divider"></li>
                    @if($result["editEnquiryKey"])
                    <li>
                        <a href="{{ route('enquiry', ['id' => $result['editEnquiryKey']]) }}">
                            <i class="fa fa-pencil" aria-hidden="true"></i> Edit Enquiry
                        </a>
                    </li>
                    @endif
                    <li>
                        <a href="{{ route('enquiries.show', [
                            'enquiryreference' => $result['EnquiryReferenceNumber'],
                            'userid' => $result['CustomerId']
                        ]) }}" class="view-enquiry-link">
                            <i class="fa fa-eye" aria-hidden="true"></i> View Enquiry
                        </a>
                    </li>
                    <li role="separator" class="divider"></li>
                    @if($result["SubmittedAt"])
                    <li>
                        <a href="{{ route('users.enquiries.quick-estimates.index', [
                            'user' => $result['CustomerId'],
                            'enquiry' => $result['UserFormDataId'],
                        ]) }}">
                            <i class="fa fa-bars" aria-hidden="true"></i> List Quick Estimates
                        </a>
                    </li>
                    @endif
                </ul>
            </span>
        </td>
    </tr>
@endforeach
