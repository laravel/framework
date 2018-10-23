<table class="table table-striped" id="EnquiresListTable">
    @if($EnquiriesDataCount == 0)
    <caption class="SearchCaption">No enquiries found for the user with email - <u>{{$UserData->Email}}</u></caption>
    <tfoot>
        <tr>
            <td colspan="6" class="footer-td">
                <a href="{{route('enquiry', ['id' => $FormUniqueKey])}}" target="_blank" title="" class="btn btn-primary" id="NewEnquiry">Make a New Enquiry</a>
            </td>
        </tr>
    </tfoot>
    @else
    <caption class="SearchCaption">Enquires list for the user with email - <u>{{$UserData->Email}}</u></caption>
    <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
        <tr>
            <th>Customer name</th>
            <th>Mobile</th>
            <th>Email</th>
            <th>Builder name</th>
            <th>Project name</th>
            <th>Unit</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody id="SearchResultsBody">
        @foreach($EnquiriesData as $Enquiry)
        <tr>
            <td>{{$Enquiry["JsonObj"]->step01->Firstname . " " . $Enquiry["JsonObj"]->step01->Lastname}}</td>
            <td>{{$Enquiry["JsonObj"]->step01->Mobile}}</td>
            <td>{{$Enquiry["JsonObj"]->step01->Email}}</td>
            @if(isset($Enquiry["JsonObj"]->step01->BuilderName))
            <td>{{$Enquiry["JsonObj"]->step01->BuilderName}}</td>
            @elseif(isset($Enquiry["JsonObj"]->step02->BuilderName))
            <td>{{$Enquiry["JsonObj"]->step02->BuilderName}}</td>
            @else
            <td>-</td>
            @endif
            @if(isset($Enquiry["JsonObj"]->step01->ProjectName))
            <td>{{$Enquiry["JsonObj"]->step01->ProjectName}}</td>
            @elseif(isset($Enquiry["JsonObj"]->step02->ProjectName))
            <td>{{$Enquiry["JsonObj"]->step02->ProjectName}}</td>
            @else
            <td>-</td>
            @endif
            @if(isset($Enquiry["JsonObj"]->step01->Unit))
            <td>{{$Enquiry["JsonObj"]->step01->Unit}}</td>
            @elseif(isset($Enquiry["JsonObj"]->step02->Unit))
            <td>{{$Enquiry["JsonObj"]->step02->Unit}}</td>
            @else
            <td>-</td>
            @endif
            <td class="EnquiryActions">
                <a href='{{route("viewenquiry", ['id' => $Enquiry["ViewUniqueFormKey"]])}}' target="_blank" class="btn btn-default btn-xs">
                    <span class="glyphicon glyphicon-eye-open"></span>
                </a>
                <a href='{{route("enquiry", ['id' => $Enquiry["EditUniqueFormKey"]])}}' target="_blank" class="btn btn-default btn-xs">
                    <span class="glyphicon glyphicon-pencil"></span>
                </a>
            </td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6" class="footer-td">
                <a href="{{route('enquiry', ['id' => $FormUniqueKey])}}" target="_blank" title="" class="btn btn-primary" id="NewEnquiry">Make a New Enquiry</a>
            </td>
        </tr>
    </tfoot>
    @endif
</table>