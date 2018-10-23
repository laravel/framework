<div class="box-body">
    <div class="table-responsive">
    <table id="AllSpecRateCardsTable" class="table table-striped table-bordered"> 
        <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
            <tr>
                <th width="4%" rowspan="2" class="text-center text-vertical-align pd-rt-8">#</th>
                <th width="25%" rowspan="2" class="text-center text-vertical-align">Name</th>
                <th width="8%" rowspan="2" class="text-center text-vertical-align">Unit</th>                         
                @foreach($PricePackages as $Key => $PricePackage)
                <th width="21%" colspan="2"class="text-center text-vertical-align">{{$PricePackage->Name}}</th>
                @endforeach     
            </tr>
            <tr>
                @foreach($PricePackages as $Key => $PricePackage)
                <th width="3%"  class="text-center text-vertical-align"><span>Customer Rate (&#8377;)</span></th>
                <th width="3%"  class="text-center text-vertical-align" style="text-transform:none;"><span>Vendor Rate (&#8377;)</span></th>
                @endforeach
            </tr>
        </thead>
    </table>
    <div class="row text-center mr-tp-8 mr-bt-10">
        <a href="{{ route('ratecards.reports.download', ['type' => 'pdf', "city" => $CityId]) }}" id="DownloadPDF">
            <button type="button" class="btn btn-primary mr-rt-25">
                <i class="fa fa-file-pdf-o"></i> Download PDF
            </button>
        </a>

        <a href="{{ route('ratecards.reports.download', ['type' => 'excel', "city" => $CityId]) }}" id="DownloadExcel">
            <button type="button" class="btn btn-primary">
                <i class="fa fa-file-excel-o"></i> Download Excel
            </button>
        </a>
    </div>
    </div>
</div>
