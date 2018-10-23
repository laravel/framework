<div class="row">
    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <a href="{{route('enquiries.index')}}">
            <div class="small-box bg-blue">
                <div class="inner">
                    <h3>My Enquiries</h3>
                    <p>&nbsp;</p>
                </div>
                <div class="icon">
                    <i class="ion ion-document-text"></i>
                </div>
                <span class="small-box-footer">Enquiries for your home interiors <i class="fa fa-arrow-circle-right"></i></span>
            </div>
        </a>
    </div>
    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <a href="{{route('quickestimates.list')}}">
            <div class="small-box bg-orange">
                <div class="inner">
                    <h3>My Quick Estimates</h3>
                    <p>&nbsp;</p>
                </div>
                <div class="icon">
                    <i class="ion ion-ios-list-outline"></i>
                </div>
                <span class="small-box-footer">Quick Estimates for enquiries raised <i class="fa fa-arrow-circle-right"></i></span>
            </div>
        </a>
    </div>
    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <a class="CursorPointer" id="PortfolioPopup">
            <div class="small-box bg-aqua">
                <div class="inner">
                    <h3>HECHPE Portfolio</h3>
                    <p>&nbsp;</p>
                </div>
                <div class="icon">
                    <i class="ion ion-filing"></i>
                </div>
                <span class="small-box-footer">Photos of work done by HECHPE <i class="fa fa-arrow-circle-right"></i></span>
            </div>
        </a>
    </div>
    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <a href="{{route('designs.list')}}">
        <div class="small-box bg-red">
            <div class="inner">
                <h3>My Designs</h3>
                <p>&nbsp;</p>
            </div>
            <div class="icon">
                <i class="ion ion-images"></i>
            </div>
            <span class="small-box-footer">Designs for your home interior <i class="fa fa-arrow-circle-right"></i></span>
        </div>
        </a>
    </div>
    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <a id="ToolTip" class="CursorPointer">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3 class="no-text-transform">Tips and Tools</h3>
                    <p>&nbsp;</p>
                </div>
                <div class="icon">
                    <i class="ion ion-android-bulb"></i>
                </div>
                <span class="small-box-footer">Useful tips and tools for home interior <i class="fa fa-arrow-circle-right"></i></span>
            </div>
        </a>
    </div>
    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <a  href="{{route('preferences.index')}}"><div class="small-box bg-yellow">
                <div class="inner">
                    <h3>My Profile</h3>
                    <p>&nbsp;</p>
                </div>
                <div class="icon">
                    <i class="ion ion-person"></i>
                </div>
                <span class="small-box-footer">Manage your profile details <i class="fa fa-arrow-circle-right"></i></span>
            </div>
        </a>
    </div>
</div>
@if(isset($Carousel))
<div class="row">
    <div class="col-md-12">
        <div class="box box-solid">
            <div class="box-body" id="carouselBox">
                <div id="Imagecarousel" class="carousel slide" data-ride="carousel">
                    <div class="carousel-inner">
                        @foreach($Carousel as $key=>$value)
                        <div class="item {{$loop->first?'active':''}}">
                            <div class="col-md-4 CarouselImageSec">
                                <img src="{{URL::CDN($value['Source'])}}" class="img-responsive DashboardCarouselImage">
                            </div>
                            <div class="col-md-8 CarouselTextdSec">
                                <div class="DashboardCaroueslData">
                                    <h2>Project : {{$value['Title']}}</h2>
                                    <p class="DashboardCarouselText">{{$value['Description']}}</p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <a class="left carousel-control DashboardCarouselControl" href="#Imagecarousel" data-slide="prev">
                        <span class="fa fa-angle-left" id="CarouselLeftControl"></span>
                    </a>
                    <a class="right carousel-control DashboardCarouselControl" href="#Imagecarousel" data-slide="next">
                        <span class="fa fa-angle-right"></span>
                    </a>
                </div>
            </div>
            <!-- /.box-body -->
        </div>
        <!-- /.box -->
    </div>   
</div>
@endif
<div id="ToolTipModal" class="modal fade" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header text-center">
                <h4 class="modal-title no-capitalize">Tips and Tools
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span></button></h4>
            </div>
            <div class="modal-body">
                <div class="row ">
                    <div class="col-sm-12">
                        @if(isset($ToolTipData))
                        <ul class="products-list product-list-in-box">
                            @foreach($ToolTipData as $Value)
                            <li class="item">
                                <div class="col-md-2 col-sm-3 col-xs-12">
                                <div class="product-img ToolTipImg">
                                    <img src="{{URL::CDN($Value['Image'])}}" alt="Product Image">
                                </div>
                                    </div>
                                <div class="col-md-10 col-sm-9 col-xs-12">
                                <div class="product-info ToolTipData">
                                    <h4 class="product-title">{{$Value['Title']}}</h4>
                                    <p>
                                       {{$Value['Description']}}
                                    </p>
                                </div>
                                    </div>
                            </li>
                            @endforeach
                            <!-- /.item -->
                        </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
