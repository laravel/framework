@if(isset($Statistics['ChartsData']))         
<div class="row" style="page-break-after: always !important; page-break-inside: avoid;">
    <div class="col-xs-12">
        <div class="box-header with-border text-center pd-5">
            <b>Room wise Summary And Comparison Across Specification</b>
        </div> 
        <table class="table table-bordered">
            <tbody>
                <tr class="bg-primary bg-blue room-items">
                    <td  width="40%">Room</td>
                    <td  width="20%" class="text-center brand-bg-color">{{$PricePackages[0]["Name"]}}</td>
                    <td  width="20%" class="text-center hechpe-bg-color">{{$PricePackages[1]["Name"]}}</td>
                    <td  width="20%" class="text-center market-bg-color">{{$PricePackages[2]["Name"]}}</td>
                </tr>
                @foreach($Statistics['ChartsData']['Room'] as $index => $data)
                <tr>
                    <td>{{$index}}</td>
                    @foreach($PricePackages as $package)
                    <td  width="20%" class="text-right">&#8377; {{ money_format('%!.0n', $data[$package["Name"]]) }}</td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif