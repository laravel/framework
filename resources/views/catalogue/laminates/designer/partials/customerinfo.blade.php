<div class="col-md-12 text-right custom-info-block" :class="{ hidden : (CustomerDetails == null) }">
     <span class="pd-5 text-capitalize user-info">
        <i class="fa fa-user text-info" aria-hidden="true"></i>&nbsp;
        @{{ !(CustomerDetails == null) ? CustomerDetails.userName : '' }}
    </span>
    <span class="pd-5 user-info">
        <i class="fa fa-phone-square text-info" aria-hidden="true"></i>&nbsp;
        @{{ !(CustomerDetails == null) ? CustomerDetails.mobile: '' }}
    </span>
    <span class="pd-5 user-info"> 
        <i class="fa fa-envelope-square text-info" aria-hidden="true"></i>&nbsp;
        @{{ !(CustomerDetails == null) ? CustomerDetails.email: '' }}
    </span>
</div>