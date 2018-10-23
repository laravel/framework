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
            <p>{{$UpholsteryType}}</p>
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