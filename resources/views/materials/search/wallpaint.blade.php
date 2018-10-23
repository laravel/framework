<div class="box-body mr-tp-10 table-responsive">
    @if(count($materials) > 0)
    <table class="table table-striped table-bordered dataTable no-footer" id="WallpaintTable">
        <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center text-vertical-align">
            <tr>
                <th class="pd-lt-28">#</th>
                <th>Brand<br>Sub Brand</th>
                <th>Shade Name<br>Code</th>
                <th>Colour</th>
                <th>Images</th>
                <th>Finish</th>
                <th>Type</th>
                <th>Washable<br>Lead Free</th>
                <th class="pd-lt-28">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($materials as $key => $material)
            <tr>
                <td>{{ $key + 1 }}</td>
                <td>
                    <h6>{{ $material["Brand"]}}</h6> 
                    <h5>{{ $material["SubBrand"] }}</h5>
                </td>
                <td>
                    <h5>{{ $material["ShadeName"] }}</h5>
                    <h6>{!! $material["ShadeCode"] !!}</h6>
                </td>    
                <td>
                    @if(isset($material["Colour"]))
                        <ol class="text-left pd-lt-17">
                            {{$material["Colour"]}}
                        </ol>
                    @else
                        <small>N/A</small>
                    @endif
                </td>
                <td>
                    @if(isset($material["FullSheetmages"]))
                    <?php
                    $fanImageJson = json_encode($material["FullSheetmages"]);
                    if (count($material["FullSheetmages"]) > 1) {
                        $Imagetag = '<i class="ion ion-images gallery-icon"></i>';
                    } else {
                        $Imagetag = '<img src="' . URL::CDN($material["FullSheetmages"][0]["Path"]) . '" alt="Sample Laminate" class="note-thumbnail" title="' . $material["FullSheetmages"][0]["UserFileName"] . '">';
                    }
                    ?>
                    <div class="image-link">
                        <a href="{{URL::CDN($material["FullSheetmages"][0]['Path'])}}" class="FullSheetImages" value="{{$fanImageJson}}"  class="cursor-pointer">
                            {!!$Imagetag!!}
                        </a>
                    </div>
                    @else
                    <small>N/A</small>
                    @endif
                </td>
                <td style="text-align: left;">
                    @foreach($material["Finish"] as $Key => $finish)
                        <p class="mr-bt-2">{{$Key+1}}. {{ $finish }}</p>
                    @endforeach
                </td>
                <td>{!! $material["Type"] !!}</td>
                <td>@if($material["Washable"]) 
                    <i class="fa fa-check text-green" title="Yes"></i><br>
                    @else
                    <i class="fa fa-remove text-red" title="No"></i><br>
                    @endif
                    @if($material["LeadFree"]) 
                    <i class="fa fa-check text-green" title="Yes"></i>
                    @else
                    <i class="fa fa-remove text-red" title="No"></i>
                    @endif
                </td>
                <td>
                    <a href="{{ route($material['Slug'], ['id' => $material['editKey']]) }}" class="cursor-pointer" id="Edit" data-toggle="tooltip" title="Edit">
                        <i class="fa fa-fw fa-pencil"></i>
                    </a>
                    <a href="{{ route('materials.view',['slug' => $material['Slug'], 'id' => $material['editKey']]) }}" class="cursor-pointer" id="Edit" data-toggle="tooltip" title="View">
                        <i class="fa fa-fw fa-eye"></i>
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="callout callout-info mr-tp-15 mr-bt-15">No Paints found for the given search parameters.</div>
    @endif
</div>