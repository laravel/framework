<div id="CreateCustomItemModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title no-capitalize" id="HeadTitle">Create a new Custom Item for this Quick Estimation</h4>
            </div>
            <div class="modal-body">
                <form method="POST" id="CreateCustomItemForm">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="CustomItemDescription">Description*</label>
                                <input type="text" name="CustomItemDescription" id="CustomItemDescription" class="form-control" autocomplete="off"/>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="CustomItemRoom">Room*</label>
                                <select name="CustomItemRoom" id="CustomItemRoom" class="form-control custom-item-room">
                                    <option value="">Select</option>
                                    <option v-for="room in rooms" :value="room.id">@{{ room.name }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="CustomItemUnit">Unit*</label>
                                <select name="CustomItemUnit" id="CustomItemUnit" class="form-control custom-item-unit">
                                    <option value="">Select</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="CustomItemQuantity">Quantity*</label>
                                <input type="number" name="CustomItemQuantity" id="CustomItemQuantity" class="form-control" value="1" min="1"/>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="CustomItemWidth">Width*</label>
                                <input type="number" name="CustomItemWidth" id="CustomItemWidth" class="form-control" value="1" min="1"/>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="CustomItemHeight">Height*</label>
                                <input type="number" name="CustomItemHeight" id="CustomItemHeight" class="form-control" value="1" min="1"/>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="CustomItemDepth">Depth*</label>
                                <input type="number" name="CustomItemDepth" id="CustomItemDepth" class="form-control" value="1" min="1"/>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="CustomItemImage">Image</label>
                                <input type="file" name="CustomItemImage" id="CustomItemImage" class="form-control" accept="image/*"/>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="CustomItemNotes">Notes</label>
                                <input type="text" name="CustomItemNotes" id="CustomItemNotes" class="form-control" autocomplete="off"/>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="CustomItemCategory">Category*</label>
                                <select name="CustomItemCategory" id="CustomItemCategory" class="custom-item-category form-control" style="width:100%">
                                    <option value="">Select</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="no-text-transform">Payment by Customer to*</label>
                                <div class="mr-tp-6">
                                    <input type="radio" name="CustomItemPaymentBy" id="CustomItemPaymentByCompany" value="Company" checked="checked" class="input-radio"/>
                                    <label for="CustomItemPaymentByCompany" tabindex="0"></label>
                                    <label for="CustomItemPaymentByCompany" class="text-normal cursor-pointer mr-rt-8">HECHPE</label>
                                    <input type="radio" name="CustomItemPaymentBy" id="CustomItemPaymentByCustomer" value="Customer" class="input-radio">
                                    <label for="CustomItemPaymentByCustomer" tabindex="0"></label>
                                    <label for="CustomItemPaymentByCustomer" class="text-normal cursor-pointer mr-rt-8">Third Party Vendor</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="CustomItemRatecardItems">Ratecard Item</label>
                                <select name="CustomItemRatecardItems" id="CustomItemRatecardItems" class="form-control" data-ratecards-url="{{ $ratecardItemsRoute }}">
                                    <option value="">Select</option>
                                    @foreach ($ratecardItems as $ratecardItem)
                                        <option value="{{ $ratecardItem->id }}">{{ $ratecardItem->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-bordered table-striped">
                                <thead class="bg-light-blue">
                                    <tr>
                                        <th width="40%" class="text-vertical-align">Price Package</th>
                                        <th width="30%" class="text-vertical-align">Customer Rate (&#8377;)</th>
                                        <th width="30%" class="text-vertical-align">Vendor Rate (&#8377;)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($pricePackages as $pricePackage)
                                        <tr>
                                            <td width="40%" class="text-vertical-align">{{ $pricePackage->name }}</td>
                                            <td width="30%" class="text-vertical-align">
                                                <div class="form-group mr-bt-0">
                                                    <input
                                                        type="number"
                                                        name="CustomItem-{{ $pricePackage->id }}-CustomerPrice"
                                                        id="CustomItem-{{ $pricePackage->id }}-CustomerPrice"
                                                        class="form-control custom-item-customer-price"
                                                        value="1"
                                                        min="1"
                                                    />
                                                </div>
                                            </td>
                                            <td width="30%" class="text-vertical-align">
                                                <div class="form-group mr-bt-0">
                                                    <input
                                                        type="number"
                                                        name="CustomItem-{{ $pricePackage->id }}-VendorPrice"
                                                        id="CustomItem-{{ $pricePackage->id }}-VendorPrice"
                                                        class="form-control custom-item-vendor-price"
                                                        value="1"
                                                        min="1"
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="row mr-tp-12">
                        <div class="col-md-12">
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary button-custom" id="CreateCustomItemFormSubmit">Create</button>
                                <button type="reset" class="btn btn-default button-custom" id="CreateCustomItemFormReset">Undo Changes</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="form-overlay hidden" id="CreateCustomItemFormOverlay">
                <div class="large loader"></div>
                <div class="loader-text">Creating Custom Item...</div>
            </div>
            <div id="CreateCustomItemFormNotificationArea" class="notification-area"></div>
        </div>
    </div>
</div>
