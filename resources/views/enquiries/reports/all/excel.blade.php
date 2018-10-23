<table class="table table-striped table-bordered">
    <thead style="border-top: 1px solid #f4f4f4;" class="bg-light-blue text-center">
        <tr>
            <th style="text-align:center;">#</th>
            <th style="text-align:center;">
                Enquiry No <br> Enquiry Date
            </th>
            <th style="text-align:center;">
                Customer Name <br>Email <br> Mobile
            </th>
            <th style="text-align:center;">
                Project Name<br>
                Unit Type<br>
                Site Address
            </th>
            <th style="text-align:center;">Super Builtup Area</th>
            <th style="text-align:center;">Status</th>
            <th style="text-align:center;">Status Description</th>
            <th style="text-align:center;">Is Awarded</th>
        </tr>
    </thead>
    <tbody>
        @foreach($Enquiries as $index => $result)
        <tr>
            <td >{{ $index + 1 }}</td>
            <td >
                {{ $result["EnquiryReferenceNumber"] }}<br>
                {{ \Carbon\Carbon::parse($result["EnquiryCreatedAt"])->addHours(5)->addMinutes(30)->format("d-M-Y") }}
            </td>
            <td>
                {{ $result["FirstName"] . " " . $result["LastName"] }}<br>
                {{ $result["Email"] }}<br>
                {{ $result["Mobile"] }}
            </td>
            <td>
                <strong>{!! $result["ProjectName"] ? $result["ProjectName"] : "<small>N/A</small>" !!}</strong><br>
                {!! $result["UnitType"] !!}<br>
                {!! $result["Address"] ? $result["Address"] :  "<small>N/A</small>" !!}
            </td>
            <td>
                {!! $result["SuperBuiltUpArea"] ? $result["SuperBuiltUpArea"]: "<small>N/A</small>" !!}
            </td>
            <td>
                {!! $result["EnquiryStatus"] !!}
            </td>
            <td>
                @if(!empty($result["StatusDescription"]))
                @foreach($result["StatusDescription"] as $desc)
                <p>
                    <strong>{{ $loop->index+1 }}.</strong> {{ $desc->desc }}
                </p>
                @endforeach
                @else
                <small>N/A</small>
                @endif
            </td>
            <td>
                {!! $result["IsAwarded"] !!}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>