<tfoot>
    <tr class="bg-primary bg-blue" style="page-break-inside:avoid!important;">
        <th colspan="5" class="text-right text-vertical-align" width="45%">
            <span class="pull-right mr-rt-10">Total</span>
        </th>
        @foreach ($estimate->totals as $total)
            <th class="text-center text-vertical-align amount-text {{ $total->class }}" width="10%">
                <span class="text-bold">&#8377; {{ $total->amount() }}</span>
                <div class="text-bold">{{ $total->name }}</div>
            </th>
        @endforeach
        <th width="25%"></th>
    </tr>
</tfoot>
