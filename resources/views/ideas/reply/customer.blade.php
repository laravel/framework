<div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="ReplyModal" id="ReplyModal">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title no-text-transform">Post your reply</h4>
            </div>
            <form id="ReplyIdeaForm" method="POST" action="{{ route("ideas.reply") }}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="Reply">Reply*</label>
                                <input type="hidden" name="CommentId" id="CommentId"/>
                                <textarea name="Reply" id="Reply" class="form-control" rows="4" style="resize:none"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="Attachments">Attachments</label>
                                <input type="file" name="Attachments[]" id="Attachments" multiple="multiple" class="form-control"/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary pd-rt-20 pd-lt-20 mr-rt-8 pull-left" id="ReplyIdeaFormSubmit">Post</button>
                            <button type="reset" class="btn btn-default pull-left" id="ReplyIdeaFormReset">Undo changes</button>
                        </div>
                    </div>
                </div>
            </form>
            <div class="overlay hidden" id="ReplyIdeaFormOverlay">
                <div class="large loader"></div>
                <div class="loader-text">Posting Reply...</div>
            </div>
            <div id="ReplyIdeaFormNotificationArea" class="notification-area"></div>
        </div>
    </div>
</div>
