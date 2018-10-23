<template>
    <div class="notification-overlay" :class="{hidden: formOverLay}" id="FormUpdatedOverLay" @click.prevent="clearMessage()">
        <div style="text-align: center;" :class="'overlay-'+notificationIcon">
            <button type="button" class="close notificationOverlay-close" @click.prevent="clearMessage()" aria-label="Close" title="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <div :class="'notificationOverlay-icon fa fa-' + notificationIcon">
            </div>
           <div class="nofitication-message">{{notificationMessage}}</div>
        </div>
   </div> 
</template>

<script>
    export default {

        props: {

            "form-over-lay":{
                
                type: Boolean
            },
            "notification-icon": {

                type: String
            },
            "notification-message":{
                
                type: String
            }
        },

        methods: {

            clearMessage() {

                this.$emit("clearmessage");
            }
        }
    }
</script>