<form action="" method="POST" accept-charset="utf-8" id="ModifyRateCardItemForm" data-item-id="{{$ItemDetails['Id']}}">
    <div class="row">
        <div class="col-md-2">
            <div class="form-group">
                <label for="RateCardItemCode">Item Code</label>
                <p class="form-control-static">{{$ItemDetails['Code']}}</p>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label for="RateCardItemUnit">Item Unit</label>
                <p class="form-control-static">{{$ItemDetails['Unit']}}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="RateCardItemName">Item Name <span style="color:red">*</span></label>
                <input type="text" name="RateCardItemName" placeholder='Ex: 24 Inch Depth Plywood Box with Shutters'  id="RateCardItemName" class="form-control" value="{{$ItemDetails['Name']}}" />
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="form-group">
                <label for="RateCardItemDescription">Item Description <span style="color:red">*</span></label>
                <textarea name="RateCardItemDescription" rows="4" placeholder='Ex: A box made of plywood with 24-26" depth, 6 mm plywood with lamination back and doors made with plywood laminated on both sides with edges edge banded with high quality PVC strips or polished wooden strips, based on the design, and with drawers, hanger rods and shelves as per the design' id="RateCardItemDescription" class="form-control" style="resize:none">{{$ItemDetails['Description']}}</textarea>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12 table-responsive">
            <table class="table table-striped table-bordered">
                <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                    <tr>
                        <th rowspan="2" width="20%" style="vertical-align:middle">Price Package</th>
                        <th colspan="2" width="24%" class="text-center">Existing Rates</th>
                        <th colspan="2" width="30%" class="text-center">New Rates</th>
                        <th rowspan="2" width="11%" class="text-center text-vertical-align">Start Date</th>
                        <th rowspan="2" width="15%" class="text-center text-vertical-align">New Start Date</th>
                    </tr>
                    <tr>
                        <th class="text-center" width="12%">Customer Rate (&#8377;)</th>
                        <th class="text-center" width="12%">Vendor Rate (&#8377;)</th>
                        <th class="text-center" width="15%">Customer Rate (&#8377;)</th>
                        <th class="text-center" width="15%">Vendor Rate (&#8377;)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ItemDetails["PricePackages"] as $Key => $PricePackage)
                    <tr>
                        <td style="vertical-align:middle"><b>{{$PricePackage["Name"]}}</b></td>
                        <td class="text-right" style="vertical-align:middle">{{$PricePackage["CurrentRateCard"]["CustomerRate"]}}</td>
                        <td class="text-right" style="vertical-align:middle">{{$PricePackage["CurrentRateCard"]["VendorRate"]}}</td>
                        <td>
                            <div class="has-feedback">
                                <input type="text" name="{{$PricePackage['Id']}}-CustomerRate" id="{{$PricePackage['Id']}}-CustomerRate" class="form-control input-sm" autocomplete="off" />
                            </div>
                        </td>
                        <td>
                            <div class="has-feedback">
                                <input type="text" name="{{$PricePackage['Id']}}-VendorRate" id="{{$PricePackage['Id']}}-VendorRate" class="form-control input-sm" autocomplete="off" />
                            </div>
                        </td>
                        <td class="text-center" style="vertical-align:middle">{{$PricePackage["CurrentRateCard"]["StartDate"]}}</td>
                        <td>
                            <div class="has-feedback">
                                <input type="text" name="{{$PricePackage['Id']}}-NewStartDate" id="{{$PricePackage['Id']}}-NewStartDate" class="form-control input-sm date-picker"  data-provide="datepicker" readonly="true" />
                                <i class="fa fa-calendar form-control-feedback"></i>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12" style="text-align:center">
            <p>
                <input type="submit" name="" value="Update" class="btn btn-primary button-custom" id="ModifyRateCardFormSubmit" />
                <input type="reset" name="" value="Cancel" class="btn btn-danger button-custom" id="ModifyRateCardFormReset" />
            </p>
        </div>
    </div>
</form>