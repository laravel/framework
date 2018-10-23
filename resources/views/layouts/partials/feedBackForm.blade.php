<div id="ContactUsLink" data-toggle="modal" data-target=".feedback-modal" role="button">
    <i class="fa fa-comments-o"></i>
</div>

<div class="modal fade feedback-modal" tabindex="-1" role="dialog" aria-labelledby="FeedbackModal">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <form class="ContactUsForm" id="FeedbackForm">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="gridSystemModalLabel">Feedback form</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="feedBackName">Name</label>
                        <input type="text" name="feedBackName" id="feedBackName" class="form-control text-capitalize" value="{{ Auth::user()->Person->FirstName }} {{ Auth::user()->Person->LastName }}" disabled="disabled" />
                    </div>
                    <div class="form-group">
                        <label for="feedBackEmail">Email</label>
                        <input type="email" name="feedBackEmail" id="feedBackEmail" class="form-control" value="{{ Auth::User()->Email }}" disabled="disabled"/>
                    </div>
                    <div class="form-group">
                        <label for="feedBackMessage">Message</label><br />
                        <textarea rows="6" class="form-control no-resize-input" id="feedBackMessage" name="feedBackMessage"></textarea>
                    </div>
                    <div class="form-group">
                        <input type="submit" name="send" id="SendMessage" value="Send Message" class="btn btn-primary text-capitalize" />
                        <button type="button" class="btn btn-link fl-rt" data-dismiss="modal" style="padding-right:0">Cancel</button>
                    </div>
                </div>
            </form>
            <div id="FeedbackOverlay" class="form-loader hidden"></div>
            <div id="FeedbackNotificationArea"></div>
        </div>
    </div>
</div>
