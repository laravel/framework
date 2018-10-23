<thead>
    <tr>
        <th colspan="5" class="text-normal" width="55%">
            <i class="fa fa-info-circle text-light-blue" aria-hidden="true"></i>
            <i class="fa fa-exclamation-triangle text-black" aria-hidden="true"></i>
            <span class="text-center no-text-transform">
                <span>Indicates comments |</span>
                <i class="fa fa-image text-black" aria-hidden="true"></i>
                <span class="no-text-transform">Indicates reference images |</span>
                <span>All dimensions in feet |</span>
                <span>All amount in Indian Rupees ( <i class="fa fa-rupee"></i> )</span>
            </span>
        </th>
        @foreach ($estimate->totals as $total)
            <th class="text-center text-vertical-align amount-text {{ $total->class }}" width="10%">
                <i class="fa fa-rupee"></i>
                <span class="text-bold">{{ $total->amount() }}</span>
                <div class="text-bold">{{ $total->name }}</div>
            </th>
        @endforeach
        <th class="text-center bg-white text-vertical-align speciciations" width="15%">
            <a href="#" class="item-specifications">Specifications</a><br/>
            <a href="#" class="item-ratecards">Ratecards</a>
        </th>
    </tr>
    <tr class="bg-blue text-center">
        <th class="text-center" width="5%">#</th>
        <th class="text-center" width="30%">Items (Units)</th>
        <th class="text-center" width="5%">
            <span class="header-tooltip" title="Who is going to pay whom?">Pay</span>
        </th>
        <th class="text-center cursor-help" width="5%">
            <span class="header-tooltip" title="Required Quantity, if applicable">Nos</span>
        </th>
        <th class="text-center cursor-help" width="10%">
            <span class="header-tooltip no-text-transform" title="Dimensions of the Item, if applicable">W x H/L x D</span>
        </th>
        @foreach ($estimate->totals as $total)
            <th class="text-center {{ $total->class }}" width="10%">Amount</th>
        @endforeach
        <th class="text-center" width="15%">Notes</th>
    </tr>
</thead>
