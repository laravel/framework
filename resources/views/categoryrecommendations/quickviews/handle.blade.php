
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
            <label for="">Material</label>
            <p>{{$Material}}</p>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Type</label>
            <p>{{$Type}}</p>
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Also Avaiable Types</label>
            @if($AlsoAvaiableTypes !== "N/A")
            <p>{{implode(', ', $AlsoAvaiableTypes)}}</p>
            @else
            <p>N/A</p>
            @endif
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Finish</label>
            <ul class="list-style-circle" style="padding-left: 15px;">
                @foreach($Finish as $area)
                <li>{{$area}}</li>
                @endforeach
            </ul>
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Colour</label>
            @if($Colour !== "N/A")
            <p>{{implode(', ',$Colour)}}</p>
            @else
            <p><small>N/A</small></p>
            @endif
        </div>
    </div>

</div>
<div class="row">
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Handle Image</label>
            @if($HandleImage !== "N/A")
            <?php
            $handleImageJson = json_encode($HandleImage);
            $Imagetag = '<img src="' . URL::CDN($HandleImage[0]["Path"]) . '" alt="Sample Handle" class="note-thumbnail" title="' . $HandleImage[0]["UserFileName"] . '">';
            ?>
            <div class="image-link">
                {!!$Imagetag!!}
            </div>
            @else
            <p>{{$HandleImage}}</p>
            @endif
        </div>
    </div>
</div>
<h4 class="text-primary no-text-transform">Available Sizes</h4>
<div class="row">
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">Knob</label>
            @if($Knob == "Yes")<p><i class="fa fa-fw fa-check"></i></p> @else <p><i class="fa fa-fw fa-close"></i></p> @endif
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">72mm (3")</label>
            @if($Size72mm == "Yes")<p><i class="fa fa-fw fa-check"></i></p> @else <p><i class="fa fa-fw fa-close"></i></p> @endif
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">96mm (4")</label>
            @if($Size96mm == "Yes")<p><i class="fa fa-fw fa-check"></i></p> @else <p><i class="fa fa-fw fa-close"></i></p> @endif
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">128mm (5")</label>
            @if($Size128mm == "Yes")<p><i class="fa fa-fw fa-check"></i></p> @else <p><i class="fa fa-fw fa-close"></i></p> @endif
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">160mm (6")</label>
            @if($Size160mm == "Yes")<p><i class="fa fa-fw fa-check"></i></p> @else <p><i class="fa fa-fw fa-close"></i></p> @endif
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">192mm (7")</label>
            @if($Size192mm == "Yes")<p><i class="fa fa-fw fa-check"></i></p> @else <p><i class="fa fa-fw fa-close"></i></p> @endif
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">224mm (8")</label>
            @if($Size224mm == "Yes")<p><i class="fa fa-fw fa-check"></i></p> @else <p><i class="fa fa-fw fa-close"></i></p> @endif
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">256mm (10")</label>
            @if($Size256mm == "Yes")<p><i class="fa fa-fw fa-check"></i></p> @else <p><i class="fa fa-fw fa-close"></i></p> @endif
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">288mm (11")</label>
            @if($Size288mm == "Yes")<p><i class="fa fa-fw fa-check"></i></p> @else <p><i class="fa fa-fw fa-close"></i></p> @endif
        </div>
    </div>
    <div class="col-md-3 col-xs-3">
        <div class="form-group">
            <label for="">320mm (12")</label>
            @if($Size320mm == "Yes")<p><i class="fa fa-fw fa-check"></i></p> @else <p><i class="fa fa-fw fa-close"></i></p> @endif
        </div>
    </div>
</div>