/**
 * QuickEstimation class for managing application using "vue.js".
 *
 * Fetches complete item and prices data and calculatesitem prices,
 * room wise prices, total prices and normalizes actual post data.
 */
class QuickEstimation
{
    /**
     * Get notifier for QuickEstimation.
     *
     * @var \Core\Notifier|undefined
     */
    notifier = undefined;

    /**
     * Get translator for QuickEstimation.
     *
     * @var \Core\Translator|undefined
     */
    translator = undefined;

    /**
     * Get form requisites for QuickEstimation.
     *
     * @var \Core\FormRequisites|undefined
     */
    formRequisites = undefined;

    /**
     * Create a new instance of QuickEstimation.
     *
     * @param  \Core\Notifier notifier
     * @param  \Core\Translator  translator
     * @param  \Core\FormRequisites  formRequisites
     * @return void
     */
    constructor(notifier, translator, formRequisites)
    {
        this.notifier = notifier;
        this.translator = translator;
        this.formRequisites = formRequisites;
    }

    /**
     * Initialize estimate page components.
     *
     * @return void
     */
    execute()
    {
        // Reference to current class.
        let that = this;

        // Create a new Vue instance on the application.
        return new Vue({
            /**
             * The root DOM element that the Vue instance is managing.
             *
             * @var Selector
             */
            el: "#CopyQuickEstimateApplication",

            /**
             * The data object that the Vue instance is observing.
             *
             * @var Object
             */
            data: {
                rooms: [],
                roomItems: [],
                customItems: [],
                selectedRooms: [],
                defaultRooms: [],
                tempSelectedRooms: [],
                estimationName: "",
                pricePackages: [],
                paymentByOptions: [],
                units: [],
                // Keep these temp object for the backup, incase if you want to reset
                // the data before submit we can always go to original object and reset.
                // Reset? Why because we are changing these arrays during the creation
                // of quick estimate before submit. Actually they mutate (change),
                // so back them up and reset using original object data.
                original: {
                    rooms: [],
                    roomItems: [],
                    customItems: [],
                    selectedRooms: [],
                    defaultRooms: [],
                },
            },

            /**
             * Computed properties to be mixed into the Vue instance.
             *
             * @var Object
             */
            computed: {
                /**
                 * Get selected room names concatenated with a comma.
                 *
                 * @return String
                 */
                selectedRoomsNames()
                {
                    let rooms = [];
                    // Filter out selected room objects.
                    _.forEach(this.rooms, (room) => {
                        if (this.selectedRooms.indexOf(room.id) != -1) {
                            rooms.push(room.name);
                        }
                    });

                    // Concat array with a comma.
                    return rooms.join(", ");
                },

                /**
                 * Decide whether to show no items warning alert.
                 *
                 * @return Boolean
                 */
                showNoItemsWarning()
                {
                    let notSelected = true;
                    // Check whether atleast one item is selected.
                    _.forEach(this.roomItems, (room) => {
                        _.forEach(room.items, (item) => {
                            // If selected return "false" to break the loop explictly.
                            if (item.isSelected == true) {
                                return notSelected = false;
                            }
                        });
                        // If selected return "false" to break the loop explictly.
                        if (notSelected == false) {
                            return false;
                        }
                    });

                    // If no item is selected, trigger "qe.items.not-selected" event.
                    if (notSelected) {
                        $("#CopyQuickEstimateTable").trigger("qe.items.not-selected");
                    }

                    return notSelected;
                },
            },

            /**
             * Methods to be mixed into the Vue instance.
             *
             * @var Object
             */
            methods: {
                /**
                 * Bootstrap the application by setting data and event triggers.
                 *
                 * @param  Object  response
                 * @return void
                 */
                bootstrap(response)
                {
                    // Set rooms and items on vue instance.
                    this.rooms = response.rooms;
                    this.roomItems = response.roomItems;
                    this.customItems = response.customItems;
                    this.selectedRooms = response.selectedRooms;
                    this.defaultRooms = response.defaultRooms;
                    this.tempSelectedRooms = response.selectedRooms;
                    this.pricePackages = response.pricePackages;
                    this.paymentByOptions = response.paymentByOptions;
                    this.units = response.units;

                    // Backup the data into the original property. Use "deep clone"
                    // to seperate out original data from actual mutuating data.
                    // Why? because objects in javascript are "shared by reference".
                    this.original.rooms = _.cloneDeep(response.rooms);
                    this.original.roomItems = _.cloneDeep(response.roomItems);
                    this.original.customItems = _.cloneDeep(response.customItems);
                    this.original.selectedRooms = _.cloneDeep(response.selectedRooms);
                    this.original.defaultRooms = _.cloneDeep(response.defaultRooms);

                    // Code that will run only after the entire view has been re-rendered.
                    // Trigger an event to notify application that items data is ready.
                    this.$nextTick(() => {
                        $("#CopyQuickEstimateTable").trigger("qe.items.ready");
                        $("#Name").trigger("focus");
                        this.initializeCustomItemTooltips(response.customItems);
                        this.initializeCustomItemPopovers(response.customItems);
                    });
                },

                /**
                 * Initialize custom item tooltips.
                 *
                 * @param  Array  customItems
                 * @return void
                 */
                initializeCustomItemTooltips(customItems)
                {
                    _.forEach(customItems, (customItem) => {
                        $(`#${customItem.id}-CustomItem-PaymentBy`).tooltip({
                            "container": "body",
                            "placement": "top",
                            "title": customItem.paymentBy.description,
                        });
                    });
                },

                /**
                 * Initialize image popover of the custom item.
                 *
                 * @param  Array  customItems
                 * @return void
                 */
                initializeCustomItemPopovers(customItems)
                {
                    _.forEach(customItems, (customItem) => {
                        $(`#${customItem.id}-CustomItem-Image`).popover({
                            "container": "body",
                            "content": this.getReferenceImagePopoverContent(customItem.image.url),
                            "html": true,
                            "placement": "right",
                            "trigger": "hover",
                        });
                    });
                },

                /**
                 * Get reference image popover html content.
                 *
                 * @param  String  url
                 * @return String
                 */
                getReferenceImagePopoverContent(url)
                {
                    return `<img src="${url}" alt="Reference image" class="img-responsive"/>`;
                },

                /**
                 * Fetch quick estimation items.
                 *
                 * @return void
                 */
                fetchQuickEstimationItems()
                {
                    // Show loading overlay.
                    that.formRequisites.prepareFormForAjax("#CopyQuickEstimateFormOverlay", "Fetching Quick Estimation Items");
                    // Perform ajax call to fetch items.
                    $.ajax({
                        url: $("#CopyQuickEstimateForm").data("bootstrap-url"),
                        type: "GET",
                        dataType: "json",
                    })
                    .done(this.bootstrap)
                    .fail(() => {
                        // Notifiy user of ajax request failure.
                        that.notifier.notify("#CopyQuickEstimateFormNotificationArea", {
                            "status": "error",
                            "message": that.translator.trans("system.failure"),
                        });
                    })
                    .always(() => {
                        // Close the overlay on completion of request.
                        that.formRequisites.hideOverlay("#CopyQuickEstimateFormOverlay");
                    });
                },

                /**
                 * Get selected rooms list.
                 *
                 * @return Array
                 */
                selectedRoomsList()
                {
                    let rooms = [];
                    // Filter out selected room objects.
                    _.forEach(this.roomItems, (room) => {
                        rooms.push(room.name);
                    });

                    return rooms;
                },

                /**
                 * Get selected prices list.
                 *
                 * @param  Integer  index
                 * @return Array
                 */
                selectedRoomsPrices(index)
                {
                    let prices = [];
                    // Filter out selected room objects.
                    _.forEach(this.roomItems, (room) => {
                        prices.push(this.roomwiseItemsTotal(room.id, index));
                    });

                    return prices;
                },

                /**
                 * Get unit name from list of units.
                 *
                 * @param  String  id
                 * @return String
                 */
                getUnitName(id)
                {
                    return _.find(this.units, ["id", id]).name;
                },

                /**
                 * Make a unique checkbox name based on given itemid and roomid.
                 *
                 * @param  String  itemId
                 * @param  String  roomId
                 * @param  String  name
                 * @return String
                 */
                createName(itemId, roomId, name = "Required")
                {
                    return `${itemId}-${roomId}-${name}`;
                },

                /**
                 * Make a unique checkbox name based on given roomid.
                 *
                 * @param  String  roomId
                 * @return String
                 */
                createUpdateRoomName(roomId)
                {
                    return `${roomId}-Checkbox`;
                },

                /**
                 * Decrement given item quantity by 1.
                 *
                 * @param  Object  item
                 * @return void
                 */
                decrementQuantity(item)
                {
                    let quantity = item.quantity;
                    // If current quantity is less than 1, then make its value "1".
                    if ((quantity - 1) < 1) {
                        item.quantity = 1;
                    } else {
                        // Or else decrement its quantity by "1".
                        item.quantity--;
                    }
                },

                /**
                 * Decrement given item width by 1.
                 *
                 * @param  Object  item
                 * @return void
                 */
                decrementWidth(item)
                {
                    let width = item.width;
                    // If current width is less than 1, then make its value "1".
                    if ((width - 1) < 1) {
                        item.width = 1;
                    } else {
                        // Or else decrement its width by "1".
                        item.width--;
                    }
                },

                /**
                 * Decrement given item height by 1.
                 *
                 * @param  Object  item
                 * @return void
                 */
                decrementHeight(item)
                {
                    let height = item.height;
                    // If current height is less than 1, then make its value "1".
                    if ((height - 1) < 1) {
                        item.height = 1;
                    } else {
                        // Or else decrement its height by "1".
                        item.height--;
                    }
                },

                /**
                 * Check item quantity and restrict its value.
                 *
                 * @param  Object  item
                 * @return void
                 */
                checkQuantity(item)
                {
                    let quantity = item.quantity;
                    // If item's quantity is less than 1, then make its value "1".
                    if (quantity < 1) {
                        item.quantity = 1;
                    }
                },

                /**
                 * Check item width and restrict its value.
                 *
                 * @param  Object  item
                 * @return void
                 */
                checkWidth(item)
                {
                    let width = item.width;
                    // If item's width is less than 1, then make its value "1".
                    if (width < 1) {
                        item.width = 1;
                    }
                },

                /**
                 * Check item height and restrict its value.
                 *
                 * @param  Object  item
                 * @return void
                 */
                checkHeight(item)
                {
                    let height = item.height;
                    // If item's height is less than 1, then make its value "1".
                    if (height < 1) {
                        item.height = 1;
                    }
                },

                /**
                 * Calcualte room-wise selected items total.
                 *
                 * @param  String  roomId
                 * @param  Integer  index
                 * @return Integer
                 */
                roomwiseItemsTotal(roomId, index)
                {
                    // Filter out all items of the given room id. 
                    let items = _.find(this.roomItems, ["id", roomId]).items, total = 0;
                    // Calculate selected items price.
                    _.forEach(items, (item) => {
                        if (item.isSelected) {
                            // Call helper method on "QuickEstimate" class to calculate price.
                            total += that.calculate(
                                item.quantity,
                                item.width,
                                item.height,
                                item.pricePackages[index].customerRate
                            );
                        }
                    });

                    // Return items total.
                    return total;
                },

                /**
                 * Calcualte selected custom items total.
                 *
                 * @param  Integer  index
                 * @return Integer
                 */
                customItemsTotal(index)
                {
                    let total = 0;
                    // Calculate selected custom items price.
                    _.forEach(this.customItems, (item) => {
                        if (item.isSelected) {
                            // Call helper method on "QuickEstimate" class to calculate price.
                            total += that.calculate(
                                item.quantity,
                                item.width,
                                item.height,
                                item.pricePackages[index].customerRate
                            );
                        }
                    });

                    // Return items total.
                    return total;
                },

                /**
                 * Calcualte all selected items total.
                 *
                 * @param  Integer  index
                 * @return String
                 */
                itemsTotal(index)
                {
                    let total = 0;
                    // Get room-wise totals for all rooms.
                    _.forEach(this.roomItems, (room) => {
                        total += this.roomwiseItemsTotal(room.id, index);
                    });
                    // Get selected custom items totals.
                    total += this.customItemsTotal(index);

                    // Return items total.
                    return that.formatMoney(total);
                },

                /**
                 * Get payment by description for the given name.
                 *
                 * @param  String  name
                 * @return String
                 */
                paymentByDescription(name)
                {
                    let paymentBy = _.find(this.paymentByOptions, ["name", name]);

                    return _.isUndefined(paymentBy) ? "" : paymentBy.description;
                },

                /**
                 * Get payment by image url for the given name.
                 *
                 * @param  String  name
                 * @return String
                 */
                paymentByImage(name)
                {
                    let paymentBy = _.find(this.paymentByOptions, ["name", name]);

                    return _.isUndefined(paymentBy) ? "" : paymentBy.image;
                },

                /**
                 * Get payment by shortcode for the given name.
                 *
                 * @param  String  name
                 * @return String
                 */
                paymentByShortcode(name)
                {
                    let paymentBy = _.find(this.paymentByOptions, ["name", name]);

                    return _.isUndefined(paymentBy) ? "" : paymentBy.shortcode;
                },
            },

            /**
             * Lifecycle hook used to run code after an instance is mounted.
             *
             * @return void
             */
            mounted()
            {
                // Fetch all quick estimation items when vue instance is mounted on DOM.
                this.fetchQuickEstimationItems();
            },
        });
    }

    /**
     * Calcualte the total price of an item based on given parameters.
     *
     * @param  Integer  quantity
     * @param  Integer  width
     * @param  Integer  height
     * @param  Integer  price
     * @return Integer
     */
    calculate(quantity, width, height, price)
    {
        return quantity * width * height * price;
    }
        
    /**
     * Calcualte the total price of an item based on given parameters.
     *
     * @param  Integer  money
     * @return String
     */
    formatMoney(money)
    {
        return money == 0 ? 0 : money.toFixed(2);
    }
}

export default QuickEstimation;
