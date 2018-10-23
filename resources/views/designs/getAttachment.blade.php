<?php
function attachment($Path) {
    $data = array();
    foreach (json_decode($Path, true) as $Key => $Value) {
        $Class = 'iframe';
        if ($Value['Path'] == null) {
            $data[$Key]['FileName'] = "null";
            $data[$Key]['UserFileName'] = $Value['UserFileName'];
        } else {
            $FileName = explode('/', $Value['Path']);            
            $data[$Key]['UserFileName'] = $Value['UserFileName'];
            $SystemFileName = array_pop($FileName);
            $data[$Key]['FileName'] = $SystemFileName;
            $Ext = explode('.', $data[$Key]['FileName']);
            if ($Ext[1] != 'pdf') {
                $Class = 'image';
            }
            $data[$Key]['Class'] = $Class;
            $data[$Key]['URL'] = $Value['Path'];
            $data[$Key]['Title'] = $Value["UserFileName"] . " : " . removeTimeStamp($SystemFileName);
            $data[$Key]['UserFileName'] = $Value['UserFileName'];
            $data[$Key]['Class'] = $Class;
    }
    }
    return $data;
}

function removeTimeStamp($SystemFileName){
    $FileNameArray = explode("_", $SystemFileName);
    $TimeStamp = array_pop($FileNameArray);
    $TimeStampArray = explode(".", $TimeStamp);
    $Extension = array_pop($TimeStampArray);
    $FileName = implode("_", $FileNameArray). "." . $Extension;
    
    return $FileName;
}
?>