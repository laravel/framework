<div class="box-body mr-tp-10 table-responsive">
    @if(count($materials) > 0)
    <table class="table table-striped table-bordered no-footer" id="UphosteryTable">
        <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center text-vertical-align">
            <tr>
                <th class="pd-lt-28">#</th>
                <th>Brand<br>Sub Brand</th>
                <th>Design Name<br>Code</th>
                <th>Base Colour</th>
                <th>Design Colour</th>
                <th>Images</th>
                <th>Pattern - Code</th>
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
                    <h6>{!! $material["DesignCode"] !!}</h6>
                </td>
                <td>
                    @if(isset($material["BaseColour"]))
                    <ol class="text-left pd-lt-17">
                        @foreach($material["BaseColour"] as $Key => $colour)
                        <li>{{ $colour }}</li>
                        @endforeach
                    </ol>
                    @else
                        <small>N/A</small>
                    @endif
                </td>
                <td>
                    @if(isset($material["DesignColour"]))
                    <ol class="text-left pd-lt-17">
                        @foreach($material["DesignColour"] as $Key => $colour)
                        <li>{{ $colour }}</li>
                        @endforeach
                    </ol>
                    @else
                        <small>N/A</small>
                    @endif
                </td>
                <td>
                    @if(isset($material["UpholsteryImage"]))
                    <?php
                    $UpholsteryImageImageJson = json_encode($material["UpholsteryImage"]);
                    if (count($material["UpholsteryImage"]) > 1) {
                        $Imagetag = '<i class="ion ion-images gallery-icon"></i>';
                    } else {
                        $Imagetag = '<img src="' . URL::CDN($material["UpholsteryImage"][0]["Path"]) . '" alt="Sample Laminate" class="note-thumbnail" title="' . $material["UpholsteryImage"][0]["UserFileName"] . '">';
                    }
                    ?>
                    <div class="image-link">
                        <a href="{{URL::CDN($material["UpholsteryImage"][0]['Path'])}}" class="FullSheetImages" value="{{$UpholsteryImageImageJson}}"  class="cursor-pointer">
                             {!!$Imagetag!!}
                        </a>
                    </div>
                    @else
                        <small>N/A</small>
                    @endif
                </td>
                <td>{!! $material["Pattern"] !!} - {!! $material["PatternCode"] !!}</td>
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
    <div class="callout callout-info mr-tp-15 mr-bt-15">No Uphostery found for the given search parameters.</div>
    @endif
</div>