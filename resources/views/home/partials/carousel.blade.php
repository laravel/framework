<div class="row">
    <div class="col-md-12">
        <div class="box box-solid">
            <div class="box-body" id="carouselBox">
                <div id="Imagecarousel" class="carousel slide" data-ride="carousel">
                    <div class="carousel-inner">
                        @foreach($carousel as $image)
                            <div class="item @if ($loop->first) active @endif">
                                <div class="col-md-4 CarouselImageSec">
                                    <img src="{{ URL::CDN($image["Source"]) }}" class="img-responsive DashboardCarouselImage"/>
                                </div>
                                <div class="col-md-8 CarouselTextdSec">
                                    <div class="DashboardCaroueslData">
                                        <h2>Project : {{ $image["Title"] }}</h2>
                                        <p class="DashboardCarouselText">{{ $image["Description"] }}</p>
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
        </div>
    </div>   
</div>
