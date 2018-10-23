<template>
    <div class="modal fade" id="ViewNotesModal" role="dialog" ref="ViewNotesModal">
        <div class="modal-dialog modal-md">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Notes</h4>
                </div>
                <div class="modal-body">                 
                    <div class="box-footer box-comments" v-if="combinationNotes.length > 0">
                        <div id="CommentsBox">
                            <div class="pd-tp-8">
                                <div class="box-comment" v-for="note in combinationNotes">
                                    <img class="img-circle img-sm" :src="note.User.Photo" alt="User Photo">
                                    <div class="comment-text">
                                        <span class="username">
                                            {{note.User.Name}}
                                            <span class="text-muted pull-right">
                                            {{note.CreatedTime | giveDate}}
                                            </span>
                                        </span>
                                        <span>
                                            {{note.Note}}
                                        </span>
                                    </div>
                                </div>
                            </div>                 
                        </div>
                    </div>
                    <div v-else class="alert alert-info" style="margin-top:0.5em;margin-bottom:0.8em;">No notes added for combination.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div> 
</template>
<script>
// moment.js converts timestamp to local date and time format
let moment = require('moment');
    // Child component
    export default {
        props: {
            "combination-notes": {
                type: Array
            }
        },
        filters: {
            giveDate(MappedAt){
                return moment(MappedAt).add(5, 'h').add(30, 'm').format("DD-MMM-YYYY hh:mm A");
            }
        }
    }
</script>