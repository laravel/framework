<tbody id="CreateQuickEstimateTableBody">
    <template v-for="room in roomItems">
        <tr class="bg-info text-center room-items">
            <td width="4%"></td>
            <td width="28%" class="rooms">
                <span class="pull-left pd-lt-10">@{{ room.name }}</span>
            </td>
            <td width="28%" colspan="6">
                <span class="pull-right mr-rt-10">Section Subtotal &nbsp;
                    <i class="fa fa-arrow-right smallarrow" aria-hidden="true"></i>
                </span>
            </td>
            @foreach ($pricePackages as $index => $pricePackage)
                <td width="10%" class="{{ $pricePackage->class }}">
                    <i class="fa fa-rupee"></i>
                    <span>{{ $pricePackage->roomwiseTotalsVueString($index) }}</span>
                </td>
            @endforeach
            <td width="10%"></td>
        </tr>
        @verbatim
            <tr v-for="(item, index) in room.items">
                <td class="text-center text-vertical-align items-index" width="4%">{{ index + 1 }}</td>
                <td class="text-vertical-align" width="28%">
                    {{ item.description }} ({{ getUnitName(item.unitId) }})
                    <span v-if="item.comments.length > 0">
                        <span class="text-aqua comments-tooltip pd-lt-5" :data-comments="JSON.stringify(item.comments)">
                            <i class="fa fa-info-circle text-light-blue" aria-hidden="true"></i>
                        </span>
                    </span>
                    <span class="text-danger notes-tooltip pd-lt-5" v-if="item.notes.length > 0" :title="item.notes">
                        <i class="fa fa-exclamation-triangle text-black" aria-hidden="true"></i>
                    </span>
                    <span v-if="item.images.length > 0">
                        <span class="text-danger cursor-pointer reference-images pd-lt-5" v-for="image in item.images" :data-url="image">
                            <i class="fa fa-image text-black" aria-hidden="true"></i>
                        </span>
                    </span>
                </td>
                <td class="text-center text-vertical-align" width="4%">
                    <span class="payment-tooltip cursor-pointer" v-if="! _.isNull(item.paymentBy)" :title="item.paymentBy.description">
                        <img :src="item.paymentBy.image" alt="item.paymentBy.shortcode"/>
                    </span>
                </td>
                <td class="text-center text-vertical-align" width="4%">
                    <input
                        type="checkbox"
                        :name="createName(item.id, room.id, 'Required')"
                        :id="createName(item.id, room.id, 'Required')"
                        class="checkbox"
                        :checked="item.isPreselected"
                        :disabled="item.isPreselected && ! item.isDeselectable"
                        :data-room-id="room.id"
                        v-model="item.isSelected"
                    />
                    <label :for="createName(item.id, room.id, 'Required')" class="mr-0"></label>
                </td>
                <td class="text-center text-vertical-align pd-lt-3 pd-rt-3" width="5%">
                    <input
                        type="text"
                        :name="createName(item.id, room.id, 'Quantity')"
                        :id="createName(item.id, room.id, 'Quantity')"
                        class="form-control input-sm text-center pd-lt-0 pd-rt-0"
                        :disabled="! item.isQuantityEditable"
                        :data-room-id="room.id"
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
                        :name="createName(item.id, room.id, 'Width')"
                        :id="createName(item.id, room.id, 'Width')"
                        class="form-control input-sm text-center pd-lt-0 pd-rt-0"
                        :disabled="! item.areDimensionsEditable"
                        :data-room-id="room.id"
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
                        :name="createName(item.id, room.id, 'Height')"
                        :id="createName(item.id, room.id, 'Height')"
                        class="form-control input-sm text-center pd-lt-0 pd-rt-0"
                        :disabled="! item.areDimensionsEditable"
                        :data-room-id="room.id"
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
                        :name="createName(item.id, room.id, 'Notes')"
                        :id="createName(item.id, room.id, 'Notes')"
                        class="form-control input-sm user-notes"
                        placeholder="Notes"
                        style="resize:none"
                        v-model="item.customerNotes"
                    ></textarea>
                </td>
            </tr>
        @endverbatim
    </template>
</tbody>
