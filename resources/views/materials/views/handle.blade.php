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
                                if (count($HandleImage) > 1) {
                                    $Imagetag = '<i class="ion ion-images gallery-icon"></i>';
                                } else {
                                    $Imagetag = '<img src="' . URL::CDN($HandleImage[0]["Path"]) . '" alt="Sample Laminate" class="note-thumbnail" title="' . $HandleImage[0]["UserFileName"] . '">';
                                }
                                ?>
                                <div class="image-link">
                                    <a href="{{URL::CDN($HandleImage[0]["Path"])}}" class="FullSheetImages" value="{{$handleImageJson}}"  class="cursor-pointer">
                                        {!!$Imagetag!!}
                                    </a>
                                </div>
                                @else
                                <p>{{$HandleImage}}</p>
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
                </div>
                <h4 class="col-md-12 text-primary no-text-transform Availabile_Sizes">Available Sizes</h4>
                <div class="box-header with-border">
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
