<div id="UpdateCustomItemModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title no-capitalize" id="HeadTitle">Update a Custom Item</h4>
            </div>
            <div class="modal-body">
                <form method="POST" id="UpdateCustomItemForm">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="UpdateCustomItemDescription">Description*</label>
                                <input type="text" name="UpdateCustomItemDescription" id="UpdateCustomItemDescription" class="form-control" autocomplete="off" :value="currentCustomItem.description"/>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="UpdateCustomItemRoom">Room*</label>
                                <select name="UpdateCustomItemRoom" id="UpdateCustomItemRoom" class="form-control custom-item-room" v-model="currentCustomItem.roomId">
                                    <option value="">Select</option>
                                    <option v-for="room in rooms" :value="room.id">@{{ room.name }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="UpdateCustomItemUnit">Unit*</label>
                                <select name="UpdateCustomItemUnit" id="UpdateCustomItemUnit" class="form-control custom-item-unit" v-model="currentCustomItem.unitId">
                                    <option value="">Select</option>
                                    <option v-for="unit in units" :value="unit.id">@{{ unit.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="UpdateCustomItemQuantity">Quantity*</label>
                                <input type="number" name="UpdateCustomItemQuantity" id="UpdateCustomItemQuantity" class="form-control" :value="currentCustomItem.quantity" min="1"/>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="UpdateCustomItemWidth">Width*</label>
                                <input type="number" name="UpdateCustomItemWidth" id="UpdateCustomItemWidth" class="form-control" :value="currentCustomItem.width" min="1"/>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="UpdateCustomItemHeight">Height*</label>
                                <input type="number" name="UpdateCustomItemHeight" id="UpdateCustomItemHeight" class="form-control" :value="currentCustomItem.height" min="1"/>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="UpdateCustomItemDepth">Depth*</label>
                                <input type="number" name="UpdateCustomItemDepth" id="UpdateCustomItemDepth" class="form-control" :value="currentCustomItem.depth" min="1"/>
                            </div>
                        </div>
                        <div class="col-md-4" v-if="_.isPlainObject(currentCustomItem.image)">
                            <div class="form-group">
                                <label class="control-label">Image</label>
                                <p class="form-control-static">
                                    <span>@{{ currentCustomItem.image.name }}</span>
                                    <b class="pd-8 text-hover" title="Delete" @click="deleteCustomItemImage">&times;</b>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4" v-else>
                            <div class="form-group">
                                <label for="UpdateCustomItemImage">Image</label>
                                <input type="file" name="UpdateCustomItemImage" id="UpdateCustomItemImage" class="form-control" accept="image/*"/>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="UpdateCustomItemNotes">Notes</label>
                                <input type="text" name="UpdateCustomItemNotes" id="UpdateCustomItemNotes" class="form-control" autocomplete="off" :value="currentCustomItem.notes"/>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="UpdateCustomItemCategory">Category*</label>
                                <select name="UpdateCustomItemCategory" id="UpdateCustomItemCategory" class="custom-item-category form-control" style="width:100%" v-model="currentCustomItem.categoryId">
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
                                    <input type="radio" name="UpdateCustomItemPaymentBy" id="UpdateCustomItemPaymentByCompany" value="Company" class="input-radio" v-model="currentCustomItem.paymentBy.name"/>
                                    <label for="UpdateCustomItemPaymentByCompany" tabindex="0"></label>
                                    <label for="UpdateCustomItemPaymentByCompany" class="text-normal cursor-pointer mr-rt-8">HECHPE</label>
                                    <input type="radio" name="UpdateCustomItemPaymentBy" id="UpdateCustomItemPaymentByCustomer" value="Customer" class="input-radio" v-model="currentCustomItem.paymentBy.name"/>
                                    <label for="UpdateCustomItemPaymentByCustomer" tabindex="0"></label>
                                    <label for="UpdateCustomItemPaymentByCustomer" class="text-normal cursor-pointer mr-rt-8">Third Party Vendor</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="UpdateCustomItemRatecardItems">Ratecard Item</label>
                                <select name="UpdateCustomItemRatecardItems" id="UpdateCustomItemRatecardItems" class="form-control" data-ratecards-url="{{ $ratecardItemsRoute }}">
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
                                    @foreach ($pricePackages as $index => $pricePackage)
                                        <tr>
                                            <td width="40%" class="text-vertical-align">{{ $pricePackage->name }}</td>
                                            <td width="30%" class="text-vertical-align">
                                                <div class="form-group mr-bt-0">
                                                    <input
                                                        type="number"
                                                        name="UpdateCustomItem-{{ $pricePackage->id }}-CustomerPrice"
                                                        id="UpdateCustomItem-{{ $pricePackage->id }}-CustomerPrice"
                                                        class="form-control update-custom-item-customer-price"
                                                        :value="currentCustomItem.pricePackages[{{ $index }}].customerRate"
                                                        min="1"
                                                    />
                                                </div>
                                            </td>
                                            <td width="30%" class="text-vertical-align">
                                                <div class="form-group mr-bt-0">
                                                    <input
                                                        type="number"
                                                        name="UpdateCustomItem-{{ $pricePackage->id }}-VendorPrice"
                                                        id="UpdateCustomItem-{{ $pricePackage->id }}-VendorPrice"
                                                        class="form-control update-custom-item-vendor-price"
                                                        :value="currentCustomItem.pricePackages[{{ $index }}].vendorRate"
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
                                <button type="submit" class="btn btn-primary button-custom" id="UpdateCustomItemFormSubmit">Update</button>
                                <button type="reset" class="btn btn-default button-custom" id="UpdateCustomItemFormReset">Undo Changes</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="form-overlay hidden" id="UpdateCustomItemFormOverlay">
                <div class="large loader"></div>
                <div class="loader-text">Updating Custom Item...</div>
            </div>
            <div id="UpdateCustomItemFormNotificationArea" class="notification-area"></div>
        </div>
    </div>
</div>
