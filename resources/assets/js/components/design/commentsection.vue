<template>
    <div>
        <ul class="timeline">
            <li id="ReplyListHeader" class="mr-bt-5">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <div class="timeline-item">
                            <h3 class="timeline-header no-text-transform no-border default-font custom-color">
                                Ideas / Notes for <strong class="pd-rt-6 text-blue">{{shortCode}}</strong>
                                <span class="fl-rt small">
                                    <i class="fa fa-paperclip"></i> Add (No Attachments) | 
                                    <i class="fa fa-paperclip text-blue"></i> Update/Add (Files Attached) | 
                                    <i class="fa fa-reply"></i> Reply | 
                                    <i class="fa fa-trash-o"></i> Delete
                                </span>
                            </h3>
                        </div>
                    </div>
                </div>
            </li>
            <li id="ReplyListStickyHeader" class="mr-bt-5 pos-fix hidden">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <div class="timeline-item">
                            <h3 class="timeline-header no-text-transform no-border default-font custom-color">
                                Ideas / Notes for <strong class="pd-rt-6 text-blue">{{shortCode}}</strong>
                                <span class="fl-rt small">
                                    <i class="fa fa-paperclip"></i> Add (No Attachments) | 
                                    <i class="fa fa-paperclip text-blue"></i> Update/Add (Files Attached) | 
                                    <i class="fa fa-reply"></i> Reply | 
                                    <i class="fa fa-trash-o"></i> Delete
                                </span>
                            </h3>
                        </div>
                    </div>
                </div>
            </li>  
            <li v-for="Comment in comments">
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pd-rt-0">
                        <div class="timeline-item">
                            <h3 class="timeline-header no-text-transform br-rt pd-4 designcomments">
                                <span v-if="Comment.TranshIcon" class="status cursorPointer" alt="Delete" data-toggle="tooltip" title="" data-widget="chat-pane-toggle" data-original-title="Delete" @click.prevent="deleteComment(Comment.Id)">
                                    <i class="fa fa-trash-o"></i>
                                </span>
                                <span class="pd-rt-6 text-blue user-name">{{Comment.Name}}</span>
                                <span class="comment-details"><i class="fa fa-clock-o mr-rt-3"></i>{{Comment.CreatedAt}} </span>
                                <span class="comment-details pd-rt-6 version"><i> (Design version - {{checkVersion(Comment.Version)}}) </i> </span>
                            </h3>
                            <div class="timeline-body mr-5 pd-lt-3">
                                {{Comment.Comment}}
                                <div class="attachments-container">
                                    <span v-for="files in Comment.Attachments">
                                        <a :class="files.Class+'-comment'" class="CursorPointer  design-img" :href="files.URL" >
                                            <img :src="files.URL" class="mr-rt-5" :title="files.Title">
                                        </a>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 mr-0 pd-lt-0">
                        <div v-if="Comment.Reply">
                            <div v-for="SubComment in Comment.Reply" class="timeline-item">
                                <h3 class="timeline-header no-text-transform pd-4 designcomments">
                                    <span class="pd-rt-6 text-blue user-name">{{SubComment.Name}}</span>
                                    <span class="comment-details"><i class="fa fa-clock-o"></i> {{SubComment.CreatedAt}}</span>
                                    <span class="fl-rt form-group no-margin" v-if="customerRole==false">
                                        <select name="Status" id="Status" @change="changeStatus(Comment.Id, $event)" class="form-control pd-0 pd-rt-5 status-dropdown">
                                            <option  v-for="(status, key) in replyStatuses" :value="key" :selected="status==Comment.Status">{{status}}</option>
                                        </select>
                                    </span>
                                    <span v-else class="fl-rt form-group no-margin text-blue" >{{Comment.Status}}</span>
                                    <span class="fl-rt form-group no-margin mr-rt-5"> Status: </span>
                                </h3>
                                <div class="timeline-body mr-5 pd-lt-3">
                                    {{SubComment.Message}}
                                    <div class="attachments-container">
                                        <span v-for="SubCommentfiles in SubComment.Attachment">
                                            <a :class="SubCommentfiles.Class+'-comment'" class="CursorPointer  design-img" :href="SubCommentfiles.URL" >
                                                <img :src="SubCommentfiles.URL" class="mr-rt-5" :title="SubCommentfiles.Title">
                                            </a>
                                        </span> 
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else>
                            <div class="timeline-item">
                                <form class="ReplyCommentForm" :id="'ReplyForm'+Comment.Id" method="POST" accept-charset="utf-8" action="" novalidate="novalidate">
                                    <h3 class="timeline-header no-text-transform pd-4 designcomments">
                                        <span class="pd-rt-6 text-blue user-name">Waiting for reply</span>
                                        <span class="fl-rt form-group no-margin" v-if="customerRole==false">
                                            <select name="Status" id="Status" class="form-control pd-0 pd-rt-5 status-dropdown">
                                                <option  v-for="(status, key) in replyStatuses" :value="key" :selected="status==Comment.Status">{{status}}</option>
                                            </select>
                                        </span>
                                        <span class="fl-rt form-group no-margin mr-rt-5" v-if="customerRole==false"> Status: </span>
                                        <input type="hidden" name="Status" v-if="customerRole==true" value="2">
                                    </h3>
                                    <div class="timeline-body mr-5">
                                        <div class="timeline-body mr-5">
                                            <div class="row">
                                                <div class="col-md-11 pd-rt-5 pd-lt-10">
                                                    <div class="form-group no-margin">
                                                        <input type="hidden" name="Version" :value="Comment.Version">
                                                        <input class="hidden fileupload" type="file" id="ReplyAttachments" name="ReplyAttachments[]" accept="image/*" multiple="multiple">
                                                        <input type="hidden" name="CommentId" id="CommentId" :value="Comment.Id">
                                                        <textarea name="ReplyText" rows="1" id="ReplyText" class="form-control" placeholder="Please add reply" style="resize: none;"></textarea>
                                                    </div>
                                                </div>
                                                <div class="col-md-1 pd-rt-5 pd-lt-5">
                                                    <button type="button" class="btn btn-box-tool pd-0 btn-attachmentupload" data-toggle="tooltip" title="" data-widget="chat-pane-toggle" data-original-title="Add Attachments"><i class="fa fa-paperclip"></i></button>
                                                    <button type="submit" class="btn btn-box-tool pd-0" data-toggle="tooltip" title="" data-widget="chat-pane-toggle" data-original-title="Reply"><i class="fa fa-reply"> </i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>    
                        </div>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</template>

<script>
    export default {

        props: {
            "comments": {
                type: Array
            },
            "customer-role":{
                type: Boolean
            },
            "short-code":{
                type: String
            },
            "reply-statuses":{
                type: Object
            }
        },

        methods: {

            checkVersion(Version){
                var Value = "Draft";
                if(Version.length>0){
                    Value = "V"+Version;
                }
                return Value;
            },

            deleteComment(Id){

                this.$emit("delcomment", Id);
            },

            changeStatus(Id, EventData){

                this.$emit("changestatus", {"CommentId":Id, "Status":EventData.target.value});
            }
        }
    }
</script>