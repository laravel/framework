<div class="row">
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="Brand">Brand - Sub Brand</label>
            <p>{{$Brand." - ".$SubBrand}}</p>
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Shade Name</label>                            
            <p>{{$Name}}</p>
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Shade Code</label>
            <p>{{$Number}}</p>
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Available In</label>
            @if($AvailableIn !== "N/A")
            <p>{{implode(', ', $AvailableIn)}}</p>
            @else
            <p><small>N/A</small></p>
            @endif
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Base</label>
            @if($Base !== "N/A")
            <p>{{implode(', ', $Base)}}</p>
            @else
            <p><small>N/A</small></p>
            @endif
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
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Colour</label>
            <p>{{$Colour}}</p>
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Type</label>
            <p>{{$Type}}</p>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Coverage Per Liter</label>
            <p>{{$Coverage}} Sq.Ft</p>
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Application Area</label>
            <p>{{$ApplicationArea}}</p>
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Washable</label>
            <p>{{$Washable}}</p>
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Lead Free</label>
            <p>{{$LeadFree}}</p>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Shade Image</label>
            @if($FullSheetmage !== "N/A")
            <?php
            $fullImageJson = json_encode($FullSheetmage);
            $PaintImagetag = '<img src="' . URL::CDN($FullSheetmage[0]["Path"]) . '" alt="Sample Laminate" class="note-thumbnail" title="' . $FullSheetmage[0]["UserFileName"] . '">';
            ?>
            <div class="image-link">
                {!! $PaintImagetag !!}
            </div>
            @else
            <p>{{$FullSheetmage}}</p>
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