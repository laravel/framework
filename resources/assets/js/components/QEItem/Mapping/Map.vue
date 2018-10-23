<template>
    <div class="modal fade" role="dialog" id="MapModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Detail Estimation Item Mapping</h4>
                </div>
                <form :action="url+'/'+selectedItem.Id" method="POST" accept-charset="utf-8" id="UpdateForm">
                    <input :value="filter" name="filter" type="hidden">
                    <div class="modal-body pd-bt-0">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Quick Estimation Item</label>
                                    <p>{{selectedItem.Description}}</p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="DEItems">Detail Estimation Item*</label>
                                    <select name="DEItems[]" id="DEItems" class="form-control" multiple="multiple" style="width:100%">
                                        <option value="">Select a Item</option>
                                        <option v-for="item in detailEstItems" :selected="selectValue(item.Id)" :value="item.Id">{{ item.Description }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary pull-left button-custom" >Update</button>
                    </div>
                </form>
                <div class="overlay" id="UpdateFormOverlay" :class="{hidden: loader}">
                     <div class="large loader"></div>
                    <div class="loader-text">Updating...</div>
                </div>
                <div id="UpdateNotificationArea"></div>
            </div>
        </div>
    </div>
</template>
<script>
    // Child component
    export default {
        props: {
            "selected-item": {
                type: Object
            },
            "url": {
                type: String
            },
            "loader":{
                type: Boolean
            },
            "detail-est-items":{
                type: Array
            },
            "filter":{
                type: String
            }
        },
        methods:{
            selectValue(Id){
                if(this.selectedItem.DetailItemsId){
                    return _.find(this.selectedItem.DetailItemsId, function(value) {
                        return Id === value;
                    });
                }
            }
        }
    }
</script>