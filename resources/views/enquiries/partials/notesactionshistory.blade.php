<?php

function profileImage($dbPictureName) {
    $defaultImage = URL::CDN("public/images/user-160x160.png");
    if ($dbPictureName) {
        $splittedFileName = explode(".", $dbPictureName);
        if ($splittedFileName[0] === $dbPictureName) {
            $profilePictureURL = $defaultImage;
        } else {
            $profilePicture = $splittedFileName[0] . "-160x160." . $splittedFileName[1];
            $profilePicture = str_replace('/source/', '/thumbnails/', $profilePicture);
            if (Storage::has($profilePicture)) {
                $profilePictureURL = URL::CDN($profilePicture);
            } else {
                $profilePictureURL = $defaultImage;
            }
        }
    } else {
        $profilePictureURL = $defaultImage;
    }
    return $profilePictureURL;
}
?>

<div class="nav-tabs-custom">
    <ul class="nav nav-tabs">
        @foreach($notesTypes as $Key => $type)
        <?php $ActiveTab = ""; ?>
        @if ($loop->first)
        <?php $ActiveTab = "active"; ?>
        @endif
        <li class="{{$ActiveTab}}"><a href="#{{$type["Id"]}}" data-toggle="tab"><strong>{{$type["Name"]}}</strong></a></li>
        @endforeach
    </ul>
    <div class="tab-content">
        @foreach($notesTypes as $Key => $type)
        <?php $ActiveTab = ""; ?>
        @if ($loop->first)
        <?php $ActiveTab = "active"; ?>
        @endif
        <div class="tab-pane {{$ActiveTab}}" id="{{$type["Id"]}}">
            @if(isset($notesActions[$type["Id"]]))       
            <div class="box-footer box-comments">
                <div id="CommentsBox">
                    <div class="pd-tp-8">
                        @foreach($notesActions[$type["Id"]] as $values)
                        <?php $ColWidth = "col-md-6"; ?>
                        @if($values->Type == 1)
                        <?php $ColWidth = "col-md-10"; ?>
                        @endif
                        <div class="box-comment">
                            <div class="row">
                                <div class="{{$ColWidth}}">
                                    <img class="img-circle img-sm" src="{{profileImage($values->AddedUserPhoto)}}" alt="User Image">
                                    <span class="username comment-text">{{$values->AddedBy}}</span>
                                    <div class="pd-lt-39" style="color:#555;">{{$values->Description}}</div>
                                </div>
                                @if($values->Type == 2)
                                <div class="col-md-4 added-time">
                                    <span>
                                        <strong>Due Date: </strong>{{$values->DueDate}}
                                    </span><br>                         
                                    <span>
                                        <strong>Assigned To: </strong> {{$values->AssignedTo}}
                                    </span><br>
                                    <span>
                                        <strong>Status: </strong>
                                        @if($values->Status)
                                        <span class="label label-{{$StatusLabel[$values->Status]}}">{{$Status[$values->Status]}}</span>
                                        @else
                                        <small>N/A</small>
                                        @endif
                                    </span>
                                </div>
                                @endif
                                <div class="col-md-2">
                                    <span class="text-muted pull-right">
                                        {{Carbon\Carbon::parse($values->CreatedAt)->addHours(5)->addMinutes(30)->format("d-M-Y h:i A")}}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>                 
                </div>
            </div>
            @else  
            <div class="alert alert-info" style="margin-top:0.5em;margin-bottom:0.8em;">No history available.</div>
            @endif
        </div>
        @endforeach
    </div>
</div>