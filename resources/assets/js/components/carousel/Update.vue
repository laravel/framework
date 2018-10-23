<template>
    <div class="modal fade" role="dialog" id="UpdateModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Update</h4>
                </div>
                <form :action="url+'/'+selectedCarousel.key" method="POST" accept-charset="utf-8" id="UpdateForm">
                    <div class="modal-body pd-bt-0">
                        <input type="hidden" name="key" :value="selectedCarousel.key">
                        <div class="row">
                            <div class="col-md-12">  
                                <div class="form-group">
                                    <label for="Title">Title*</label>
                                    <input type="text" name="Title" id="Title" :value="selectedCarousel.Title" class="form-control" placeholder=""/>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">  
                                <div class="form-group">
                                    <label for="Image">Image</label>
                                    <input type="file" name="Image" id="Image" accept="image/*" class="form-control" placeholder=""/>
                                    <small v-if="selectedCarousel.Source">{{fileName(selectedCarousel.Source)}}</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="Order">Order*</label>
                                    <select class="form-control" name="Order" id="Order">
                                        <option v-for="value in length" :value="value" :selected="value==selectedCarousel.Order">{{value}}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">  
                                <div class="form-group">
                                    <label for="Description">Description*</label>
                                    <textarea name="Description" id="Description" :value="selectedCarousel.Description" class="form-control no-resize-input" rows="5" placeholder="Ex:- "></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary pull-left button-custom" >Update</button>
                        <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
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
            "selected-carousel": {
                type: Object
            },
            "url": {
                type: String
            },
            "loader":{
                type: Boolean
            },
            "length":{
                type: Number
            }
        },
        methods: {
            fileName(Path){
                let FName = Path.split("/").pop();
                let Extension = FName.split(".").pop();
                let FileName = FName.split("_");
                let Name = "";
                if(FileName.length>1){
                    FileName.pop();
                    Name = FileName.join('_')+'.'+Extension;
                }else{
                    Name = FileName.join('');
                }
                return Name; 
            }
        }
    }
</script>