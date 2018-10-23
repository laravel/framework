@extends('layouts/master_template')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <form action="{{ $currentItem->updateRoute() }}" method="POST" id="UpdateItemForm">
                        {{ method_field("PATCH") }}
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="Description">Description*</label>
                                    <input type="text" name="Description" id="Description" class="form-control" placeholder="24 Inch Depth Plywood Box with Shutters" value="{{ $currentItem->description }}" autofocus="autofocus"/>
                                </div>
                            </div>
                            <div class="col-md-4 @if ($currentItem->type == "DE") hidden @endif" id="RoomsBlock">
                                <div class="form-group">
                                    <label for="Rooms">Rooms*</label>
                                    <select name="Rooms[]" id="Rooms" class="form-control" multiple="multiple" style="width:100%">
                                        <option value="">Select a Rooms</option>
                                        @foreach ($rooms as $room)
                                            @if ($currentItemRooms->contains("id", $room->id))
                                                <option value="{{ $room->id }}" selected="selected">{{ $room->name }}</option>
                                            @else
                                                <option value="{{ $room->id }}">{{ $room->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="Type">Type*</label>
                                    <select name="Type" id="Type" class="form-control" data-current-item-type="{{ $currentItem->type }}">
                                        @foreach(config('systemconfig.EstimationItemType') as $key => $type)
                                            @if ($currentItem->type==$key)
                                                <option value="{{ $key }}" selected="selected">{{$key}} ({{$type}})</option>
                                            @else
                                                <option value="{{ $key }}">{{$key}} ({{$type}})</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4 @if ($currentItem->type == "QE") hidden @endif" id="QEItemsBlock">
                                <div class="form-group">
                                    <label for="QEItems">QE Items*</label>
                                    <select class="form-control" name="QEItems[]" id="QEItems" multiple="multiple">
                                        @foreach ($qeItems as $qeItem)
                                            @if ($currentQEItems->where("id", $qeItem->id)->count() == 1)
                                                <option value="{{ $qeItem->id }}" selected="selected">{{ $qeItem->description }}</option>
                                            @else
                                                <option value="{{ $qeItem->id }}">{{ $qeItem->description }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group mr-bt-0 mr-tp-20">
                                    <label for="Unit">Unit*</label>
                                    <select name="Unit" id="Unit" class="form-control">
                                        <option value="">Select</option>
                                        @foreach ($units as $unit)
                                            @if ($unit->id == $currentItem->unitId)
                                                <option value="{{ $unit->id }}" selected="selected">{{ $unit->name }}</option>
                                            @else
                                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mr-bt-0 mr-tp-20">
                                    <label for="Quantity">Default Quantity*</label>
                                    <input type="number" step="1" min="1" max="999" name="Quantity" id="Quantity" class="form-control" value="{{ $currentItem->quantity }}"/>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="Width">
                                        <span>Default Width*</span><br/>
                                        <small class="text-aqua no-text-transform">(in Feet)</small>
                                    </label>
                                    <input type="number" step="0.01" min="1.00" max="999" name="Width" id="Width" class="form-control" value="{{ $currentItem->width }}"/>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="Height">
                                        <span>Default Height/Length*</span><br/>
                                        <small class="text-aqua no-text-transform">(in Feet)</small>
                                    </label>
                                    <input type="number" step="0.01" min="1.00" max="999" name="Height" id="Height" class="form-control" value="{{ $currentItem->height }}"/>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="Depth">
                                        <span>Default Depth*</span><br/>
                                        <small class="text-aqua no-text-transform">(in Feet)</small>
                                    </label>
                                    <input type="number" step="0.01" min="1.00" max="999" name="Depth" id="Depth" class="form-control" value="{{ $currentItem->depth }}"/>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="RatecardItem">Ratecard Item*</label>
                                    <select name="RatecardItem" id="RatecardItem" class="form-control">
                                        <option value="">Select</option>
                                        @foreach ($ratecardItems as $ratecardItem)
                                            @if ($ratecardItem->id == $currentItem->ratecardItemId)
                                                <option value="{{ $ratecardItem->id }}" selected="selected">{{ $ratecardItem->name }}</option>
                                            @else
                                                <option value="{{ $ratecardItem->id }}">{{ $ratecardItem->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="Category">Category*</label>
                                    <select name="Category" id="Category" class="form-control">
                                        <option value="">Select</option>
                                        @foreach ($categories as $category)
                                            @if ($category->id == $currentItem->categoryId)
                                                <option value="{{ $category->id }}" selected="selected">{{ $category->name }}</option>
                                            @else
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="References">Reference Images</label>
                                    <input type="file" name="References[]" id="References" class="file-chooser" accept="image/*" multiple="multiple"/>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="Comments">Standard Comments</label>
                                    <select name="Comments[]" id="Comments" class="form-control" multiple="multiple" style="width:100%">
                                        @foreach ($comments as $comment)
                                            @if ($currentItemComments->contains("id", $comment->id))
                                                <option value="{{ $comment->id }}" selected="selected">{{ $comment->description }}</option>
                                            @else
                                                <option value="{{ $comment->id }}">{{ $comment->description }}</option>
                                            @endif
                                        @endforeach
                                        <option value="addnew">Add New Comment</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4 hidden">
                                <div class="form-group">
                                    <label for="NewComment">New Comment*</label>
                                    <input type="text" name="NewComment" id="NewComment" class="form-control" placeholder="Add New Comment"/>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="Notes">HECHPE Notes</label>
                                    <textarea name="Notes" id="Notes" class="form-control no-resize-input" rows="3" placeholder="Estimate Only. Actual cost depends on Home Owner selection.">{{ $currentItem->note }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="no-text-transform">Is quantity editable?</label>
                                    <div class="mr-tp-6">
                                        @if ($currentItem->isQuantityEditable())
                                            <input type="radio" name="QuantityEditable" id="QuantityEditableYes" value="Yes" checked="checked" class="input-radio"/>
                                        @else
                                            <input type="radio" name="QuantityEditable" id="QuantityEditableYes" value="Yes" class="input-radio"/>
                                        @endif
                                        <label for="QuantityEditableYes" tabindex="0"></label>
                                        <label for="QuantityEditableYes" class="text-normal cursor-pointer mr-rt-8">Yes</label>
                                        @if (! $currentItem->isQuantityEditable())
                                            <input type="radio" name="QuantityEditable" id="QuantityEditableNo" value="No" checked="checked" class="input-radio"/>
                                        @else
                                            <input type="radio" name="QuantityEditable" id="QuantityEditableNo" value="No" class="input-radio"/>
                                        @endif
                                        <label for="QuantityEditableNo" tabindex="0"></label>
                                        <label for="QuantityEditableNo" class="text-normal cursor-pointer mr-rt-8">No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3" style="width:19%">
                                <div class="form-group">
                                    <label class="no-text-transform">Are dimensions editable?</label>
                                    <div class="mr-tp-6">
                                        @if ($currentItem->areDimensionsEditable())
                                            <input type="radio" name="DimensionEditable" id="DimensionEditableYes" value="Yes" checked="checked" class="input-radio"/>
                                        @else
                                            <input type="radio" name="DimensionEditable" id="DimensionEditableYes" value="Yes" class="input-radio"/>
                                        @endif
                                        <label for="DimensionEditableYes" tabindex="0"></label>
                                        <label for="DimensionEditableYes" class="text-normal cursor-pointer mr-rt-8">Yes</label>
                                        @if (! $currentItem->areDimensionsEditable())
                                            <input type="radio" name="DimensionEditable" id="DimensionEditableNo" value="No" checked="checked" class="input-radio"/>
                                        @else
                                            <input type="radio" name="DimensionEditable" id="DimensionEditableNo" value="No" class="input-radio"/>
                                        @endif
                                        <label for="DimensionEditableNo" tabindex="0"></label>
                                        <label for="DimensionEditableNo" class="text-normal cursor-pointer mr-rt-8">No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="no-text-transform">Is preselected?</label>
                                    <div class="mr-tp-6">
                                        @if ($currentItem->isPreSelected())
                                            <input type="radio" name="Preselected" id="PreselectedYes" value="Yes" checked="checked" class="input-radio"/>
                                        @else
                                            <input type="radio" name="Preselected" id="PreselectedYes" value="Yes" class="input-radio"/>
                                        @endif
                                        <label for="PreselectedYes" tabindex="0"></label>
                                        <label for="PreselectedYes" class="text-normal cursor-pointer mr-rt-8">Yes</label>
                                        @if (! $currentItem->isPreSelected())
                                            <input type="radio" name="Preselected" id="PreselectedNo" value="No" checked="checked" class="input-radio"/>
                                        @else
                                            <input type="radio" name="Preselected" id="PreselectedNo" value="No" class="input-radio"/>
                                        @endif
                                        <label for="PreselectedNo" tabindex="0"></label>
                                        <label for="PreselectedNo" class="text-normal cursor-pointer mr-rt-8">No</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="MandatoryBlock" class="col-md-2 {{ ($currentItem->isPreSelected()) ? '': 'hidden' }}">
                                <div class="form-group">
                                    <label class="no-text-transform">Is mandatory?</label>
                                    <div class="mr-tp-6">
                                        @if (! $currentItem->isDeselectable())
                                            <input type="radio" name="Deselectable" id="DeselectableYes" value="Yes" checked="checked" class="input-radio"/>
                                        @else
                                            <input type="radio" name="Deselectable" id="DeselectableYes" value="Yes" class="input-radio"/>
                                        @endif
                                        <label for="DeselectableYes" tabindex="0"></label>
                                        <label for="DeselectableYes" class="text-normal cursor-pointer mr-rt-8">Yes</label>
                                        @if ($currentItem->isDeselectable())
                                            <input type="radio" name="Deselectable" id="DeselectableNo" value="No" checked="checked" class="input-radio"/>
                                        @else
                                            <input type="radio" name="Deselectable" id="DeselectableNo" value="No" class="input-radio"/>
                                        @endif
                                        <label for="DeselectableNo" tabindex="0"></label>
                                        <label for="DeselectableNo" class="text-normal cursor-pointer mr-rt-8">No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="no-text-transform">Payment by Customer to</label>
                                    <div class="mr-tp-6">
                                        @if ($currentItem->isPaidByCompany($paymentBy))
                                            <input type="radio" name="PaymentBy" id="PaymentByCompany" value="Company" checked="checked" class="input-radio"/>
                                        @else
                                            <input type="radio" name="PaymentBy" id="PaymentByCompany" value="Company" class="input-radio"/>
                                        @endif
                                        <label for="PaymentByCompany" tabindex="0"></label>
                                        <label for="PaymentByCompany" class="text-normal cursor-pointer mr-rt-8">HECHPE</label>
                                        @if ($currentItem->isPaidByCustomer($paymentBy))
                                            <input type="radio" name="PaymentBy" id="PaymentByCustomer" value="Customer" checked="checked" class="input-radio"/>
                                        @else
                                            <input type="radio" name="PaymentBy" id="PaymentByCustomer" value="Customer" class="input-radio"/>
                                        @endif
                                        <label for="PaymentByCustomer" tabindex="0"></label>
                                        <label for="PaymentByCustomer" class="text-normal cursor-pointer mr-rt-8">Third Party Vendor</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">                            
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="no-text-transform">Is design required?</label>
                                    <div class="mr-tp-6">
                                        @if ($currentItem->isDesignRequired())
                                            <input type="radio" name="IsDesignRequired" id="IsDesignRequiredYes" value="Yes" checked="checked" class="input-radio"/>
                                        @else
                                            <input type="radio" name="IsDesignRequired" id="IsDesignRequiredYes" value="Yes" class="input-radio"/>
                                        @endif
                                        <label for="IsDesignRequiredYes" tabindex="0"></label>
                                        <label for="IsDesignRequiredYes" class="text-normal cursor-pointer mr-rt-8">Yes</label>
                                        @if (! $currentItem->isDesignRequired())
                                            <input type="radio" name="IsDesignRequired" id="IsDesignRequiredNo" value="No" checked="checked" class="input-radio"/>
                                        @else
                                            <input type="radio" name="IsDesignRequired" id="IsDesignRequiredNo" value="No" class="input-radio"/>
                                        @endif
                                        <label for="IsDesignRequiredNo" tabindex="0"></label>
                                        <label for="IsDesignRequiredNo" class="text-normal cursor-pointer mr-rt-8">No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3" style="width:19%;">
                                <div class="form-group">
                                    <label class="no-text-transform">Is selection required?</label>
                                    <div class="mr-tp-6">
                                        @if ($currentItem->isSelectionRequired())
                                            <input type="radio" name="IsSelectionRequired" id="IsSelectionRequiredYes" value="Yes" checked="checked" class="input-radio"/>
                                        @else
                                            <input type="radio" name="IsSelectionRequired" id="IsSelectionRequiredYes" value="Yes" class="input-radio"/>
                                        @endif
                                        <label for="IsSelectionRequiredYes" tabindex="0"></label>
                                        <label for="IsSelectionRequiredYes" class="text-normal cursor-pointer mr-rt-8">Yes</label>
                                        @if (! $currentItem->isSelectionRequired())
                                            <input type="radio" name="IsSelectionRequired" id="IsSelectionRequiredNo" value="No" checked="checked" class="input-radio"/>
                                        @else
                                            <input type="radio" name="IsSelectionRequired" id="IsSelectionRequiredNo" value="No" class="input-radio"/>
                                        @endif
                                        <label for="IsSelectionRequiredNo" tabindex="0"></label>
                                        <label for="IsSelectionRequiredNo" class="text-normal cursor-pointer mr-rt-8">No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="no-text-transform">Is payment in cash?</label>
                                    <div class="mr-tp-6">
                                        @if ($currentItem->isPaymentInCash())
                                            <input type="radio" name="IsPaymentInCash" id="IsPaymentInCashYes" value="Yes" checked="checked" class="input-radio"/>
                                        @else
                                            <input type="radio" name="IsPaymentInCash" id="IsPaymentInCashYes" value="Yes" class="input-radio"/>
                                        @endif
                                        <label for="IsPaymentInCashYes" tabindex="0"></label>
                                        <label for="IsPaymentInCashYes" class="text-normal cursor-pointer mr-rt-8">Yes</label>
                                        @if (! $currentItem->IsPaymentInCash())
                                            <input type="radio" name="IsPaymentInCash" id="IsPaymentInCashNo" value="No" checked="checked" class="input-radio"/>
                                        @else
                                            <input type="radio" name="IsPaymentInCash" id="IsPaymentInCashNo" value="No" class="input-radio"/>
                                        @endif
                                        <label for="IsPaymentInCashNo" tabindex="0"></label>
                                        <label for="IsPaymentInCashNo" class="text-normal cursor-pointer mr-rt-8">No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="no-text-transform">Is Warranty Provided?</label>
                                    <div class="mr-tp-6">
                                        @if ($currentItem->isWarrantyProvided())
                                            <input type="radio" name="IsWarrantyProvided" id="IsWarrantyProvidedYes" value="Yes" checked="checked" class="input-radio"/>
                                        @else
                                            <input type="radio" name="IsWarrantyProvided" id="IsWarrantyProvidedYes" value="Yes" class="input-radio"/>
                                        @endif
                                        <label for="IsWarrantyProvidedYes" tabindex="0"></label>
                                        <label for="IsWarrantyProvidedYes" class="text-normal cursor-pointer mr-rt-8">Yes</label>
                                        @if (! $currentItem->isWarrantyProvided())
                                            <input type="radio" name="IsWarrantyProvided" id="IsWarrantyProvidedNo" value="No" checked="checked" class="input-radio"/>
                                        @else
                                            <input type="radio" name="IsWarrantyProvided" id="IsWarrantyProvidedNo" value="No" class="input-radio"/>
                                        @endif
                                        <label for="IsWarrantyProvidedNo" tabindex="0"></label>
                                        <label for="IsWarrantyProvidedNo" class="text-normal cursor-pointer mr-rt-8">No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="no-text-transform">Is Bill Provided?</label>
                                    <div class="mr-tp-6">
                                        @if ($currentItem->isBillProvided())
                                            <input type="radio" name="IsBillProvided" id="IsBillProvidedYes" value="Yes" checked="checked" class="input-radio"/>
                                        @else
                                            <input type="radio" name="IsBillProvided" id="IsBillProvidedYes" value="Yes" class="input-radio"/>
                                        @endif
                                        <label for="IsBillProvidedYes" tabindex="0"></label>
                                        <label for="IsBillProvidedYes" class="text-normal cursor-pointer mr-rt-8">Yes</label>
                                        @if (! $currentItem->isBillProvided())
                                            <input type="radio" name="IsBillProvided" id="IsBillProvidedNo" value="No" checked="checked" class="input-radio"/>
                                        @else
                                            <input type="radio" name="IsBillProvided" id="IsBillProvidedNo" value="No" class="input-radio"/>
                                        @endif
                                        <label for="IsBillProvidedNo" tabindex="0"></label>
                                        <label for="IsBillProvidedNo" class="text-normal cursor-pointer mr-rt-8">No</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="no-text-transform">Status</label>
                                    <div class="mr-tp-6">
                                        @if ($currentItem->isActive())
                                            <input type="radio" name="Status" id="StatusActive" value="Active" checked="checked" class="input-radio"/>
                                        @else
                                            <input type="radio" name="Status" id="StatusActive" value="Active" class="input-radio"/>
                                        @endif
                                        <label for="StatusActive" tabindex="0"></label>
                                        <label for="StatusActive" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                        @if (! $currentItem->isActive())
                                            <input type="radio" name="Status" id="StatusInactive" value="InActive" checked="checked" class="input-radio"/>
                                        @else
                                            <input type="radio" name="Status" id="StatusInactive" value="InActive" class="input-radio"/>
                                        @endif
                                        <label for="StatusInactive" tabindex="0"></label>
                                        <label for="StatusInactive" class="text-normal cursor-pointer mr-rt-8">Inactive</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <p class="mr-tp-12 mr-bt-15">
                                    <input type="submit" name="UpdateItemFormSubmit" value="Update" class="btn btn-primary button-custom" id="UpdateItemFormSubmit"/>
                                    <input type="reset" name="UpdateItemFormReset" value="Undo Changes" class="btn button-custom" id="UpdateItemFormReset"/>
                                </p>
                            </div>
                        </div>
                    </form>
                    <div id="UpdateItemFormOverlay" class="overlay hidden">
                        <div class="large loader"></div>
                        <div class="loader-text">Updating Item...</div>
                    </div>
                </div>
                <div id="UpdateItemFormNotificationArea" class="notification-area hidden">
                    <div class="alert alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <p class="body"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@include('notificationOverlay')
@endsection

@section("dynamicStyles")
    <link rel="stylesheet" href="{{ asset("css/vendor/select2.min.css") }}">
@endsection

@section("dynamicScripts")
    <script src="{{ asset("js/vendor/select2.min.js") }}"></script>
    <script src="{{ asset("js/common.js") }}"></script>
    <script src="{{ URL::assetUrl("/js/NotificationOverlay.js") }}"></script>
    <script src="{{ asset("js/qeitems/edit.min.js") }}"></script>
@endsection
