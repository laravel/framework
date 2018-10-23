<div class="row">
    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <a href="{{ route('designs.editlist') }}">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3>Designs</h3>
                    <p>&nbsp;</p>
                </div>
                <div class="icon">
                    <i class="ion ion-images"></i>
                </div>
                <span class="small-box-footer">Designs for projects allocated to you <i class="fa fa-arrow-circle-right"></i></span>
            </div>
        </a>
    </div>
    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <a  href="{{ route('preferences.index') }}">
            <div class="small-box bg-yellow">
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
