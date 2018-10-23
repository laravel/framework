<div class="row">
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="Brand">Brand - Sub Brand</label>
            <p>{{$Brand." - ".$SubBrand}}</p>
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Name</label>                            
            <p>{{$Name}}</p>
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Code</label>
            <p>{{$Number}}</p>
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Pattern</label>
            <p>{{$Pattern}}</p>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Finish</label>
            <p>{{$Finish}}</p>
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
            <label for="">Lacquer Type</label>                            
            <p>{{$LacquerType}}</p>
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Material</label>
            <p>{{$Material}}</p>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-6 col-xs-6">
        <div class="form-group">
            <label for="">Matching Surface Material</label>
            <ul class="pd-lt-13">
                @if($MatchSurfaceMaterial !== "N/A")
                @foreach($MatchSurfaceMaterial as $material)    
                <li class="">{{$material}}</li>         
                @endforeach
                @else
                N/A
                @endif
            </ul>
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Image</label>
            @if($FullSheetmage !== "N/A")
            <?php
            $fullImageJson = json_encode($FullSheetmage);
            $Imagetag = '<img src="' . URL::CDN($FullSheetmage[0]["Path"]) . '" alt="Sample Edgeband" class="note-thumbnail" title="' . $FullSheetmage[0]["UserFileName"] . '">';
            ?>
            <div class="image-link">
                {!!$Imagetag!!}
            </div>
            @else
            <p>{{$FullSheetmage}}</p>
            @endif
        </div>
    </div>
</div>