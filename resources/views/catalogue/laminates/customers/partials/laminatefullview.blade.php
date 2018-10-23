                
                    <h4 
                        class="no-text-transform mr-tp-0 full-view-heading">
                        {{$laminate->BrandName}}
                        <span>|</span> {{$laminate->DesignName}} 
                        <span>|</span> {{$laminate->DesignNo}}
                    </h4>
                    <div class="row">
                        <div class="col-xs-2">
                            <div class="form-group">
                                <a href="javascript:void(0)">
                                    <img 
                                        src="{{URL::CDN("/").$laminate->FullSheetImage[0]['Path']}}" 
                                        alt="Full Sheet Image" 
                                        class="laminate-full-image" 
                                        title="{{$laminate->FullSheetImage[0]['UserFileName']}}"
                                    >
                                </a>
                            </div>
                        </div>
                        <div class="col-xs-10">
                            <div class="row">
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label for="">Sub Brand</label>   
                                        <p>{{$laminate->SubBrand}}</p>
                                    </div>
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                       <label>Category</label> 
                                       <p>{!!$laminate->CategoryName ? $laminate->CategoryName : '<small>N/A</small>'!!}</p>
                                    </div> 
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                       <label>Range</label> 
                                       <p>{!!$laminate->SurfaceRange ? $laminate->SurfaceRange : '<small>N/A</small>'!!}</p>
                                    </div> 
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                       <label>Surface Finish</label> 
                                       <p>{!!$laminate->SurfaceFinish ? $laminate->SurfaceFinish : '<small>N/A</small>'!!}</p>
                                    </div> 
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-3">
                                    <div class="form-group">
                                       <label>Textured Surface</label> 
                                       <p>{{($laminate->TexturedSurface === '1' ? "Yes" : "No")}}</p>
                                    </div> 
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                       <label>Glossiness</label> 
                                       <p>{{($laminate->Glossy === '1') ? "Yes" : "No"}}</p>
                                    </div> 
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label>Swatch Image
                                            @if($laminate->SampleImage)
                                            <a 
                                                href="{{route('download.laminates.swatch.image.zip', ["id" => $laminate->LaminateId])}}" 
                                                data-toggle="tooltip" 
                                                title="Download all Swatch images" 
                                            >
                                            <i class="fa fa-fw fa-download"></i>
                                            </a>
                                            @endif
                                        </label>
                                        @if($laminate->SampleImage)
                                        <div>
                                            <span class="element-container">
                                                    <a href="javascript:void(0)">
                                                        <img src="{{URL::CDN("/").$laminate->SampleImage[0]['Path']}}" alt="Swatch Image" class="note-thumbnail overlay-img" title="{{$laminate->SampleImage[0]['UserFileName']}}">
                                                    </a>
                                                
                                                <div title="Download" class="middle cursor-pointer">
                                                  <a href="{{route('download.laminates.swatch.image', ['id' => $laminate->LaminateId])}}">
                                                      <i class="fa fa-fw fa-download download-icon"></i>
                                                  </a>
                                                </div>
                                            </span>
                                        </div>
                                        @else
                                        <div><small>N/A</small></div>
                                        @endif
                                    </div> 
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                       <label>Usage Image</label> 
                                       @if($laminate->UsageImage)
                                       <p>
                                            <a href="javascript:void(0)">
                                                <img 
                                                    src="{{URL::CDN("/").$laminate->UsageImage[0]['Path']}}" 
                                                    alt="Usage Image" 
                                                    class="note-thumbnail" 
                                                    title="{{$laminate->UsageImage[0]['UserFileName']}}"
                                                >
                                            </a>
                                        </p>
                                        @else
                                        <p><small>N/A</small></p>
                                        @endif
                                    </div> 
                                </div>
                            </div>
                            <h4 class="text-primary">EdgeBand Availability:
                                <span class="EdgebandStatus">
                                    {{($laminate->Edgeband === '1') ? "Yes" : "No"}}
                                </span>
                            </h4>
                            @if($laminate->Edgeband == 1)
                            <div class="row">
                                <div class="col-md-3 col-xs-3">
                                    <div class="form-group">
                                        <label for="">Exact Match</label>
                                        <p>{!!$laminate->ExactMatch!!}</p>
                                    </div>
                                </div>
                                <div class="col-md-3 col-xs-3">
                                    <div class="form-group">
                                        <label for="">Nearest Match</label>
                                        <p>{!!$laminate->NearestMatch!!}</p>    
                                    </div>
                                </div> 
                                <div class="col-md-3 col-xs-3">
                                    <div class="form-group">
                                        <label for="">Contrast Match</label>
                                        <p>{!!$laminate->ContrastMatch!!}</p>
                                    </div>
                                </div>
                            </div>
                            @endif
                            <div class="row">
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label for="">Scratch Resistant</label>   
                                        <p>{{($laminate->ScratchResistant != null) ? "Yes" : "No"}}</p>
                                    </div>
                                </div>
                                <div class="col-xs-3">
                                     <div class="form-group">
                                         <label for="">Color Fast</label>   
                                         <p>{{$laminate->ColorFast != null ? "Yes" : "No"}}</p>
                                     </div>
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label for="">Heat Resistant</label>   
                                        <p>{{$laminate->HeatResistant != null ? "Yes" : "No"}}</p>
                                    </div>
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label for="">Stain Resistant</label>   
                                        <p>{{$laminate->StainResistant != null ? "Yes" : "No"}}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label for="">Superior Gloss Level</label>   
                                        <p>{{($laminate->GlossLevel != null) ? "Yes" : "No"}}</p>
                                    </div>
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label for="">Thickness Tolerance</label>   
                                        <p>{{($laminate->ThickTolerance != null) ? "Yes" : "No"}}</p>
                                    </div>
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label for="">Surface Water Resistance</label>   
                                        <p>{{($laminate->SurfaceWaterRes != null) ? "Yes" : "No"}}</p>
                                    </div>
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label for="">Boiling Water Resistance</label>   
                                        <p>{{($laminate->BoilingWaterResistant != null) ? "Yes" : "No"}}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label for="">Hight Temperature Resistance</label>   
                                        <p>{{($laminate->HighTemperatureResistant != null) ? "Yes" : "No"}}</p>
                                    </div>
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label for="">Suggested Pairing</label>   
                                        <p>{!!$laminate->SuggestedPairing!!}</p>
                                    </div>
                                </div>
                                <div class="col-xs-6">
                                    <div class="form-group">
                                        <label for="">Suggested Usage</label>   
                                        <p>{!!$laminate->SuggestedUsage!!}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label for="">No of Sheets available</label> 
                                        <p>{!!($laminate->Sheets) ? $laminate->Sheets : '<small>N/A</small>'!!}</p>  
                                    </div>
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label for="">Status as on Date</label> 
                                        <p>{!!($laminate->Status) ? $laminate->Status : '<small>N/A</small>'!!}</p>  
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                