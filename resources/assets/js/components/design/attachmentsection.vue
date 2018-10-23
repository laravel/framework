<template>
    <div>
        <div  v-for="(Files, key) in attachments" class="attachment_container row">
            <div class="mr-bt-20 design-img">
                <div class="mr-bt-10">
                    <h4 class="box-title no-text-transform col-md-12 pd-lt-11">Version: {{Files.Version}} 
                        <a :href="pageUrl+'designs/downloadattachments/'+designId+'/'+Files.Version" title="Download all attachments of this Version" class="mr-rt-10">
                            <i class="fa fa-fw fa-download CursorPointer"></i>
                        </a>
                        <span v-if="key==0&&attachments.length<attachmentCount" @click.prevent="showMore(designId)" class="CursorPointer text-blue show-more-link">
                            Show Older Versions <i aria-hidden="true" class="fa fa-plus-circle"></i>
                        </span>
                        <span v-if="key==0&&attachments.length>1" @click.prevent="showLatest()" class="CursorPointer text-blue show-latest-link">
                            Show Only Latest <i aria-hidden="true" class="fa fa-minus-circle"></i>
                        </span>
                    </h4>
                </div>
                <div v-for="(file, index) in Files.Attachments" :class="attachBoxClasses(index)">
                    <p v-if="file.Name!='Reference Images'">{{file.Name}}</p>
                    <p v-else>Reference</p>
                    <div class="row">
                        <div v-for="attach in file.Files" :class="imgBoxClasses(index)+ ' element-container'">
                            <span v-if="attach.Class==='iframe'&&attach.ThumbNail==''" class='mr-rt-5 rate-text-center'>
                                <i class=" fa fa-file-pdf-o design_pdf overlay_pdf" :title="attach.Title">
                                </i>
                            </span>
                            <img  v-if="attach.Class==='iframe'&&attach.ThumbNail!==''" :src="attach.ThumbNail" :title="attach.Title" class="overlay-img">
                            <img v-if="attach.Class==='image'" :src="attach.URL" :title="attach.Title" class="overlay-img">
                            <div class="middle CursorPointer row">
                                <div class="col-md-6 col-xs-6 pd-lt-0 pd-rt-0">
                                    <a  :class="attach.Class" class="CursorPointer" :href="attach.URL" >
                                        <i class="fa fa-fw fa-eye eye-icon"  title="View"></i>
                                    </a>
                                </div>
                                <div class="col-md-6 col-xs-6 pd-lt-3 pd-rt-0">
                                    <a :href="pageUrl+'designs/downloadfile/'+designId+'/'+attach.FileName">
                                        <i class="fa fa-fw fa-download download-icon"  title="Download"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {

        props: {

            "attachments": {

                type: Array
            },
            "page-url":{

                type: String
            },
            "design-id":{

                type: String
            },
            "attachment-count":{

                type: String
            }
        },

        methods: {

            showMore(Id){
                
                this.$emit("showmore", {"Url":"/designs/attachments/"+Id, "Element":"Attachments"});
            },

            showLatest(){
                this.$emit("showlatest");
            },

            attachBoxClasses(AttachName){
                
                var Classes = "col-md-4 col-sm-12";
                if(AttachName == "3D"){
                    Classes = "col-md-3 col-sm-6";
                }
                if(AttachName == "RefImages"){
                    Classes = "col-md-1 col-sm-6";
                }
                return Classes;
            },

            imgBoxClasses(AttachName){

                var Classes = "col-md-3 col-xs-2";
                if(AttachName == "3D"){
                    Classes = "col-md-4 col-xs-4";
                }
                if(AttachName == "RefImages"){
                    Classes = "col-md-12 col-xs-4";
                }
                return Classes + " pd-rt-0 pd-lt-10";
            }

        }
    }
</script>