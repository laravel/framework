<div class="row">
    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <a href="{{ route('enquiries.index') }}">
            <div class="small-box bg-blue">
                <div class="inner">
                    <h3>Enquiry</h3>
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
        <a href="{{ route('quick-estimates.index') }}">
            <div class="small-box bg-orange">
                <div class="inner">
                    <h3>Quick Estimate</h3>
                    <p>&nbsp;</p>
                </div>
                <div class="icon">
                    <i class="ion ion-ios-list-outline"></i>
                </div>
                <span class="small-box-footer">Quick Estimates for enquiries raised <i class="fa fa-arrow-circle-right"></i></span>
            </div>
        </a>
    </div>
    <!--    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <a href="#" id="Portfolio">
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
        </div>-->
    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <a href="{{ route('ideas.create') }}" id="Ideas">
            <div class="small-box bg-aqua">
                <div class="inner">
                    <h3>Ideas</h3>
                    <p>&nbsp;</p>
                </div>
                <div class="icon">
                    <i class="ion ion-android-bulb"></i>
                </div>
                <span class="small-box-footer">Ideas for home interior <i class="fa fa-arrow-circle-right"></i></span>
            </div>
        </a>
    </div>
    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <a href="{{ route('designs.list') }}">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3>Design</h3>
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
        <div class="small-box bg-green">
            <div class="inner HomePageAction">
                <h3 class="no-text-transform">Actions</h3>
                <p class="small-box-footer actions">
                    @if($Actions!="")
                    <span class="Popover CursorPointer" href="#" data-toggle="popover" title="" data-content="{!!$Actions!!}">{!!$ActionsCount!!}</span>
                    @else
                        {{'No actions required.'}}
                    @endif
                </p>
            </div>
            <div class="icon">
                <i class="fa fa-wrench"></i>
            </div>
            <span class="small-box-footer">Actions need to be taken </span>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <a href="{{ route('faqs.view') }}">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3>FAQs</h3>
                    <p>&nbsp;</p>
                </div>
                <div class="icon">
                    <i class="fa fa-question"></i>
                </div>
                <span class="small-box-footer">Frequently asked questions <i class="fa fa-arrow-circle-right"></i></span>
            </div>
        </a>
    </div>

    <!--    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <a href="#" data-toggle="modal" data-target="#TipsAndToolsModal">
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
        </div>-->
</div>

{{-- Include projects carousel --}}
@isset ($carousel)
@include ('home.partials.carousel')
@endisset

{{-- Tips and Tools modal --}}
@include ('home.partials.tipsandtools')

{{-- Portfolio gallery --}}
<div class="hidden" id="PortfoliosList">
    @foreach ($portfolios as $portfolio)
    <a href="{{ cdn_asset($portfolio->FeatureImageSrc) }}">{{ $portfolio->ProjectName }}</a>
    @endforeach
</div>
