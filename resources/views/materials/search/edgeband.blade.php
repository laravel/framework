<div class="box-body mr-tp-10 table-responsive">
    @if(count($materials) > 0)
    <table class="table table-striped table-bordered dataTable no-footer" id="EdgebandTable">
        <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center text-vertical-align">
            <tr>
                <th class="pd-lt-28">#</th>
                <th>Brand<br>Sub Brand</th>
                <th>Design Name<br>Code</th>
                <th>Colour - Pattern<br>(Finish)</th>
                <th>Stock Availability</th>
                <th>Images</th>
                <th class="no-text-transform">Available Sizes<br>(W x T)</th>
                <th class="pd-lt-28">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($materials as $key => $material)
            <?php
            $fullImageJson = json_encode($material["FullSheetImage"]);
            if (count($material["FullSheetImage"]) > 1) {
                $Imagetag = '<i class="ion ion-images gallery-icon"></i>';
            } else {
                $Imagetag = '<img src="' . URL::CDN($material["FullSheetImage"][0]["Path"]) . '" alt="Sample Laminate" class="note-thumbnail" title="' . $material["FullSheetImage"][0]["UserFileName"] . '">';
            }
            ?>
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
                <td>{!! $material["Colour"] !!} - {!! $material["Pattern"] !!} <br>({!! $material["Finish"] !!})</td>
                <td>
                    @if($material["Rolls"] != "N/A" || $material["Meters"] != "N/A")
                       <span class="header-tooltip" data-toggle="tooltip" data-html="true" title="<p align='left'><b>No of Rolls available:</b> {{$material["Rolls"]}} <br><br> <b>No of Meters available:</b> {{$material["Meters"]}} <br><br><b>Status as on Date:</b> {{$material["Status"]}}<br><br><b>Notes:</b> {{$material["StockNotes"]}}</p>">{{ ($material["Rolls"]!="N/A")? $material["Rolls"] : $material["Meters"] }}</span>
                    @else
                    {!!$material["Rolls"]!!}
                    @endif
                </td>
                <td>
                    <div class="image-link">
                        <a href="{{URL::CDN($material["FullSheetImage"][0]['Path'])}}" class="FullSheetImages" value="{{$fullImageJson}}"  class="cursor-pointer">
                            {!!$Imagetag!!}
                        </a>
                    </div>
                </td>
                <td>
                    <?php
                    $AvailableSizes = array();
                    for ($index = 0; $index < count($material["Width"]); $index++) {
                        $AvailableSizes[$index] = $material["Width"][$index] . "x" . $material["Thickness"][$index];
                    }
                    ?>
                    <p class="mr-bt-2">{{implode(", ",$AvailableSizes)}}</p>
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
    <div class="callout callout-info mr-tp-15 mr-bt-15">No Edgebands found for the given search parameters.</div>
    @endif
</div>