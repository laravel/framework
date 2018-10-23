<tfoot>
    <tr class="bg-blue">
        <th colspan="5" class="text-right text-vertical-align" width="55%">
            <span class="pull-right mr-rt-10">Total</span>
        </th>
        @foreach ($estimate->totals as $total)
            <th class="text-center text-vertical-align amount-text {{ $total->class }}" width="10%">
                <i class="fa fa-rupee"></i>
                <span class="text-bold">{{ $total->amount() }}</span>
                <div class="text-bold">{{ $total->name }}</div>
            </th>
        @endforeach
        <th width="15%"></th>
    </tr>
</tfoot>
