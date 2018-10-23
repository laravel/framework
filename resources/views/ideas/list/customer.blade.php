@foreach ($ideas as $idea)
@if ($loop->first)
<div class="overlay hidden" id="ReplyIdeaFormOverlay">
    <div class="large loader"></div>
    <div class="loader-text">Posting Reply...</div>
</div>
<div id="ReplyIdeaFormNotificationArea" class="notification-area"></div>
<li id="ReplyListHeader" class="mr-bt-5">
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div class="timeline-item">
                <h3 class="timeline-header no-text-transform no-border default-font custom-color">
                    Ideas / Notes for <strong class="pd-rt-6 text-blue">{{ $idea->shortCode }}</strong>
                    <span class="fl-rt small">
                        <i class="fa fa-paperclip"></i> Add (No Attachments) | <i class="fa fa-paperclip text-blue"></i> Update/Add (Files Attached) | <i class="fa fa-reply"></i> Reply | <i class="fa fa-trash-o" ></i> Delete
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
                    Ideas / Notes for <strong class="pd-rt-6 text-blue">{{ $idea->shortCode }}</strong>
                    <span class="fl-rt small">
                        <i class="fa fa-paperclip"></i> Add (No Attachments) | <i class="fa fa-paperclip text-blue"></i> Update/Add (Files Attached) | <i class="fa fa-reply"></i> Reply | <i class="fa fa-trash-o" ></i> Delete
                    </span>
                </h3>
            </div>
        </div>
    </div>
</li>
@endif
<li>
    <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pd-rt-0">
            <div class="timeline-item">
                <h3 class="timeline-header no-text-transform br-rt designcomments">
                    <span class="pd-rt-6 text-blue">{{ $idea->commentUser }}</span>
                    <span class="comment-details"><i class="fa fa-clock-o"></i> {{ $idea->commentCreated() }}</span>
                    <span class="pd-rt-6 version comment-details"><i>(Design version - @if($idea->designVersion >=1) V{{ $idea->designVersion }} @else Draft @endif)</i></span>
                    @if ($idea->isNotReplied())
                    <span class="fl-rt">
                        <a href="{{ route("ideas.destroy", $idea->commentId) }}" class="delete-idea pull-right" alt="Delete" data-toggle="tooltip" title="" data-widget="chat-pane-toggle" data-original-title="Delete">
                            <i class="fa fa-trash-o mr-lt-5" aria-hidden="true"></i>
                        </a>
                    </span>
                    @endif
                </h3>
                <div class="timeline-body mr-5">
                    {{ $idea->comment }}
                    @if ($idea->hasAttachments())
                    <div class="attachments-container">
                        @foreach ($idea->attachments() as $attachment)
                        <img src="{{ $idea->attachmentPath($attachment->Path) }}" alt="{{ $attachment->UserFileName }}" class="mr-tp-5" width="50" height="50" data-mfp-src="{{ $idea->attachmentPath($attachment->Path) }}"/>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

        </div>
        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 mr-0 pd-lt-0">
            <div class="timeline-item">
                @if ($idea->hasReply())
                <h3 class="timeline-header no-text-transform designcomments">
                    <span class="pd-rt-6 text-blue">{{ $idea->replyUserFullname }}</span>
                    <span class="comment-details"><i class="fa fa-clock-o"></i> {{ $idea->repliedOn() }}</span>
                    <span class="fl-rt text-blue">
                        {{$statuses[$idea->commentStatus]}}
                    </span>
                    <span class="fl-rt form-group no-margin mr-rt-5">
                            Status: 
                    </span>
                    
                </h3>
                <div class="timeline-body mr-5">
                    {{ $idea->replyBody }}
                    @if ($idea->hasReplyAttachments())
                    <div class="attachments-container">
                        @foreach ($idea->replyAttachments() as $attachment)
                        <img src="{{ $idea->attachmentPath($attachment->Path) }}" alt="{{ $attachment->UserFileName }}" class="mr-tp-5" width="50" height="50" data-mfp-src="{{ $idea->attachmentPath($attachment->Path) }}"/>
                        @endforeach
                    </div>
                    @endif
                </div>
                @else 
                <h3 class="timeline-header no-text-transform designcomments">
                    <span class="pd-rt-6 text-blue">Waiting for reply</span>
                </h3>
                <div class="timeline-body mr-5">
                    <form name="ReplyIdeaForm{{ $idea->commentId }}" class="ReplyIdeaForm" method="POST" accept-charset="utf-8" action="{{ route("ideas.reply") }}" id="ReplyIdeaForm{{ $idea->commentId }}">
                        <div class="timeline-body mr-5">
                            <div class="row">
                                <div class="col-md-11 pd-rt-5 pd-lt-10">
                                    <div class="form-group no-margin">   
                                        <input type="hidden" name="CommentId" id="CommentId" value="{{ $idea->commentId }}"/>
                                        <input type="file" id="Attachments" name="Attachments[]" accept="image/*" multiple="multiple" class="hidden fileupload">
                                        @if ($idea->hasAttachments())
                                        <textarea name="Reply" id="Reply" class="form-control" placeholder="Please add reply" style="resize: none;" rows="2"></textarea>
                                        @else
                                        <textarea name="Reply" id="Reply" class="form-control" placeholder="Please add reply" style="resize: none;" rows="1"></textarea>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-1 pd-rt-5 pd-lt-5">
                                    <button type="button" class="btn btn-box-tool pd-0 btn-attachmentupload" data-toggle="tooltip" data-widget="chat-pane-toggle" data-original-title="Attachments"><i class="fa fa-paperclip"></i></button>
                                    <button type="submit" class="btn btn-box-tool pd-0" data-toggle="tooltip" data-widget="chat-pane-toggle" data-original-title="Reply"><i class="fa fa-reply"> </i></button>
                                </div>
                                <!--                            <div class="col-md-4">
                                                                <div class="form-group no-margin">
                                                                    <input class="form-control" type="file" name="Attachments[]" accept="image/*" multiple="multiple"/>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <button type="submit" class="btn btn-primary">Reply</button>
                                                            </div>-->
                            </div>
                        </div>
                    </form>
                </div>
                @endif
            </div>

        </div>
    </div>
</li>
@endforeach
