<?php

if (! function_exists('isPhoto')) 
{
    /**
     * Check if the given file is a photo
     * 
     * @param filename
     * @return boolean
     */
    function isPhoto($filename) 
    {
        // Get the file extension from the path
        $exploded = explode('.', $filename);
        $ext = strtolower(end($exploded));
        // Define the photos extensions
        $photoExtensions = ['png', 'jpg', 'jpeg', 'gif', 'jfif', 'tif'];
        // Check if this extension belongs to the extensions we defined
        if (in_array($ext, $photoExtensions)) {
            return true;
        }
        return false;
    }
}

if (! function_exists('isVideo')) 
{
    /**
     * Check if the given file is a video
     * 
     * @param filename
     * @return boolean
     */
    function isVideo($filename) 
    {
        // Get the file extension from the path
        $exploded = explode('.', $filename);
        $ext = end($exploded);
        // Define the videos extensions
        $videoExtensions = ['mov', 'mp4', 'avi', 'wmf', 'flv', 'webm'];
        // Check if this extension belongs to the extensions we defined
        if (in_array($ext, $videoExtensions)) {
            return true;
        }
        return false;
    }
}