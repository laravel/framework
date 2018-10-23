<template>
    <div class="modal fade" tabindex="-1" role="dialog" :id="'ViewNotesModal-' + roomno">
         <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Notes</h4>
                </div>
                <div class="modal-body">
                    <p v-for="note in notes">
                        <strong>{{note.Name}}: </strong> {{note.description}}<br>
                        <a v-for="noteattachment in note.attachments">
                            <img :src="noteattachment.src" class="notes-images" :title="noteattachment.title">
                        </a>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-right" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
            
        props: {

            "notes": {

                type: Array
            },
            
            "roomno": {
                
                type: Number
            }
        }
    }
</script>