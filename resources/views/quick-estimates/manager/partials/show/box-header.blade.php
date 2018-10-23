<div class="box-header with-border">
    <div class="row">
        <div class="col-md-2">
            <div class="form-group">
                <label>Reference Number</label>
                <div class="form-control-static">{{ $estimate->referenceNumber }}</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label>Name</label>
                <div class="form-control-static">{{ $estimate->name }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="Enquiry" id="EnquiryInformation" class="text-blue text-hover" data-address='@json($address)'>Enquiry</label>
                <div class="form-control-static">{{ $enquiry->referenceNumber }} ({{ $enquiry->name }})</div>
            </div>
        </div>
        <div class="col-md-2 col-md-offset-3 text-right">
            <div class="form-group mr-tp-10">
                <a class="btn btn-primary" href="{{URL::route('users.quick-estimates.statistics',
                            ['user'=>$enquiryUser->id, 'estimate'=>$estimate->id])}}">
                    Statistics
                </a> 
            </div>
        </div>
    </div>
</div>
