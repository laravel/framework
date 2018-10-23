@foreach($results as $index => $result)
    <tr>
        <td class="text-center">{{ $index + 1 }}</td>
        <td>
        {{ $result["FirstName"] }} 
        @if($result["IsUserActive"] === "0")
        <i class="fa fa-flag text-red" aria-hidden="true" title="User not validated"></i>
        @endif
        </td>
        <td>{{ $result["LastName"] }}</td>
        <td>
        {{ $result["Email"] }}
        @if($result["IsEmailValidated"] === "0")
        <i class="fa fa-flag text-red" aria-hidden="true" title="Email not validated"></i>
        @endif
        </td>
        <td>
        {{ $result["Mobile"] }}
        @if($result["IsMobileValidated"] === "0")
        <i class="fa fa-flag text-red" aria-hidden="true" title="Mobile not validated"></i>
        @endif
        </td>
        <td class="text-center">
            @if(($result["IsUserActive"] === "1") && ($result["IsMobileValidated"] === "1"))
            <span class="dropdown">
                <a class="dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-caret-square-o-down" aria-hidden="true"></i>
                </a>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                    <li>
                        <a href="{{ route('enquiry', ['id' => $result['EnquiryKey']]) }}">
                            <i class="fa fa-file-text-o" aria-hidden="true"></i> New Enquiry
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('enquiries.index', ['userid' => $result['UserId']]) }}">
                            <i class="fa fa-bars" aria-hidden="true"></i> List Enquiries
                        </a>
                    </li>
                    <li class="divider" role="separator"></li>
                    <li>
                        <a href="{{ route('users.enquiries.select.quick-estimates.create', $result['UserId']) }}">
                            <i class="fa fa-check-square-o" aria-hidden="true"></i> New Quick Estimate
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('users.quick-estimates.index', $result['UserId']) }}">
                            <i class="fa fa-bars" aria-hidden="true"></i> List Quick Estimates
                        </a>
                    </li>
                </ul>
            </span>
            @endif
        </td>
    </tr>
@endforeach
