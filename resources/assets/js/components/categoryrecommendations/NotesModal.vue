<template>
   <!-- Modal -->
  <div class="modal fade note-modal" role="dialog">
    <div class="modal-dialog">
    
      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header pd-15 pd-tp-10 pd-bt-10">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title text-primary">Add / Update Note</h4>
        </div>
        <div class="modal-body">
          <form action="" method="post" accept-charset="UTF-8" enctype="multipart/form-data" id="AddNoteForm">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                          <label for="Note">Note*</label>
                          <textarea name="Note" v-model="updatedNote" class="form-control no-resize-input" rows="5" id="Note"></textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-12 mr-tp-10">
                        <p>  
                            <input type="submit" 
                                name="AddNoteBtn" 
                                value="Save" 
                                class="btn btn-primary button-custom" 
                                id="AddNoteSubBtn"
                                style="width: 5em;"
                            />
                            <button type="button" 
                                class="btn button-custom" 
                                data-dismiss="modal"
                                style="width: 5em;"
                            >Close
                            </button>
                        </p>
                    </div>
                </div> 
          </form>
          <div class="overlay" v-if="noteLoader">
            <div class="large loader"></div>
            <div class="loader-text">Saving Note</div>
          </div>
        </div>
        <overlay-notification 
            :form-over-lay="noteOverLay" 
            :notification-icon="noteNotificationIcon" 
            :notification-message="noteNotificationMessage" 
            @clearmessage="clearOverLayMessage()" 
        >
        </overlay-notification>
      </div>
    </div>
  </div>

</template>
<script>
    // Child component
    import OverlayNotification from '../../components/overlayNotification';

    export default {
        props: { 
            "note" : {
                type: String
            },
            "note-loader": {
                type: Boolean
            },
            "note-over-lay": {
                type: Boolean
            },
            "note-notification-icon" : {
                type: String
            },
            "note-notification-message": {
                type: String
            }
        },
        data() {
            return {
                updatedNote: ""
            }
        },
        computed: {
            UpdatedNote() {
                return this.updatedNote = this.note;
            }       
        },
        updated() {
            this.note = this.$root.note;
        },
        components: {
            'overlay-notification': OverlayNotification
        },
        methods: {
            clearOverLayMessage() {
                this.$emit("closenoteoverlay");
            }
        }
    }
</script>