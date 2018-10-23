<thead>
    <tr>
        <td colspan="8" class="bg-white" width="60%">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="StickyHeaderName">Estimation Name*</label>
                        <input type="text" name="StickyHeaderName" id="StickyHeaderName" class="form-control" placeholder="Estimation for Aparna Heights Flat in Gachibowli" v-model="estimationName" autocomplete="off"/>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="StickyHeaderEnquiry" id="StickyHeaderEnquiryInformation" class="text-blue text-hover" data-address='@json($address)'>Enquiry</label>
                        <select name="StickyHeaderEnquiry" id="StickyHeaderEnquiry" class="form-control" disabled="disabled">
                            @foreach ($enquiries as $enquiry)
                                @if ($currentEnquiryId == $enquiry->id)
                                    <option value="{{ $enquiry->id }}" selected="selected">{{ $enquiry->reference }} ({{ $enquiry->name }})</option>
                                @else
                                    <option value="{{ $enquiry->id }}">{{ $enquiry->reference }} ({{ $enquiry->name }})</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Rooms*</label>
                        <div class="form-control-static">
                            <span class="rooms-count" v-if="selectedRooms.length == 0">No rooms selected.</span>
                            <span class="rooms-count" v-else-if="selectedRooms.length == 1">1 room selected.</span>
                            <span class="rooms-count" v-else>@{{ selectedRooms.length }} rooms selected.</span>
                            <span class="pd-lt-8 text-blue text-hover" id="StickyHeaderChangeRooms" :data-content="selectedRoomsNames">Change</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <i class="fa fa-info-circle text-light-blue" aria-hidden="true"></i>
                    <i class="fa fa-exclamation-triangle text-black" aria-hidden="true"></i>
                    <span class="text-center no-text-transform">
                        <span>Indicates comments |</span>
                        <i class="fa fa-image text-black" aria-hidden="true"></i>
                        <span class="no-text-transform">Indicates reference images |</span>
                        <span>All dimensions in feet |</span>
                        <span>All amount in Indian Rupees ( <i class="fa fa-rupee"></i> )</span>
                    </span>
                </div>
            </div>
        </td>
        @foreach ($pricePackages as $index => $pricePackage)
            <td class="text-center text-vertical-align amount-text {{ $pricePackage->class }}" width="10%">
                <i class="fa fa-rupee"></i>
                <span class="text-bold">{{ $pricePackage->totalsVueString($index) }}</span>
                <div class="text-bold">{{ $pricePackage->name }}</div>
            </td>
        @endforeach
        <th class="text-center bg-white text-vertical-align speciciations" width="10%">
            <a href="#" class="item-specifications">Specifications</a>
            <a href="#" class="item-ratecards">Ratecards</a>
        </th>
    </tr>
</thead>
