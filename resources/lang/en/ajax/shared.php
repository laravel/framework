<?php

return [
    "validation" => [
        "required" => ":attribute can't be blank.",
        "required_alias" => "This field is required.",
        "consecutive_spaces" => "Consecutive spaces are not allowed.",
        "max" => "Maximum :count :type are allowed.",
        "numeric" => "Only numerics are accepted.",
        "alphabet" => "Only alphabets are accpeted.",
        "email" => "Invalid email. Check email and try again.",
        "failed" => "Form inputs validation failed.",
        "unique" => ":attribute already exists. Try with different one.",
        "image" => "Invalid :attribute.  Accepted picture types: jpeg, png, bmp.",
        "filesize" => "Max upload file size is 10MB. Check file size and try again.",
        "length" => ":attribute should be :count :type in length.",
        "sentence" => "Only alphabets and numerics are accepted.",
        "file" => "Invalid file.  Accepted file types: jpeg, png, bmp, pdf.",
        "imagevideofile" => "Invalid file.  Accepted file types: jpeg,jpg,bmp,png,gif,avi,wmv,flv,mov,mpg,mp4,3gp.",
        "greater_than_field" => ":attribute Should be greater than :attribute2",
        "per_file_size" => "Max upload size is :size MB per file. Check file size and try again.",
        "min_text" => "Please enter atleast :count characters.",
        "mobile" => "Invalid mobile, check and try again.",
    ]
];
