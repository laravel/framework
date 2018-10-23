<div class="box-body">
    @if($EnquiriesDataCount == 0)
    <div class="callout callout-info">
        <h4>Information!</h4>
        <p>No enquiries found <a href="{{route('enquiry', ['id' => $FormUniqueKey])}}" title="Make a new enquiry" id="NewEnquiry">Make a New Enquiry</a>.</p>
    </div>
    @else
    <div class=" table-responsive">
        <table id="UserEnquiresList" class="table table-bordered table-hover">
<!-- <caption class="SearchCaption">Enquires list for the user with email - <u>{{$UserData->Email}}</u></caption> -->
            <thead class="bg-light-blue text-center">
                <tr>
                    <th>Name</th>
                    <th>Mobile</th>
                    <th>Email</th>
                    <th>Builder name</th>
                    <th>Project name</th>
                    <th>Unit</th>
                    <th>Site Address</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="EnquiryListBody">
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
                    @elseif(isset($Enquiry["JsonObj"]->step02->UnitType))
                    <td>{{$Enquiry["JsonObj"]->step02->UnitType}}</td>
                    @else
                    <td>-</td>
                    @endif
                    @if(isset($Enquiry["SiteAddress"]))
                    <td>{{$Enquiry["SiteAddress"]}}</td>
                    @else
                    <td>-</td>
                    @endif
                    <td class="text-center">
                        <span class="dropdown">
                            <a class="dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-caret-square-o-down" aria-hidden="true"></i>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                                <li>
                                    <a href='{{route("viewenquiry", ['id' => $Enquiry["ViewUniqueFormKey"]])}}'>
                                       <i class="fa fa-eye" aria-hidden="true"></i> View Enquiry
                                    </a>
                                </li>
                                @if(!$Enquiry["ShortCode"])
                                <li>
                                    <a href='{{route("enquiry", ['id' => $Enquiry["EditUniqueFormKey"]])}}'>
                                       <i class="fa fa-pencil" aria-hidden="true"></i> Edit Enquiry
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>