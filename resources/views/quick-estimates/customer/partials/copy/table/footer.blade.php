<tfoot>
    <tr class="bg-blue">
        <th colspan="8" class="text-right text-vertical-align" width="60%">
            <span class="pull-right mr-rt-10">Total</span>
        </th>
        @foreach ($pricePackages as $index => $pricePackage)
            <th class="text-center text-vertical-align amount-text {{ $pricePackage->class }}" width="10%">
                <i class="fa fa-rupee"></i>
                <span class="branded-sum-amount">{{ $pricePackage->totalsVueString($index) }}</span>
                <div>{{ $pricePackage->name }}</div>
            </th>
        @endforeach
        <th width="10%"></th>
    </tr>
</tfoot>
