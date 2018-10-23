@extends('layouts/master_template')
@section('dynamicStyles')
<link rel="stylesheet" href="{{ asset('/css/materials/view.css') }}" />
<link rel="stylesheet" href="{{ URL::assetUrl("/css/magnific-popup.css")}}">
@endsection
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h4 class="col-md-12 text-primary mr-tp-4">Material Information</h4>
                <div class="box-header with-border">
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
                                <label for="">Design Number</label>
                                <p>{{$DesignNumber}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Category</label>                            
                                <p>{{$Category}}</p>
                            </div>
                        </div>  
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Range</label>
                                <p>{{$Range}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Surface Finish</label>
                                <p>{{$SurfaceFinish}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Full Sheet Image</label>
                                @if($FullSheetmage !== "N/A")
                                <?php
                                $fullImageJson = json_encode($FullSheetmage);
                                $FullSheetImagetag = (count($FullSheetmage) > 1) ? '<i class="ion ion-images gallery-icon"></i>' : '<img src="' . URL::CDN($FullSheetmage[0]["Path"]) . '" alt="Sample Laminate" class="note-thumbnail" title="' . $FullSheetmage[0]["UserFileName"] . '">';
                                ?>
                                <div class="image-link">
                                    <a href="{{URL::CDN($FullSheetmage[0]["Path"])}}" class="FullSheetImages" value="{{$fullImageJson}}"  class="cursor-pointer">
                                        {!! $FullSheetImagetag !!}
                                    </a>
                                </div>
                                @else
                                <p>{{$FullSheetmage}}</p>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Sample Image (Swatch)</label>
                                @if($SampleImage !== "N/A")
                                <?php
                                $sampleImageJson = json_encode($SampleImage);
                                $SampleImagetag = (count($SampleImage) > 1) ? '<i class="ion ion-images gallery-icon"></i>' : '<img src="' . URL::CDN($SampleImage[0]["Path"]) . '" alt="Sample Laminate" class="note-thumbnail" title="' . $SampleImage[0]["UserFileName"] . '">';
                                ?>
                                <div class="image-link">
                                    <a href="{{URL::CDN($SampleImage[0]["Path"])}}" class="FullSheetImages" value="{{$sampleImageJson}}"  class="cursor-pointer">
                                         {!! $SampleImagetag !!}
                                    </a>
                                </div>
                                @else
                                <p>{{$SampleImage}}</p>
                                @endif
                            </div>
                        </div>  
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Usage Image (Real)</label>
                                @if($UsageImage !== "N/A")
                                <?php
                                $usageImageJson = json_encode($UsageImage);
                                $UsageImagetag = (count($UsageImage) > 1) ? '<i class="ion ion-images gallery-icon"></i>' : '<img src="' . URL::CDN($UsageImage[0]["Path"]) . '" alt="Sample Laminate" class="note-thumbnail" title="' . $UsageImage[0]["UserFileName"] . '">';
                                ?>
                                <div class="image-link">
                                    <a href="{{URL::CDN($UsageImage[0]["Path"])}}" class="FullSheetImages" value="{{$usageImageJson}}"  class="cursor-pointer">
                                        {!! $UsageImagetag !!}
                                    </a>
                                </div>
                                @else
                                <p>{{$UsageImage}}</p>
                                @endif
                            </div>
                        </div> 
                    </div>
                </div>
                <h4 class="col-md-12 text-primary">Dimensions</h4>
                <div class="box-header with-border">
                    <div class="row">
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Width (mm)</label>
                                <p>{{$Width}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Height/Length (mm)</label>
                                <p>{{$Height}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Depth (mm)</label>                            
                                <p>{{$Depth}}</p>
                            </div>
                        </div>    
                    </div>
                    <div class="row">
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Width (inch)</label>
                                <p>{{$WidthInInch}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Height/Length (inch)</label>
                                <p>{{$HeightInInch}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Depth (inch)</label>                            
                                <p>{{$DepthInInch}}</p>
                            </div>
                        </div>    
                    </div>
                </div>
                <h4 class="col-md-12 text-primary">Properties</h4>
                <div class="box-header with-border">
                <div class="row">
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Textured Surface</label>                            
                            <p>{{$SurfaceTexture}}</p>
                        </div>
                    </div> 
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Glossy</label>                            
                            <p>{{$Glossy}}</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Scratch Resistant</label>
                            <p>{{$ScratchResis}}</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Color Fast</label>                            
                            <p>{{$ColorFast}}</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Heat Resistant</label>                            
                            <p>{{$HeatRes}}</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Stain Resistant</label>
                            <p>{{$StainRes}}</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Superior Gloss Level</label>
                            <p>{{$GlossLevel}}</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Thickness Tolerance</label>                            
                            <p>{{$ThickToler}}</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Surface Water Resistance</label>                            
                            <p>{{$SurfaceWaterRes}}</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Boiling Water Resistance</label>
                            <p>{{$BoilingWater_Resis}}</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">High Temperature Resistance</label>
                            <p>{{$HighTemperatureResis}}</p>
                        </div>
                    </div> 
                </div>
            </div>
            <div class="box-header with-border">
                <div class="row">
                    <div class="col-md-6 col-xs-6">
                        <div class="form-group">
                            <label for="">Website URL</label>
                            <p>{{$SiteUrl}}</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-xs-6">
                        <div class="form-group">
                            <label for="">Tags</label>
                            <p>{{$Tags}}</p>
                        </div>
                    </div> 
                </div>
                <div class="row">
                    <div class="col-md-6 col-xs-6">
                        <div class="form-group">
                            <label for="">Suggested Pairing</label>
                            <ul class="pd-lt-13">
                                @if($SuggestedPairing !== "N/A")
                                @foreach($SuggestedPairing as $pairing)    
                                <li class="">{{$pairing}}</li>         
                                @endforeach
                                @else
                                N/A
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
                <h4 class="col-md-12 text-primary">EdgeBand Availability: <span class="EdgebandStatus">{{($Edgeband === '1') ? "Yes" : "No"}}</span></h4>
                @if($Edgeband === "1")
                <div class="row">
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Exact Match</label>
                            <ul class="pd-lt-13">
                                @if($ExactMatch !== "N/A")
                                @foreach($ExactMatch as $match)    
                                <li class="">{{$match}}</li>         
                                @endforeach
                                @else
                                N/A
                                @endif
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Nearest Match</label>
                            <ul class="pd-lt-13">
                                @if($NearestMatch !== "N/A")
                                @foreach($NearestMatch as $match)    
                                <li class="">{{$match}}</li>         
                                @endforeach
                                @else
                                N/A
                                @endif
                            </ul>
                        </div>
                    </div> 
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Contrast Match</label>
                            <ul class="pd-lt-13">
                                @if($ContrastMatch !== "N/A")
                                @foreach($ContrastMatch as $match)    
                                <li class="">{{$match}}</li>         
                                @endforeach
                                @else
                                N/A
                                @endif
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group view-more-edgeband">
                            <label for="">Ideas For EdgeBand</label>
                            <p>{{$EdgebandIdea}}</p>
                            <a href="" title="Click here to view more Edgeband Ideas" class="more-edgeband {{$MoreEdgebandIdea}}">Show more</a>
                        </div>
                        <div class="form-group view-less-edgeband hidden">
                            <label for="">Ideas For Edgeband</label>
                            <ol class="pd-lt-13">
                                @if($EdgebandIdeas !== "N/A")
                                @foreach($EdgebandIdeas as $EdgebandIdea)    
                                <li class="">{{$EdgebandIdea}}</li>         
                                @endforeach
                                @endif
                            </ol>
                            <a href="" title="switch to minimized view" class="less-edgeband">Show less</a>
                        </div>
                    </div>
                </div>
                @endif
                <div class="row">
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Suggested Fan</label>
                            <ul class="pd-lt-13">
                                @if($SuggestedFan !== "N/A")
                                @foreach($SuggestedFan as $fan)    
                                <li class="">{{$fan}}</li>         
                                @endforeach
                                @else
                                N/A
                                @endif
                            </ul>
                        </div>
                    </div> 
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group view-more">
                            <label for="">Suggested Fan Usage</label>
                            <p>{{$Usage}}</p>
                            <a href="" title="Click here to view more Suggestions" class="more-usage {{$ShowMore}}">Show more</a>
                        </div>
                        <div class="form-group view-less hidden">
                            <label for="">Suggested Fan Usage</label>
                            <ol class="pd-lt-13">
                                @if($SuggestedUsage !== "N/A")
                                @foreach($SuggestedUsage as $usage)    
                                <li class="">{{$usage}}</li>         
                                @endforeach
                                @endif
                            </ol>
                            <a href="" title="switch to minimized view" class="less-usage">Show less</a>
                        </div>
                    </div> 
                </div>
                <div class="row">
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Suggested Handle Finish</label>
                            <p>{{$SuggestedHandleFinish}}</p>
                        </div>
                    </div> 
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Suggested Handle</label>
                            <ul class="pd-lt-13">
                                @if($SuggestedHandle !== "N/A")
                                @foreach($SuggestedHandle as $handle)    
                                <li class="">{{$handle}}</li>         
                                @endforeach
                                @else
                                N/A
                                @endif
                            </ul>
                        </div>
                    </div> 
                </div>
                <div class="row">
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Suggested Wall Colour</label>
                            <ul class="pd-lt-13">
                                @if($SuggestedWallColour !== "N/A")
                                @foreach($SuggestedWallColour as $wallcolour)    
                                <li class="">{{$wallcolour}}</li>         
                                @endforeach
                                @else
                                N/A
                                @endif
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group view-more-wall-color">
                            <label for="">Ideas For Wall Colour Selection</label>
                            <p>{{$WallColourIdea}}</p>
                            <a href="" title="Click here to view more Wall Colour Ideas" class="more-wall-color {{$MoreWallColourIdea}}">Show more</a>
                        </div>
                        <div class="form-group view-less-wall-color hidden">
                            <label for="">Ideas For Wall Colour Selection</label>
                            <ol class="pd-lt-13">
                                @if($WallColourIdeas !== "N/A")
                                @foreach($WallColourIdeas as $WallColourIdea)    
                                <li class="">{{$WallColourIdea}}</li>         
                                @endforeach
                                @endif
                            </ol>
                            <a href="" title="switch to minimized view" class="less-wall-color">Show less</a>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Suggested Wall Paper</label>
                            <ul class="pd-lt-13">
                                @if($SuggestedWallPaper !== "N/A")
                                @foreach($SuggestedWallPaper as $wallpaper)    
                                <li class="">{{$wallpaper}}</li>         
                                @endforeach
                                @else
                                N/A
                                @endif
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group view-more-wall-paper">
                            <label for="">Ideas For Wall Paper Selection</label>
                            <p>{{$WallPaperIdea}}</p>
                            <a href="" title="Click here to view more Wall Paper Ideas" class="more-wall-paper {{$MoreWallPaperIdea}}">Show more</a>
                        </div>
                        <div class="form-group view-less-wall-paper hidden">
                            <label for="">Ideas For Wall Paper Selection</label>
                            <ol class="pd-lt-13">
                                @if($WallPaperIdeas !== "N/A")
                                @foreach($WallPaperIdeas as $WallPaperIdea)    
                                <li class="">{{$WallPaperIdea}}</li>         
                                @endforeach
                                @endif
                            </ol>
                            <a href="" title="switch to minimized view" class="less-wall-paper">Show less</a>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Suggested Wall Curtain</label>
                            <ul class="pd-lt-13">
                                @if($SuggestedCurtain !== "N/A")
                                @foreach($SuggestedCurtain as $curtain)    
                                <li class="">{{$curtain}}</li>         
                                @endforeach
                                @else
                                N/A
                                @endif
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group view-more-curtain">
                            <label for="">Ideas For Curtain Selection</label>
                            <p>{{$CurtainIdea}}</p>
                            <a href="" title="Click here to view more Curtain Selection Ideas" class="more-curtain {{$MoreCurtainIdea}}">Show more</a>
                        </div>
                        <div class="form-group view-less-curtain hidden">
                            <label for="">Ideas For Curtain Selection</label>
                            <ol class="pd-lt-13">
                                @if($CurtainIdeas !== "N/A")
                                @foreach($CurtainIdeas as $CurtainIdea)    
                                <li class="">{{$CurtainIdea}}</li>         
                                @endforeach
                                @endif
                            </ol>
                            <a href="" title="switch to minimized view" class="less-curtain">Show less</a>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Suggested Upholstery</label>
                            <ul class="pd-lt-13">
                                @if($SuggestedUpholstery !== "N/A")
                                @foreach($SuggestedUpholstery as $upholstery)    
                                <li class="">{{$upholstery}}</li>         
                                @endforeach
                                @else
                                N/A
                                @endif
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group view-more-upholstery">
                            <label for="">Ideas For Upholstery Selection</label>
                            <p>{{$UpholsteryIdea}}</p>
                            <a href="" title="Click here to view more Upholstery Selection Ideas" class="more-upholstery {{$MoreUpholsteryIdea}}">Show more</a>
                        </div>
                        <div class="form-group view-less-upholstery hidden">
                            <label for="">Ideas For Upholstery Selection</label>
                            <ol class="pd-lt-13">
                                @if($UpholsteryIdeas !== "N/A")
                                @foreach($UpholsteryIdeas as $UpholsteryIdea)    
                                <li class="">{{$UpholsteryIdea}}</li>         
                                @endforeach
                                @endif
                            </ol>
                            <a href="" title="switch to minimized view" class="less-upholstery">Show less</a>
                        </div>
                    </div>
                </div>
            </div>
            @if($CatalogueNames !== "N/A")
            <h4 class="col-md-12 text-primary mr-tp-4">Catalogue Information</h4>
            <div class="box-header with-border">
                <div class="row">
                    <div class="col-md-9 col-xs-9">
                        <div class="box-body table-responsive">
                            <table class="table table-bordered" id="CatalogueReportTable" style="margin: 0px auto;">
                                <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                                    <tr>
                                    <th class="text-center text-vertical-align pd-rt-8" width="20%">S.No</th>
                                    <th class="text-center text-vertical-align pd-rt-8" width="40%">Catalogue Name</th>
                                    <th class="text-center text-vertical-align pd-rt-8" width="40%">Page No</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($CatalogueNames as $Key => $CatalogueName)
                                    <tr>
                                    <td class="text-center text-vertical-align" width="20%">{{ $Key + 1 }}</td>
                                    <td class="text-center text-vertical-align" width="40%">{{$CatalogueName}}</td>
                                    <td class="text-center text-vertical-align" width="40%">{{$PageNo[$Key]}}</td>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            <h4 class="col-md-12 text-primary">Stock Availability</h4>
            <div class="box-header with-border">
                <div class="row">
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">No of Sheets available</label>
                            <p>{{$Sheets}}</p>
                        </div>
                    </div> 
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Status as on Date</label>
                            <p>{{$Status}}</p>
                        </div>
                    </div> 
                </div>
                <div class="row">
                    <div class="col-md-6 col-xs-6">
                        <div class="form-group">
                            <label for="">Notes</label>                            
                            <p>{{$StockNotes}}</p>
                        </div>
                    </div> 
                </div>
            </div>
            <h4 class="col-md-12 text-primary">Price</h4>
            <div class="box-header">
                <div class="row">
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Price</label>
                            <p>{{$Price}}</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Unit</label>
                            <p>{{$Unit}}</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Discount (%)</label>
                            <p>{{$Discount}}</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Tax (%) [Eg: GST]</label>                            
                            <p>{{$GST}}</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Installation Charges (Rs)</label>                            
                            <p>{{$InstallationCharges}}</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Delivery Charges (Rs)</label>                            
                            <p>{{$DeliveryCharges}}</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Annual Maintainence Cost (Rs)</label>                            
                            <p>{{$AMC}}</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-xs-3">
                        <div class="form-group">
                            <label for="">Price Range</label>
                            @if($PriceRange !== "N/A")
                            <p>{{implode(', ',$PriceRange)}}</p>
                            @else
                            <p><small>N/A</small></p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 col-xs-6">
                        <div class="form-group">
                            <label for="">Comments/Offers</label>                            
                            <p>{{$Comments}}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl('/js/magnific-popup.js') }}"></script>
<script src="{{ URL::assetUrl('/js/materials/view.js') }}"></script>
@endsection
