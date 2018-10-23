<thead>
    <tr>
        <td colspan="5" class="bg-white text-normal" width="55%" style="vertical-align:bottom">
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
        @foreach ($estimate->totals as $total)
            <td class="text-center text-vertical-align amount-text {{ $total->class }}" width="10%">
                <i class="fa fa-rupee"></i>
                <span class="text-bold">{{ $total->amount() }}</span>
                <div class="text-bold">{{ $total->name }}</div>
            </td>
        @endforeach
        <th class="text-center bg-white text-vertical-align speciciations" width="15%">
            <a href="#" class="item-specifications">Specifications</a><br/>
            <a href="#" class="item-ratecards">Ratecards</a>
        </th>
    </tr>
</thead>
