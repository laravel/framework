<div class="box-body">
    <div class="table-responsive">
    <table id="SeleSpecForPEeriodTable" class="table table-striped table-bordered"> 
        <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
<!--            <tr>
                <th class="rate-text-center"></th>
                <th class="rate-text-center">Name</th>
                <th class="rate-text-center" width="8%">Unit</th>  
                <th class="rate-text-center">Customer Rate(&#8377;)</th> 
                <th class="rate-text-center">Vendor Rate (&#8377;)</th> 
                <th class="rate-text-center">Price Package</th>
                <th class="rate-text-center" width="11%">Start Date</th>
                <th class="rate-text-center" width="13%">Created Date</th>
            </tr>-->
            <tr>
                <th width="3%" class="text-center text-vertical-align pd-rt-8">#</th>
                <th width="20%" class="text-center text-vertical-align">Name</th>
                <th width="8%" class="text-center text-vertical-align">Unit</th>  
                <th width="15%" class="text-center text-vertical-align">Customer Rate(&#8377;)</th> 
                <th width="15%" class="text-center text-vertical-align">Vendor Rate (&#8377;)</th> 
                <th width="15%" class="text-center text-vertical-align">Price Package</th>
                <th width="12%" class="text-center text-vertical-align">Start Date</th>
                <th width="12%" class="text-center text-vertical-align">Created Date</th>
            </tr>
        </thead>
    </table>
    <div class="row text-center mr-tp-8 mr-bt-10">
        <a href="{{ route('ratecards.reports.ratecardwithinperiod.download', ['type' => 'pdf', "city" => $CityId, "fromdate" => $FromDate, "todate" => $ToDate, "packageid" => $PackageId]) }}" id="DownloadPDF">
            <button type="button" class="btn btn-primary mr-rt-25">
                <i class="fa fa-file-pdf-o"></i> Download PDF
            </button>
        </a>

        <a href="{{ route('ratecards.reports.ratecardwithinperiod.download', ['type' => 'excel', "city" => $CityId, "fromdate" => $FromDate, "todate" => $ToDate, "packageid" => $PackageId]) }}" id="DownloadExcel">
            <button type="button" class="btn btn-primary">
                <i class="fa fa-file-excel-o"></i> Download Excel
            </button>
        </a>
    </div>
    </div>
</div>
