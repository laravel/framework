<template>
    <div class="modal fade" role="dialog" id="AddModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Add</h4>
                </div>
                <form :action="url" method="POST" accept-charset="utf-8" id="AddForm">
                    <div class="modal-body pd-bt-0">
                        <div class="row">
                            <div class="col-md-12">               
                                <div class="form-group">
                                    <label for="Title">Title*</label>
                                    <input type="text" name="Title" id="Title" class="form-control" placeholder=""/>
                                 </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="Image">Image*</label>
                                    <input type="file" name="Image" id="Image" accept="image/*" class="form-control" placeholder=""/>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="Order">Order*</label>
                                    <select class="form-control" name="Order" id="Order">
                                        <option v-for="value in length+1" :value="value">{{value}}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="Description">Description*</label>
                                    <textarea name="Description" id="Description" class="form-control no-resize-input" rows="5" placeholder="Ex:- "></textarea>
                                </div>
                            </div>                    
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary pull-left button-custom" >Save</button>
                        <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
                <div class="overlay" id="AddFormOverlay" :class="{hidden: loader}">
                     <div class="large loader"></div>
                    <div class="loader-text">New...</div>
                </div>
                <div id="NotificationArea"></div>
            </div>
        </div>
    </div>
</template>
<script>
    // Child component
    export default {
        props: {
            "url": {
                type: String
            },
            "loader":{
                type: Boolean
            },
            "length":{
                type: Number
            }
        }
    }
</script>