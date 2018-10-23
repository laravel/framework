<div class="box-header with-border">
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="Name">Estimation Name*</label>
                <input type="text" name="Name" id="Name" class="form-control" placeholder="Estimation for Aparna Heights Flat in Gachibowli" v-model="estimationName" autocomplete="off"/>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="Enquiry" id="EnquiryInformation" class="text-blue text-hover" data-address='@json($address)'>Enquiry</label>
                <select name="Enquiry" id="Enquiry" class="form-control" disabled="disabled">
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
        <div class="col-md-5">
            <div class="form-group">
                <label>Rooms*</label>
                <div class="form-control-static">
                    <span class="rooms-count" v-if="selectedRooms.length == 0">No rooms selected.</span>
                    <span class="rooms-count" v-else-if="selectedRooms.length == 1">1 room selected.</span>
                    <span class="rooms-count" v-else>@{{ selectedRooms.length }} rooms selected.</span>
                    <span class="pd-lt-8 text-blue text-hover" id="ChangeRooms" :data-content="selectedRoomsNames">Change</span>
                </div>
            </div>
        </div>
    </div>
</div>
