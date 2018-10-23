<template>
    <div class="modal fade" tabindex="-1" role="dialog" id="ViewModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">View</h4>
                </div>
                <div class="modal-body">
                    <table class="table table-striped table-bordered">
                        <tbody>
                            <tr>
                                <td width="30%">Title</td>
                                <td width="70%">{{selectedCarousel.Title}}</td>
                            </tr>
                            <tr>
                                <td>Order</td>
                                <td>{{selectedCarousel.Order}}</td>
                            </tr>
                            <tr>
                                <td>Image</td>
                                <td>
                                    <img v-if="selectedCarousel.Source" class="img-list" :src="url+selectedCarousel.Source">
                                </td>
                            </tr>
                            <tr>
                                <td>Description</td>
                                <td>{{selectedCarousel.Description}}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    // Child component
    export default {
        props: {
            "selected-carousel": {
                type: Object
            },
            "url":{
                type: String
            }
        }
    }
</script>