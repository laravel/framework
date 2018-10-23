<div class="box-body mr-tp-10 table-responsive">
    @if(count($materials) > 0)
    <table class="table table-striped table-bordered dataTable no-footer" id="SurfaceMaterialsListTable">
        <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
            <tr>
                <th class="pd-lt-12">#</th>
                <th>Brand<br>Sub Brand</th>
                <th>Design Name</th>
                <th>Number</th>
                <th>Type</th>
                <th>Finish</th> 
                <th>Glossy ?</th> 
                <th>Edge Band ?</th>
                <th>Stock Availability</th>
                <th>Sample Image</th>
                <th>Full Sheet Image</th>
                <th>Real Image</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($materials as $key => $enquiry)
            <?php
            $fullImageJson = json_encode($enquiry["FullSheetImage"]);
            $sampleImageJson = json_encode($enquiry["SampleImage"]);
            $usageImageJson = json_encode($enquiry["UsageImage"]);
            $FullSheetImagetag = (count($enquiry["FullSheetImage"]) > 1) ? '<i class="ion ion-images gallery-icon"></i>' : '<img src="' . URL::CDN($enquiry["FullSheetImage"][0]["Path"]) . '" alt="Sample Laminate" class="note-thumbnail" title="' . $enquiry["FullSheetImage"][0]["UserFileName"] . '">';
            $SampleImagetag = (count($enquiry["SampleImage"]) > 1) ? '<i class="ion ion-images gallery-icon"></i>' : '<img src="' . URL::CDN($enquiry["SampleImage"][0]["Path"]) . '" alt="Sample Laminate" class="note-thumbnail" title="' . $enquiry["SampleImage"][0]["UserFileName"] . '">';
            $UsageImagetag = (count($enquiry["UsageImage"]) > 1) ? '<i class="ion ion-images gallery-icon"></i>' : '<img src="' . URL::CDN($enquiry["UsageImage"][0]["Path"]) . '" alt="Sample Laminate" class="note-thumbnail" title="' . $enquiry["UsageImage"][0]["UserFileName"] . '">';
            ?>
            <tr>
                <td class="text-center text-vertical-align">{{ $key + 1 }}</td>
                <td>
                    <h6>{{ $enquiry["Brand"]}}</h6> 
                    <h5>{{ $enquiry["SubBrand"] }}</h5>
                </td>
                <td>{{ $enquiry["DesignName"] }}</td>
                <td>{{ $enquiry["DesignNo"] }}</td>
                <td>{{ $enquiry["Type"] }}</td>
                <td>{{ $enquiry["SurfaceFinish"] }}</td>
                <td>{{ $enquiry["Glossy"] }}</td>
                <td>{{ $enquiry["Edgeband"] }}</td>
                <td>
                    @if($enquiry["Sheets"] != "N/A")
                       <span class="header-tooltip" data-toggle="tooltip" data-html="true" title="<p align='left'><b>No of Sheets available:</b> {{$enquiry["Sheets"]}} <br><br><b>Status as on Date:</b> {{$enquiry["Status"]}}<br><br><b>Notes:</b> {{$enquiry["StockNotes"]}}</p>">{{ $enquiry["Sheets"] }}</span>
                    @else
                    {{$enquiry["Sheets"]}}
                    @endif
                </td>
                <td>
                    @if($enquiry["SampleImage"])
                    <div class="image-link">
                        <a href="{{URL::CDN($enquiry["SampleImage"][0]['Path'])}}" value="{{$sampleImageJson}}"  class="cursor-pointer SampleImages">
                            {!! $SampleImagetag !!}
                        </a>
                    </div>
                    @else
                    <p>N/A</p>
                    @endif
                </td>
                <td>
                    <div class="image-link">
                        <a href="{{URL::CDN($enquiry["FullSheetImage"][0]['Path'])}}" class="FullSheetImages" value="{{$fullImageJson}}"  class="cursor-pointer">
                            {!! $FullSheetImagetag !!}
                        </a>
                    </div>
                </td>
                <td>
                    @if($enquiry["UsageImage"])
                    <div class="image-link">
                        <a href="{{URL::CDN($enquiry["UsageImage"][0]['Path'])}}" class="cursor-pointer UsageImages" value="{{$usageImageJson}}">
                            {!! $UsageImagetag !!}
                        </a>
                    </div>
                    @else
                    <p>N/A</p>
                    @endif  
                </td>
                <td>
                    <a href="{{ route($enquiry['Slug'], ['id' => $enquiry['editKey']]) }}" class="cursor-pointer" id="Edit" data-toggle="tooltip" title="Edit">
                        <i class="fa fa-fw fa-pencil"></i>
                    </a>
                    <a href="{{ route('materials.view',['slug' => $enquiry['Slug'], 'id' => $enquiry['editKey']]) }}" class="cursor-pointer" id="Edit" data-toggle="tooltip" title="View">
                        <i class="fa fa-fw fa-eye"></i>
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="callout callout-info mr-tp-15 mr-bt-15">No Materials found for the given search parameters.</div>
    @endif
</div>