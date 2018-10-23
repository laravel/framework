<template>
    <div class="modal fade" id="RoomsDetachAlert" role="dialog">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Alert</h4>
                </div>
                <div class="modal-body">
                    <p>
                        All other Selections with room <code class="idpro-codespan">{{room ? room : "N/A"}}</code> will be deleted.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary pull-left" id="DeleteComSubmit" @click="selectionsDetachConfirm(combinationId)">Yes</button>
                    <button type="button" class="btn pull-left mr-lt-10" data-dismiss="modal">No</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    // Child component
    export default {
        props: {
            "combination-id": {
                type: String
            },
            "room": {
                type: String
            }
        },
        methods: {
            // emit an event to inform parent(Vue) element about activity
            selectionsDetachConfirm(id) {
                this.$emit("deleteselections", id);
            },
            getRoomArea() {
                return this.room[0].text;
            }
        }
    }
</script>