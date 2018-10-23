@extends('layouts/master_template')
@section('content')
@section('dynamicStyles')
<link rel="stylesheet" href="{{ asset('/css/materials/view.css') }}" />
<link rel="stylesheet" href="{{ URL::assetUrl("/css/magnific-popup.css")}}">
@endsection
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
                                <label for="">Type of Fan</label>
                                <p>{{$Type}}</p>
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
                    </div>
                    <div class="row">
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Colour</label>
                                <p>{{$Colour}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Blade Finish</label>
                                <p>{{$BladeFinish}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Rated Speed (RPM)</label>
                                <p>{{$RatedSpeed}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Weight</label>
                                <p>{{$Weight}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">No of Blades</label>                            
                                <p>{{$NoOfBlades}}</p>
                            </div>
                        </div>  
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Downrod Length (Inches)</label>                            
                                <p>{{$DownrodLength}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Air Delivery (Cu. Meter/Min)</label>                            
                                <p>{{$AirDelivery}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Power Consumption (Watts)</label>
                                <p>{{$PowerConsumption}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Rated Voltage</label>
                                <p>{{$RatedVoltage}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Rated Frequency (Hertz)</label>                            
                                <p>{{$RatedFrequency}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Guarantee (Years)</label>                            
                                <p>{{$Guarantee}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Warranty (Years)</label>                            
                                <p>{{$Warranty}}</p>
                            </div>
                        </div>
                    </div>
                        <div class="row">
                            <div class="col-md-3 col-xs-3">
                                <div class="form-group">
                                    <label for="">Fan Image</label>
                                    @if($FanImage !== "N/A")
                                    <?php
                                    $fanImageJson = json_encode($FanImage);
                                    if (count($FanImage) > 1) {
                                        $Imagetag = '<i class="ion ion-images gallery-icon"></i>';
                                    } else {
                                        $Imagetag = '<img src="' . URL::CDN($FanImage[0]["Path"]) . '" alt="Sample Laminate" class="note-thumbnail" title="' . $FanImage[0]["UserFileName"] . '">';
                                    }
                                    ?>
                                    <div class="image-link">
                                        <a href="{{URL::CDN($FanImage[0]["Path"])}}" class="FullSheetImages" value="{{$fanImageJson}}"  class="cursor-pointer">
                                            {!!$Imagetag!!}
                                        </a>
                                    </div>
                                    @else
                                    <p>{{$FanImage}}</p>
                                    @endif
                                </div>
                            </div>  
                            <div class="col-md-6 col-xs-6">
                                <div class="form-group">
                                    <label for="">Tags</label>
                                    <p>{{$Tags}}</p>
                                </div>
                            </div>
                        </div>
                        <h4 class="col-md-12 text-primary">Dimension for Blades (mm)</h4>
                        <div class="box-header with-border">
                            <div class="row">
                                <div class="col-md-3 col-xs-3">
                                    <div class="form-group">
                                        <label for="">Width </label>
                                        <p>{{$BladeWidth}}</p>
                                    </div>
                                </div>
                                <div class="col-md-3 col-xs-3">
                                    <div class="form-group">
                                        <label for="">Height/Length</label>
                                        <p>{{$BladeHeight}}</p>
                                    </div>
                                </div>
                                <div class="col-md-3 col-xs-3">
                                    <div class="form-group">
                                        <label for="">Depth</label>                            
                                        <p>{{$BladeDepth}}</p>
                                    </div>
                                </div>  
                            </div>
                            <h4 class="col-md-12 text-primary" style="margin-left: -25px;">Dimension for Motor (mm)</h4>
                            <div class="row">
                                <div class="col-md-3 col-xs-3">
                                    <div class="form-group">
                                        <label for="">Width </label>
                                        <p>{{$MotorWidth}}</p>
                                    </div>
                                </div>
                                <div class="col-md-3 col-xs-3">
                                    <div class="form-group">
                                        <label for="">Height/Length</label>
                                        <p>{{$MotorHeight}}</p>
                                    </div>
                                </div>
                                <div class="col-md-3 col-xs-3">
                                    <div class="form-group">
                                        <label for="">Depth</label>                            
                                        <p>{{$MotorDepth}}</p>
                                    </div>
                                </div>  
                            </div>
                            <div class="row">
                                <div class="col-md-6 col-xs-6">
                                    <div class="form-group">
                                        <label for="">Finish</label>                            
                                        <p>{{$Finish}}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 col-xs-6">
                                    <div class="form-group">
                                        <label for="">Additional Notes</label>                            
                                        <p>{{$AdditionalNotes}}</p>
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
                         @if(!auth()->user()->isCustomer())
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
                                        <label for="">Notes</label>                            
                                        <p>{{$Notes}}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endsection
        @section('dynamicScripts')
        <script src="{{ URL::assetUrl('/js/magnific-popup.js') }}"></script>
        <script src="{{ URL::assetUrl('/js/materials/view.js') }}"></script>
        @endsection
