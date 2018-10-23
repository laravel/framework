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
                                <p>{{$DesignName}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Design Code</label>
                                <p>{{$DesignCode}}</p>
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
                        <div class="col-md-6 col-xs-6">
                            <div class="form-group">
                                <label for="">Tags</label>
                                <p>{{$Tags}}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <h4 class="col-md-12 text-primary">Usable Fabric</h4>
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
                    <div class="col-md-12">
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">GSM</label>
                                <p>{{$GSM}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Upholstery Image</label>
                                @if($UpholsteryImage !== "N/A")
                                <?php
                                $upholsteryImageJson = json_encode($UpholsteryImage);
                                $UpholsteryImagetag = (count($UpholsteryImage) > 1) ? '<i class="ion ion-images gallery-icon"></i>' : '<img src="' . URL::CDN($UpholsteryImage[0]["Path"]) . '" alt="Sample Laminate" class="note-thumbnail" title="' . $UpholsteryImage[0]["UserFileName"] . '">';
                                ?>
                                <div class="image-link">
                                    <a href="{{URL::CDN($UpholsteryImage[0]["Path"])}}" class="FullSheetImages" value="{{$upholsteryImageJson}}"  class="cursor-pointer">
                                        {!! $UpholsteryImagetag !!}
                                    </a>
                                </div>
                                @else
                                <p>{{$UpholsteryImage}}</p>
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
                </div>
            </div>
            <div class="box-header with-border">
                <h4 class="col-md-12 text-primary mr-tp-4">Care Instructions</h4>
                <div class="row">
                    <div class="col-xs-12">
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Hand Wash</label>
                                <p>{{$HandWash}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Machine Wash</label>
                                <p>{{$MachineWash}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Dry Clean</label>
                                <p>{{$DryClean}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">No Hot Iron</label>
                                <p>{{$NoHotIron}}</p>
                            </div>
                        </div><div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">30'c Wash</label>
                                <p>{{$thirtyCWash}}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @if($CatalogueNames !== "N/A")
            <div class="box-header with-border">
                <h4 class="col-md-12 text-primary mr-tp-4">Catalogue Information</h4>
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
            <div class="box-header">
                <h4 class="col-md-12 text-primary">Price</h4> <div class="row">
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
                    <div class="col-md-12">
                        <div class="col-md-6 col-xs-6">
                            <div class="form-group">
                                <label for="">Notes</label>                            
                                <p>{{$Notes}}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
             @endif
        </div>
    </div>
</div>
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl('/js/magnific-popup.js') }}"></script>
<script src="{{ URL::assetUrl('/js/materials/view.js') }}"></script>
@endsection
