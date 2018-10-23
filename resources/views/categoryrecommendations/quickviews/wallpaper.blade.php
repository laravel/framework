<div class="row">
    <div class="col-md-12">
        <div class="col-md-3 col-xs-3">
            <div class="form-group">
                <label for="Brand">Brand - Sub Brand</label>
                <p>{{$Brand." - ".$SubBrand}}</p>
            </div>
        </div>
        <div class="col-md-3 col-xs-3">
            <div class="form-group">
                <label for="">Design Name</label>                            
                <p>{{$Name}}</p>
            </div>
        </div>
        <div class="col-md-3 col-xs-3">
            <div class="form-group">
                <label for="">Design Code</label>
                <p>{{$Number}}</p>
            </div>
        </div>
        <div class="col-md-3 col-xs-3">
            <div class="form-group">
                <label for="">Finish</label>
                @if($Finishes !== "N/A")
                <p>{{implode(', ', $Finishes)}}</p>
                @else
                <p><small>N/A</small></p>
                @endif
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="col-md-3 col-xs-3">
            <div class="form-group">
                <label for="">Colour</label>
                @if(!$Colour)
                <p><small>N/A</small></p>
                @else
                <ul class="list-style-circle" style="padding-left: 15px;">
                    @foreach($Colour as $area)
                    <li>{{$area}}</li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>
        <div class="col-md-3 col-xs-3">
            <div class="form-group">
                <label for="">Pattern</label>
                <p>{{$Type}}</p>
            </div>
        </div>
        <div class="col-md-3 col-xs-3">
            <div class="form-group">
                <label for="">Coverage Per Roll</label>
                <p>{{$Coverage}} Sq.Ft</p>
            </div>
        </div>
        <div class="col-md-3 col-xs-3">
            <div class="form-group">
                <label for="">Roll Size</label>
                @if($RollSize === "N/A")
                <p><small>N/A</small></p>
                @else
                <p>{{$RollSize}} Sq.Ft</p>
                @endif
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="col-md-3 col-xs-3">
            <div class="form-group">
                <label for="">Usable Roll Size</label>
                @if($UsableRollSize === "N/A")
                <p><small>N/A</small></p>
                @else
                <p>{{$UsableRollSize}} Sq.Ft</p>
                @endif
            </div>
        </div>
        <div class="col-md-3 col-xs-3">
            <div class="form-group">
                <label for="">Application Area</label>
                <ul class="list-style-circle" style="padding-left: 15px;">
                    @foreach($ApplicationArea as $area)
                    <li>{{$area}}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <div class="col-md-3 col-xs-3">
            <div class="form-group">
                <label for="">Joint Type</label>
                @if($JointType === "N/A")
                <p><small>N/A</small></p>
                @else
                <p>{{$JointType}}</p>
                @endif
            </div>
        </div>
        <div class="col-md-3 col-xs-3">
            <div class="form-group">
                <span class="badge bg-red durability-badge"><i class="fa fa-link"></i></span>&nbsp;&nbsp;<label for="">Durability</label>
                @if($Durability !== "N/A")
                <ul class="pd-lt-0 list-style-none durability-list">
                    <li class="{{ (($Durability == "Low" || $Durability == "Medium" || $Durability == "High" || $Durability == "Very High" || $Durability == "Best in Class") ? "dark-color": 'light-color') }}">
                        <span class="badge bg-durability"><i class="fa fa-check"></i></span>
                    </li>
                    <li class="{{ (($Durability == "Medium" || $Durability == "High" || $Durability == "Very High" || $Durability == "Best in Class")  ? 'dark-color': 'light-color') }}">
                        <span class="badge bg-durability"><i class="fa fa-check"></i></span>
                    </li>
                    <li class="{{ (($Durability == "High" || $Durability == "Very High" || $Durability == "Best in Class") ? 'dark-color': 'light-color') }}">
                        <span class="badge bg-durability"><i class="fa fa-check"></i></span>
                    </li>
                    <li class="{{ (($Durability == "Very High" || $Durability == "Best in Class") ? 'dark-color': 'light-color') }}">
                        <span class="badge bg-durability"><i class="fa fa-check"></i></span>
                    </li>
                    <li class="{{ (($Durability == "Best in Class") ? 'dark-color': 'light-color') }}">
                        <span class="badge bg-durability"><i class="fa fa-check"></i></span>
                    </li>&nbsp;&nbsp;
                    <li>{{$Durability}}</li>
                </ul>
                @else
                <ul class="pd-lt-0 list-style-none durability-list">
                    <li class="light-color">
                        <span class="badge bg-durability"><i class="fa fa-check"></i></span>
                    </li>
                    <li class="light-color">
                        <span class="badge bg-durability"><i class="fa fa-check"></i></span>
                    </li>
                    <li class="light-color">
                        <span class="badge bg-durability"><i class="fa fa-check"></i></span>
                    </li>
                    <li class="light-color">
                        <span class="badge bg-durability"><i class="fa fa-check"></i></span>
                    </li>
                    <li class="light-color">
                        <span class="badge bg-durability"><i class="fa fa-check"></i></span>
                    </li>&nbsp;&nbsp;
                    <li>Not Specified</li>
                </ul>
                @endif
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="col-md-3 col-xs-3">
            <div class="form-group">
                <label for="">Washable</label>
                <p>{{$Washable}}</p>
            </div>
        </div>
        <div class="col-md-3 col-xs-3">
            <div class="form-group">
                <label for="">Dry Strippable (Depending on wall surface and processing)</label>
                <p>{{$LeadFree}}</p>
            </div>
        </div>
        <div class="col-md-3 col-xs-3">
            <div class="form-group">
                <label for="">Wallpaper Image</label>
                @if($FullSheetmage !== "N/A")
                <?php
                $fullImageJson = json_encode($FullSheetmage);
                ?>
                <div class="image-link">
                    <img src="{{URL::CDN($FullSheetmage[0]["Path"])}}" class="note-thumbnail" alt="Wallpaper Image">
                </div>
                @else
                <p>{{$FullSheetmage}}</p>
                @endif
            </div>
        </div>
    </div>
</div>