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
                                <label for="">Name</label>                            
                                <p>{{$Name}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Code</label>
                                <p>{{$Code}}</p>
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
                                if (count($FullSheetmage) > 1) {
                                    $Imagetag = '<i class="ion ion-images gallery-icon"></i>';
                                } else {
                                    $Imagetag = '<img src="' . URL::CDN($FullSheetmage[0]["Path"]) . '" alt="Sample Laminate" class="note-thumbnail" title="' . $FullSheetmage[0]["UserFileName"] . '">';
                                }
                                ?>
                                <div class="image-link">
                                    <a href="{{URL::CDN($FullSheetmage[0]["Path"])}}" class="FullSheetImages" value="{{$fullImageJson}}"  class="cursor-pointer">
                                         {!!$Imagetag!!}
                                    </a>
                                </div>
                                @else
                                <p>{{$FullSheetmage}}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-xs-6">
                            <div class="form-group">
                                <label for="">Features</label>                            
                                <p>{{$Features}}</p>
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
                <div class="box-header" style="padding-bottom: 0px !important;padding-top: 0px !important;">
                <h4 class="col-md-2 text-primary">Available Sizes
                <span data-toggle='tooltip' id='AddSizeIcon' class='more-sizes cursor-pointer' title='Show More'>&nbsp&nbsp<i class='fa fa-plus-circle' aria-hidden='true'></i></span>
                <span data-toggle='tooltip' id='LessSizeIcon' class='less-sizes cursor-pointer hidden' title='Show Less'>&nbsp&nbsp<i class='fa fa-minus-circle' aria-hidden='true'></i></span></h4>
                </div>
                <div class="box-header with-border">
                    <div class="row">
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group view-more-sizes">
                                <label for="">Width (mm)</label>
                                <p>{{$WidthSize}}</p>
                            </div>
                            <div class="form-group view-less-sizes hidden">
                                <label for="">Width (mm)</label>
                                <ul class="pd-lt-13">
                                    @if($Width !== "N/A")
                                    @foreach($Width as $size)    
                                    <li class="">{{$size}}</li>         
                                    @endforeach
                                    @endif
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group view-more-sizes">
                                <label for="">Thickness (mm)</label>
                                <p>{{$ThicknessSize}}</p>
                            </div>
                            <div class="form-group view-less-sizes hidden">
                                <label for="">Thickness (mm)</label>
                                <ul class="pd-lt-13">
                                    @if($Thickness !== "N/A")
                                    @foreach($Thickness as $size)    
                                    <li class="">{{$size}}</li>         
                                    @endforeach
                                    @endif
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group view-more-sizes">
                                <label for="">Min Quantity (Meter)</label>
                                <p>{{$MinQuantitySize}}</p>
                            </div>
                            <div class="form-group view-less-sizes hidden">
                                <label for="">Min Quantity (Meter)</label>
                                <ul class="pd-lt-13">
                                    @if($MinQuantity !== "N/A")
                                    @foreach($MinQuantity as $size)    
                                    <li class="">{{$size}}</li>         
                                    @endforeach
                                    @endif
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group view-more-sizes">
                                <label for="">Total Role (Meter)</label>
                                <p>{{$TotalRoleSize}}</p>
                            </div>
                            <div class="form-group view-less-sizes hidden">
                                <label for="">Total Role (Meter)</label>
                                <ul class="pd-lt-13">
                                    @if($TotalRole !== "N/A")
                                    @foreach($TotalRole as $size)    
                                    <li class="">{{$size}}</li>         
                                    @endforeach
                                    @endif
                                </ul>
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
                                <label for="">No of Rolls available</label>
                                <p>{{$Rolls}}</p>
                            </div>
                        </div> 
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">No of Meters available</label>
                                <p>{{$Meters}}</p>
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
