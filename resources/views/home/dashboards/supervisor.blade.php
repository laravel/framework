<div class="row">
    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <a href="{{ route('myprojects.list') }}">
            <div class="small-box bg-cadetblue">
                <div class="inner">
                    <h3>Projects</h3>
                    <p>&nbsp;</p>
                </div>
                <div class="icon">
                    <i class="fa fa-clipboard"></i>
                </div>
                <span class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></span>
            </div>
        </a>
    </div>
    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <a href="{{ route('user.measurements') }}">
            <div class="small-box bg-purple">
                <div class="inner">
                    <h3>Site Measurements</h3>
                    <p>&nbsp;</p>
                </div>
                <div class="icon">
                    <i class="ion ion-clipboard"></i>
                </div>
                <span class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></span>
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
</div>