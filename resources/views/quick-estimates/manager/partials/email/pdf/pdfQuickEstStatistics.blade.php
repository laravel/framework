@if(isset($Statistics['ChartsData']))
<div class="box-header with-border text-center pd-5">
    <b>Category wise Summary And Comparison Across Specification</b>
</div>               
<div class="row"  style="page-break-after: always !important;">
    <div class="col-xs-12">
        <table class="table table-bordered">
            <tbody>
                 <tr class="bg-primary bg-blue room-items">
                     <td  width="40%">Category</td>
                     <td  width="20%" class="text-center brand-bg-color">{{$PricePackages[0]["Name"]}}</td>
                     <td  width="20%" class="text-center hechpe-bg-color">{{$PricePackages[1]["Name"]}}</td>
                     <td  width="20%" class="text-center market-bg-color">{{$PricePackages[2]["Name"]}}</td>
                 </tr>
                 @foreach($Statistics['ChartsData']['Category'] as $index => $data)
                 <tr>
                     <td>{{$index}}</td>

                     @foreach($PricePackages as $package)
                     <td  width="20%" class="text-right">&#8377; {{ money_format('%!.0n', $data[$package["Name"]]) }}</td>
                     @endforeach
                 </tr>
                 @endforeach
                 <tr class="bg-primary bg-blue room-items">
                    <td>Total</td>
                    <td  width="20%" class="text-right brand-bg-color">&#8377; {{ money_format('%!.0n', $Statistics['Total'][$PricePackages[0]["Name"]])}}</td>
                    <td  width="20%" class="text-right hechpe-bg-color">&#8377; {{ money_format('%!.0n', $Statistics['Total'][$PricePackages[1]["Name"]])}}</td>
                    <td  width="20%" class="text-right market-bg-color">&#8377; {{ money_format('%!.0n', $Statistics['Total'][$PricePackages[2]["Name"]])}}</td>
                 </tr>
                 @foreach($Statistics["ChartsData"]["PaymentBy"] as $i => $val)
                 <tr>
                     <td>Paid to {{$i==="Customer"?"Other Vendors":"HECHPE"}} by Homeowner</td>
                     @foreach($PricePackages as $package)
                     <td  width="20%" class="text-right">&#8377; {{ money_format('%!.0n', $val[$package["Name"]]) }}</td>
                     @endforeach
                 </tr>
                 @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
