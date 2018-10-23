<?php $Manager = false ?>
@foreach(Auth::user()->Role()->get()->toArray() as $Key => $Role)
@if($Role["Slug"] === env("MANAGER_ROLE_SLUG", "manager"))
<?php $Manager = true ?>
@break
@endif
@endforeach
<div class="row">
    @if($Manager)
    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <a href="{{route('search.enquiries')}}">
            <div class="small-box bg-blue">
                <div class="inner">
                    <h3>Enquiries</h3>
                    <p>&nbsp;</p>
                </div>
                <div class="icon">
                    <i class="ion ion-document-text"></i>
                </div>
                <span class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></span>
            </div>
        </a>
    </div>
    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <a href="{{route('preferences.index')}}">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3>My Profile</h3>
                    <p>&nbsp;</p>
                </div>
                <div class="icon">
                    <i class="ion ion-person"></i>
                </div>
                <span class="small-box-footer" >More info <i class="fa fa-arrow-circle-right"></i></span>
            </div>
        </a>
    </div>
    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <a href="{{ route('search.quickestimates') }}">
            <div class="small-box bg-orange">
                <div class="inner">
                    <h3>Quick Estimates</h3>
                    <p>&nbsp;</p>
                </div>
                <div class="icon">
                    <i class="ion ion-android-checkbox-outline"></i>
                </div>
                <span class="small-box-footer" >More info <i class="fa fa-arrow-circle-right"></i></span>
            </div>
        </a>
    </div>
@else
<div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
    <a href="{{route('designs.editlist')}}">
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
@endif
</div>
