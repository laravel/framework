<div class="box-body mr-tp-10 table-responsive">
    @if(count($materials) > 0)
    <table class="table table-striped table-bordered dataTable no-footer" id="FanTable">
        <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center text-vertical-align">
            <tr>
                <th class="pd-lt-28">#</th>
                <th>Brand<br>Sub Brand</th>
                <th>Design Name<br>Number</th>
                <th>Images</th>
                <th>Finish</th>
                <th class="no-text-transform">Sweep <small>(mm) (feet)</small></th>
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
                    <h5>{{ $material["DesignName"] }}</h5>
                    <h6>{!! $material["DesignNo"] !!}</h6>
                </td>                      
                <td>
                    @if(isset($material["FanImages"]))
                    <?php
                    $fanImageJson = json_encode($material["FanImages"]);
                    if (count($material["FanImages"]) > 1) {
                        $Imagetag = '<i class="ion ion-images gallery-icon"></i>';
                    } else {
                        $Imagetag = '<img src="' . URL::CDN($material["FanImages"][0]["Path"]) . '" alt="Sample Laminate" class="note-thumbnail" title="' . $material["FanImages"][0]["UserFileName"] . '">';
                    }
                    ?>
                    <div class="image-link">
                        <a href="{{URL::CDN($material["FanImages"][0]['Path'])}}" class="FullSheetImages" value="{{$fanImageJson}}"  class="cursor-pointer">
                             {!!$Imagetag!!}
                        </a>
                    </div>
                    @else
                    <small>N/A</small>
                    @endif
                </td>
                <td>{!! $material["Finish"] !!}</td>
                <td>
                    @if(isset($material["SweepInMm"]))
                        @foreach($material["SweepInMm"] as $Key => $sweep)
                            <p class="mr-bt-2">{{ $material["SweepInMm"][$Key] .' ('. $material["SweepInFeet"][$Key].')'}}</p>
                        @endforeach
                    @else
                        <small>N/A</small>
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
    <div class="callout callout-info mr-tp-15 mr-bt-15">No Fans found for the given search parameters.</div>
    @endif
</div>