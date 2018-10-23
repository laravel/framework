<table class="table table-bordered">
    <thead style="border-top: 1px solid #f4f4f4;" class="bg-light-blue text-center">
        <tr>
            <th width="50" style="text-align:center;">Status</th>
            <th width="50" style="text-align:center;">Count</th>
        </tr>
    </thead>
    <tbody style="border-top: 1px solid #f4f4f4;">
        @foreach($Enquiries as $status)
        @if($status["status"] != "InActive")
        <tr>
            <td>
                {!! !$status["status"] ? '<small>N/A</small>' : $status["status"]["name"] !!}
            </td>
            <td style="text-align:center;">
                {{ $status["count"] }}
            </td>
        </tr>
        @endif
        @endforeach
    </tbody>
</table>
