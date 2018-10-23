<tr class="bg-info text-center room-items">
    <td width="4%"></td>
    <td width="30%" class="rooms">
        <span class="pull-left pd-lt-10">Custom Items</span>
    </td>
    <td width="26%" colspan="6">
        <span class="pull-right mr-rt-10">Section Subtotal &nbsp;
            <i class="fa fa-arrow-right smallarrow" aria-hidden="true"></i>
        </span>
    </td>
    @foreach ($pricePackages as $index => $pricePackage)
        <td width="10%" class="{{ $pricePackage->class }}">
            <i class="fa fa-rupee"></i>
            <span>{{ $pricePackage->customItemsTotalsVueString($index) }}</span>
        </td>
    @endforeach
    <td width="10%"></td>
</tr>
@verbatim
    <tr v-for="(item, index) in customItems">
        <td class="text-center text-vertical-align items-index" width="4%">{{ index + 1 }}</td>
        <td class="text-vertical-align" width="30%">
            {{ _.find(rooms, ["id", item.roomId]).name }}: {{ item.description }} ({{ getUnitName(item.unitId) }})
            <span :id="createName(item.id, 'CustomItem', 'Image')" class="text-hover pd-lt-5" :data-session-storage-id="item.id" v-if="_.isPlainObject(item.image)">
                <i class="fa fa-image text-black" aria-hidden="true"></i>
            </span>
        </td>
        <td class="text-center text-vertical-align" width="3%">
            <span class="custom-item-tooltips cursor-pointer" v-if="_.isPlainObject(item.paymentBy)" :id="createName(item.id, 'CustomItem', 'PaymentBy')">
                <img :src="item.paymentBy.image" :alt="item.paymentBy.shortcode"/>
            </span>
        </td>
        <td class="text-center text-vertical-align" width="3%">
            <input
                type="checkbox"
                :name="createName(item.id, 'CustomItem', 'Required')"
                :id="createName(item.id, 'CustomItem', 'Required')"
                class="checkbox"
                v-model="item.isSelected"
            />
            <label :for="createName(item.id, 'CustomItem', 'Required')" class="mr-0"></label>
        </td>
        <td class="text-center text-vertical-align pd-lt-3 pd-rt-3" width="5%">
            <input
                type="text"
                :name="createName(item.id, 'CustomItem', 'Quantity')"
                :id="createName(item.id, 'CustomItem', 'Quantity')"
                class="form-control input-sm text-center pd-lt-0 pd-rt-0"
                v-model="item.quantity"
                @keydown.up.prevent="item.quantity++"
                @keydown.down.prevent="decrementQuantity(item)"
                @input="checkQuantity(item)"
                autocomplete="off"
            />
        </td>
        <td class="text-center text-vertical-align pd-lt-3 pd-rt-3" width="5%">
            <input
                type="text"
                :name="createName(item.id, 'CustomItem', 'Width')"
                :id="createName(item.id, 'CustomItem', 'Width')"
                class="form-control input-sm text-center pd-lt-0 pd-rt-0"
                v-model="item.width"
                @keydown.up.prevent="item.width++"
                @keydown.down.prevent="decrementWidth(item)"
                @input="checkWidth(item)"
                autocomplete="off"
            />
        </td>
        <td class="text-center text-vertical-align pd-lt-3 pd-rt-3" width="5%">
            <input
                type="text"
                :name="createName(item.id, 'CustomItem', 'Height')"
                :id="createName(item.id, 'CustomItem', 'Height')"
                class="form-control input-sm text-center pd-lt-0 pd-rt-0"
                v-model="item.height"
                @keydown.up.prevent="item.height++"
                @keydown.down.prevent="decrementHeight(item)"
                @input="checkHeight(item)"
                autocomplete="off"
            />
        </td>
        <td class="text-center text-vertical-align" width="5%">{{ item.depth }}</td>
        <template v-for="pricePackage in item.pricePackages">
            <td class="text-center text-vertical-align item-rates" width="10%">
                <span v-if="item.isSelected">
                    {{ item.quantity * item.width * item.height * pricePackage.customerRate }}
                </span>
                <span v-else="item.isSelected">0</span>
            </td>
        </template>
        <td class="text-center text-vertical-align" width="10%">
            <textarea
                rows="1"
                :name="createName(item.id, 'CustomItem', 'Notes')"
                :id="createName(item.id, 'CustomItem', 'Notes')"
                class="form-control input-sm user-notes"
                placeholder="Notes"
                style="resize:none"
                v-model="item.notes"
            ></textarea>
        </td>
    </tr>
@endverbatim
