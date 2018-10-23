<div class="row">
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
            <label for="">Base Colour</label>
            @if($BaseColour !== "N/A")
            <p>{{implode(', ', $BaseColour)}}</p>
            @else
            <p><small>N/A</small></p>
            @endif
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Design Colour</label>
            @if($DesignColour !== "N/A")
            <p>{{implode(', ', $DesignColour)}}</p>
            @else
            <p><small>N/A</small></p>
            @endif
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Type</label>
            <p>{{$CurtainType}}</p>
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Base Fabric</label>
            <p>{{$BaseFabric}}</p>
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Design Fabric</label>
            <p>{{$DesignFabric}}</p>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Pattern</label>
            <p>{{$Pattern}}</p>
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Pattern Code</label>
            <p>{{$PatternCode}}</p>
        </div>
    </div>
</div>
<h4 class="text-primary">Usable Fabric</h4>
<div class="row">
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Width (Panna) Incms</label>
            <p>{{$WidthInCm}}</p>
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Width (Panna) InInch</label>
            <p>{{$WidthInInch}}</p>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">GSM</label>
            <p>{{$GSM}}</p>
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Curtain Image</label>
            @if($CurtainImage !== "N/A")
            <?php
            $cutainImageJson = json_encode($CurtainImage);
            $CurtainImagetag = '<img src="' . URL::CDN($CurtainImage[0]["Path"]) . '" alt="Sample Curtain" class="note-thumbnail" title="' . $CurtainImage[0]["UserFileName"] . '">';
            ?>
            <div class="image-link">
                {!! $CurtainImagetag !!}
            </div>
            @else
            <p>{{$$CurtainImage}}</p>
            @endif
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">End Use</label>
            @if($EndUse !== "N/A")
            <p>{{implode(', ', $EndUse)}}</p>
            @else
            <p><small>N/A</small></p>
            @endif
        </div>
    </div>
</div>