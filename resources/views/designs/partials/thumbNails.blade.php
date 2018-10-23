<div class="gallery">
@foreach($Attachments as $Data)
    @foreach( $Data["Attachments"] as $Files)
        @foreach($Files["Files"] as $file)
            @if($file["Class"]==='iframe'&&$file["ThumbNail"]=="")
            <a class="{{$file["Class"] }} CursorPointer" href="{{$file["URL"]}}" >
                <span class='rate-text-center'>
                    <i class=" fa fa-file-pdf-o overlay_pdf pdf-list" title="{{$file["Title"]}}">
                    </i>
                </span>
            </a>
            @endif
            @if($file["Class"]==='iframe'&&$file["ThumbNail"]!=="")
            <a class="{{$file["Class"]}} CursorPointer" href="{{$file["URL"]}}" >
                <img src="{{$file["ThumbNail"]}}" class="img-list" title="{{$file["Title"]}}" >
            </a>
            @endif
            @if($file["Class"]==='image')
            <a class="{{$file["Class"]}} CursorPointer" href="{{$file["URL"]}}" >
                <img src="{{$file["URL"]}}" class="img-list" title="{{$file["Title"]}}" >
            </a>
            @endif
        @endforeach
    @endforeach
@endforeach
</div>