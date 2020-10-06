<?php
    $function = isset($_REQUEST['f']) ? $_REQUEST['f'] : "no_function";
    ini_set("display_errors",0);
    if($function == "no_function")
        die;
    else
        if(!function_exists($function))
            die;
        $function();

    //////////////////////////////Functions Here !//////////////////////////////////////////////

    function sendMsg(){
        require_once "conf.php";
        $conn = new mysqli(hdts_db_host, hdts_db_user, hdts_db_password, hdts_db_name);
        $by = isset($_REQUEST['by']) ? $_REQUEST['by'] : die;
        $usr = isset($_REQUEST['usr']) ? $_REQUEST['usr'] : die;
        $msg = isset($_REQUEST['msg']) ? $_REQUEST['msg'] : die;
        $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : die;
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8_mb4");
        $from = "";
        $to = "";
        if($by != "admin"){
            $from = $by;
            $to = "admin";
        }else {
            $from = "admin";
            $to = $usr;
        }
        $sql = "INSERT INTO ".hdts_db_prefix
            ."hdts_chats (`id`, `msg_from`, `msg_to`, `msg`, `msg_type`, `msg_status`, `msg_date`, `notify`) "
            ." VALUES (NULL, '".$from."', '".$to."', '".$msg."', '".$type."', 'sent', '".cDate()."', 'false');";
        if($conn->query($sql)){
            echo  "success";
            $location = isset($_REQUEST['locate']) ? $_REQUEST['locate'] : die;
            addTrace($location,"msg");
            sNotification($from,"یک پیام جدید" ,$msg,$type);
        }else{
            echo "error";
        }
    }

    function getMsg(){
        require_once "conf.php";
        $conn = new mysqli(hdts_db_host, hdts_db_user, hdts_db_password, hdts_db_name);
        $conn->set_charset("utf8_mb4");
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $sql = "SELECT * FROM ".hdts_db_prefix."hdts_chats";
        $result = $conn->query($sql);
        $allMsg = "";
        function getStatusImage($whoIsGetting,$row){
            $status_image = "";
            if($whoIsGetting != "admin"){
                if($row['msg_status'] == "sent"){
                    $status_image = "ic_sent.png";
                }else if($row['msg_status'] == "seen"){
                    $status_image = "ic_seen.png";
                }else if($row['msg_status'] == "failed"){
                    $status_image = "ic_failed.png";
                }
            }else{
                $status_image = "ic_failed.png";
            }
            return $status_image;
        }
        if ($result->num_rows > 0) {
            $whoIsGetting = isset($_REQUEST['by']) ? $_REQUEST['by'] : die;
            $count = 0;
            while($row = $result->fetch_assoc()) {
                if($row['msg_from'] == $whoIsGetting || $row['msg_to'] == $whoIsGetting){
                    $count++;
                    $msg = "";
                    $whoIsThis = "";
                    $status_image = "";
                    $loading_status = "";
                    $status_status = "";
                    if($row['msg_from'] == "admin"){
                        $seenQuery = "UPDATE `".hdts_db_prefix."hdts_chats` SET `msg_status` = 'seen' WHERE `".hdts_db_prefix."hdts_chats`.`id` = '".$row['id']."';";
                        $conn->query($seenQuery);
                        $status_status = "none";
                        $loading_status = "none";
                        $whoIsThis = "left";
                    }else {
                        $status_image = getStatusImage($whoIsGetting,$row);
                        $status_status = "block";
                        $loading_status = "none";
                        $whoIsThis = "right";
                    }
                    if($row['msg_type'] == "msg"){
                        $msg =
                            '<div class="message '.$whoIsThis.'">
                        <div class="message-view">
                            <div class="message-content" >
                                <div class="message-text">'.$row["msg"].'</div>
                                <div class="loader" style="display: '.$loading_status.';"></div>
                                <img class="status" style="display: '.$status_status.';" src="http://kamart.ir/wp-content/plugins/hdtsChat/image/'.$status_image.'"/>
                            </div>
                            <div class="message-date" >'.getDateToShow($row['msg_date'],"web").'</div>
                        </div>
                    </div>';
                    }else if($row['msg_type'] == "img"){
                        $msg =
                            '<div class="message '.$whoIsThis.'">
                        <div class="message-view">
                            <img class="message-image" onclick="openClickedImage(this);" src="'.$row["msg"].'"/>
                            <div class="message-content" >
                                <div id="loader-'.$row["id"].'" class="loader" style="display: '.$loading_status.';"></div>
                                <img id="status-'.$row["id"].'" class="status" style="display: '.$status_status.';" src="http://kamart.ir/wp-content/plugins/hdtsChat/image/'.$status_image.'"/>
                            </div>
                            <div class="message-date" >'.getDateToShow($row['msg_date'],"web").'</div>
                        </div>
                    </div>';
                    }else if($row['msg_type'] == "msi"){
                        $msi = json_decode($row["msg"],JSON_UNESCAPED_UNICODE);
                        $message = $msi['msg'];
                        $image = $msi['img'];
                        $msg =
                            '<div class="message '.$whoIsThis.'">
                        <div class="message-view">
                        <img class="message-image" onclick="openClickedImage(this);" src="'.$image.'"/>
                            <div class="message-content" >
                                <div class="message-text">'.$message.'</div>
                                <div class="loader" style="display: '.$loading_status.';"></div>
                                <img class="status" style="display: '.$status_status.';" src="http://kamart.ir/wp-content/plugins/hdtsChat/image/'.$status_image.'"/>
                            </div>
                            <div class="message-date" >'.getDateToShow($row['msg_date'],"web").'</div>
                        </div>
                    </div>';
                    }else {

                    }
                    $allMsg .= $msg;
                }
            }
            $resMsg = array("msg"=>$allMsg,"count"=>$count);
            echo json_encode($resMsg);
        }else{
            $resMsg = array("msg"=>"","count"=>0);
            echo json_encode($resMsg);
        }
    }

    function getMIFS(){
        require_once "conf.php";
        $agent = isset($_REQUEST['agent']) ? $_REQUEST['agent'] : die;
        $userPIP = isset($_REQUEST['userPip']) ? $_REQUEST['userPip'] : die;
        $location = isset($_REQUEST['locate']) ? $_REQUEST['locate'] : die;
        $conn = new mysqli(hdts_db_host, hdts_db_user, hdts_db_password, hdts_db_name);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8_mb4");

        $sql = "SELECT * FROM `".hdts_db_prefix."hdts_users` WHERE user_agent='".$agent."' AND user_ip='".$userPIP."' ";
        $result = $conn->query($sql);
       if($result->num_rows == 0){
           $insertSQL = "INSERT INTO `".hdts_db_prefix."hdts_users` (`id`, `user_agent`, `user_ip`, `user_location`) VALUES "
           ." (NULL, '".$agent."', '".$userPIP."', '".$location."');";
           addTrace($location,"view");
           $inseResult = $conn->query($insertSQL);
           if($inseResult == 1) {
               echo_id($conn,$agent,$userPIP,$location);
           }else {
               echo "false";
           }
       }else if($result->num_rows == 1){
           $checkLocation = "SELECT * FROM ".hdts_db_prefix."hdts_users WHERE user_agent='".$agent."' AND user_ip='".$userPIP."' AND user_location='".$location."' ";
            $cheResult = $conn->query($checkLocation);
            if($cheResult->num_rows == 0){
                $updLocation = "UPDATE `".hdts_db_prefix."hdts_users` SET `user_location` = '".$location."' WHERE `user_agent`= '".$agent."' AND `user_ip`='".$userPIP."';";
                $cheLocResult = $conn->query($updLocation);
                if($cheLocResult == 1) {
                    echo_id($conn,$agent,$userPIP,$location);
                }else{
                    echo "false";
                }
            }else{
                echo_id($conn,$agent,$userPIP,$location);
            }
       }

    }

    function echo_id($conn,$agent,$userPIP,$location){
        $idSQL = "SELECT * FROM ".hdts_db_prefix."hdts_users WHERE user_agent='".$agent."' AND user_ip='".$userPIP."' AND user_location='".$location."' ";
        $idResult = $conn->query($idSQL);
        while ($row = $idResult->fetch_assoc()){
            echo  $row['id'];
        }
    }

    function sendImg(){
        require_once "conf.php";
        $conn = new mysqli(hdts_db_host, hdts_db_user, hdts_db_password, hdts_db_name);
        $conn->set_charset("utf8_mb4");
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        if(isset($_FILES["file"]["type"]))
        {
            $rand = rand(100,999999999);
            if (move_uploaded_file($_FILES["file"]["tmp_name"], "./images/".$rand.$_FILES['file']['name'])) {
                $path ="http://192.168.43.21:8080/FreeHand/Plugins/hdtsChat/unScramble/images/".$rand.$_FILES['file']['name'];
                $from = isset($_REQUEST['from']) ? $_REQUEST['from'] : die;
                $to = isset($_REQUEST['to']) ? $_REQUEST['to'] : die;
                $location = isset($_REQUEST['locate']) ? $_REQUEST['locate'] : die;
                $sql = "INSERT INTO `".hdts_db_prefix."hdts_chats`(`id`, `msg_from`, `msg_to`, `msg`, `msg_type`, `msg_status`, `msg_date`, `notify`)"
                    ." VALUES (null,'".$from."','".$to."','".$path."','img','sent','".cDate()."','false');";
                if($conn->query($sql)){
                    addTrace($location,"msg");
                    echo $path;
                }else{
                    echo "error in query";
                }
            }

        }
        else{
            echo "error parsing image";
        }
    }

    function sendMSI(){
        require_once "conf.php";
        $conn = new mysqli(hdts_db_host, hdts_db_user, hdts_db_password, hdts_db_name);
        $conn->set_charset("utf8_mb4");
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }


        if(isset($_FILES["file"]["type"]) && isset($_REQUEST['msg']))
        {
            $rand = rand(100,999999999);
            if (move_uploaded_file($_FILES["file"]["tmp_name"], "./images/".$rand.$_FILES['file']['name'])) {
                $path ="http://192.168.43.21:8080/FreeHand/Plugins/hdtsChat/unScramble/images/".$rand.$_FILES['file']['name'];
                $from = isset($_REQUEST['from']) ? $_REQUEST['from'] : die;
                $msg = isset($_REQUEST['msg']) ? $_REQUEST['msg'] : die;
                $to = isset($_REQUEST['to']) ? $_REQUEST['to'] : die;
                $location = isset($_REQUEST['locate']) ? $_REQUEST['locate'] : die;
                $msgArray = array("img"=>$path,"msg"=>$msg );
                $msgToUpload = json_encode($msgArray , JSON_UNESCAPED_UNICODE);
                $sql = "INSERT INTO `".hdts_db_prefix."hdts_chats`(`id`, `msg_from`, `msg_to`, `msg`, `msg_type`, `msg_status`, `msg_date`, `notify`)"
                ." VALUES (null,'".$from."','".$to."','".$msgToUpload."','msi','sent','".cDate()."','false');";
                if($conn->query($sql)){
                    addTrace($location,"msg");
                    echo $path;
                }else{
                    echo "error in query";
                }
            }

        }
        else{
            echo "error parsing image";
        }
    }

    function getUsers(){
        require_once "conf.php";
        $conn = new mysqli(hdts_db_host, hdts_db_user, hdts_db_password, hdts_db_name);
        $conn->set_charset("utf8_mb4");
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $sql = "SELECT * FROM ".hdts_db_prefix."hdts_users ";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            $output = array();
            while ($row = $result->fetch_assoc()){
                $filed = array();
                $filed['id'] = $row['id'];
                $cQuery = "SELECT * FROM `".hdts_db_prefix."hdts_chats` WHERE msg_from='".$row['id']."' AND msg_to='admin' OR  msg_to='".$row['id']."' AND msg_from='admin' ORDER BY `msg_date` DESC LIMIT 1";
                $seenQuery = "SELECT * FROM `".hdts_db_prefix."hdts_chats` WHERE  msg_from='".$row['id']."'  AND msg_status='sent' AND msg_to='admin' ORDER BY `msg_date`";

                $notSeen = $conn->query($seenQuery);
                $cR = $conn->query($cQuery);
                $msg = $cR->fetch_assoc();
                $filed['last_msg'] = $msg['msg'];
                $filed['not_seen'] = $notSeen->num_rows;
                $filed['date'] = getDateToShow($msg['msg_date'],"android");
                $filed['msg_from'] = $msg['msg_from'];
                $filed['agent'] = $row['user_agent'];
                $filed['ip'] = $row['user_ip'];
                $filed['location'] = $row['user_location'];
                $output[] = $filed;
            }
            echo json_encode($output,JSON_UNESCAPED_UNICODE);
        }else{
            $filed = array();
            $output = array();
            $filed['last_msg'] = "null";
            $filed['id'] = -1;
            $filed['not_seen'] ="null";
            $filed['date'] ="null";
            $filed['msg_from'] = "null";
            $filed['agent'] = "null";
            $filed['ip'] = "null";
            $filed['location'] = "null";
            $output[] = $filed;
            echo json_encode($output);
        }

    }

    function getMessages(){
        require_once "conf.php";
        $conn = new mysqli(hdts_db_host, hdts_db_user, hdts_db_password, hdts_db_name);
        $conn->set_charset("utf8_mb4");
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $sql = "SELECT * FROM ".hdts_db_prefix."hdts_chats  ";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            $who = isset($_REQUEST['who']) ? $_REQUEST['who'] : die;
            $output = array();
            while($row = $result->fetch_assoc()){
                $filed = array();
                if($row['msg_from'] == $who  && $row['msg_to'] == "admin" || $row['msg_to'] == $who && $row['msg_from'] == "admin"){
                    if($row['msg_to'] == "admin"){
                        $seenQuery = "UPDATE `".hdts_db_prefix."hdts_chats` SET `msg_status` = 'seen' WHERE `".hdts_db_prefix."hdts_chats`.`id` = '".$row['id']."';";
                        if(!$conn->query($seenQuery)){
                            die();
                        }
                    }
                    $filed['msg_id'] = $row['id'];
                    $filed['msg_from'] = $row['msg_from'];
                    $filed['msg_to'] = $row['msg_to'];
                    $filed['msg'] = $row['msg'];
                    $filed['msg_type'] = $row['msg_type'];
                    $filed['msg_status'] = $row['msg_status'];
                    $filed['msg_date'] = getDateToShow($row['msg_date'],"android");
                    $filed['notify'] = $row['notify'];
                    $output[] = $filed;
                }
            }
            echo json_encode($output,JSON_UNESCAPED_UNICODE);
        }else{
        echo "0";
        }
    }

    function deleteUser(){
        require_once "conf.php";
        $conn = new mysqli(hdts_db_host, hdts_db_user, hdts_db_password, hdts_db_name);
        $conn->set_charset("utf8_mb4");
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $user_id = isset($_REQUEST['id']) ? $_REQUEST['id'] : die;
        $which = isset($_REQUEST['which']) ? $_REQUEST['which'] : die;
        $delUser = "DELETE FROM `".hdts_db_prefix."hdts_users` WHERE id='".$user_id."' ";
        $delChats = "DELETE FROM `".hdts_db_prefix."hdts_chats` WHERE msg_from='".$user_id."' AND msg_to='admin' OR msg_to='".$user_id."' AND msg_from='admin'";
        if($which == "user"){
            if($conn->query($delUser)){
                echo json_encode("deleted");
            }else{
                echo json_encode("error");
            }

        }else if($which == "msg"){
            if($conn->query($delChats)){
                echo json_encode("deleted");
            }else{
                echo json_encode("error");
            }

        }else if($which == "all"){
            if($conn->query($delUser) && $conn->query($delChats)){
                echo json_encode("deleted");
            }else{
                echo json_encode("error");
            }

        }else {
            echo json_encode("cant detect");
        }


    }

    function refreshToken(){
        require_once "conf.php";
        $conn = new mysqli(hdts_db_host, hdts_db_user, hdts_db_password, hdts_db_name);
        $conn->set_charset("utf8_mb4");
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $token = isset($_REQUEST['token']) ? $_REQUEST['token'] : die;
        $sql = "SELECT * FROM `".hdts_db_prefix."hdts_admin` WEHRE `fcm_code` = '".$token."';";
        $resault = $conn->query($sql);
        if($resault->num_rows == 0){
            $device = isset($_REQUEST['device']) ? $_REQUEST['device'] : die;
            $insert = "INSERT INTO `".hdts_db_prefix."hdts_admin`(`id`, `developer_name`, `show_email`, `fcm_code`) VALUES (null,'".$device."','false','".$token."')";
            if($conn->query($insert)){
                echo json_encode("success");
            }else{
                echo json_encode("failed insert");
            }
        }else{
            echo json_encode("failed sql");
        }
    }

    function sendMessage(){
        require_once "conf.php";
        $conn = new mysqli(hdts_db_host, hdts_db_user, hdts_db_password, hdts_db_name);
        $conn->set_charset("utf8_mb4");
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $to = isset($_REQUEST['to']) ? $_REQUEST['to'] : die;
        $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : die;
        $msg = isset($_REQUEST['msg']) ? $_REQUEST['msg'] : die;
        $sql = "INSERT INTO ".hdts_db_prefix
            ."hdts_chats (`id`, `msg_from`, `msg_to`, `msg`, `msg_type`, `msg_status`, `msg_date`, `notify`) "
            ." VALUES (NULL, 'admin', '".$to."', '".$msg."', '".$type."', 'sent', '".cDate()."', 'false'); ";
        if($conn->query($sql)) {
            echo json_encode("inserted");
        }else {
            echo json_encode("error");
        }
    }

function cDate(){
    date_default_timezone_set("Iran");
    return date("Y-m-d H:i");
}

function getDateToShow($itemDate,$for){
    $msgToReturn = "<div style='opacity: 0.5 ;color: #474747; width: auto;display: flex; margin-bottom: -30px; '>";
    if($itemDate == cDate()){
        if($for== "android"){
            $msgToReturn = "همین الان";
        }else{
            $msgToReturn .= "همین الان";
        }
    }else{
        $spacePos = strpos($itemDate," ");
        $yearToDay = substr($itemDate,0,$spacePos);
        $length = strlen($itemDate);
        $hourToMinute = substr($itemDate,$spacePos+1,$length);

        $cYear = cYear();
        $cMonth = cMonth();
        $cDay = cDay();
        $cHour = cHour();
        $cMinute = cMinute();

        $witchDay = "";
        $witchHour = "";

        $cYearToDay = $cYear . "-" . $cMonth . "-" . $cDay;
        $cHourToMinute = $cHour . ":" . $cMinute;

        if($cYearToDay == $yearToDay){
            if($cHourToMinute != $hourToMinute){
                $iPos = strpos($hourToMinute,":");
                $iMinute = substr($hourToMinute,$iPos+1,strlen($hourToMinute));
                $iHour = substr($hourToMinute,0,$iPos);
                if($cHour == $iHour){
                    if($cMinute != $iMinute){
                        $mMinute = $cMinute - $iMinute;
                        if($for== "android"){
                            $msgToReturn = $mMinute. " دقیقه پیش ";
                        }else{
                            $msgToReturn .= $mMinute."<div style='color: grey;margin-right: 4px; margin-left: 4px'> دقیقه پیش </div>";
                        }
                    }
                }else{
                    $minHour = $cHour - $iHour;
                    if($for == "android")
                        $msgToReturn = $minHour." ساعت پیش ";
                    else
                        $msgToReturn .= $minHour."<div style='color: grey;margin-right: 4px; margin-left: 4px'> ساعت پیش </div>";
                }
            }
        }else{
            $iPosFDash = strpos($yearToDay,"-");
            $iMonthToDay = substr($yearToDay , $iPosFDash+1,strlen($yearToDay));
            $iPosSDash = strpos($iMonthToDay,"-");

            $iMonth = substr($iMonthToDay,0,$iPosSDash);
            $iDay = substr($iMonthToDay,$iPosSDash+1,strlen($iMonthToDay));
            $iYear = substr($yearToDay,0,$iPosFDash);
            if($iYear == $cYear){
                if($iMonth == $cMonth){
                    if($iDay != $cDay){
                        $minDay = $cDay - $iDay;
                        if($for == "android"){
                            $msgToReturn = $minDay." روز پیش ";
                        }else{
                            $msgToReturn .= $minDay."<div style='color: grey;margin-right: 4px; margin-left: 4px'> روز پیش </div>";
                        }
                    }
                }else{
                    $minMonth = $cMonth - $iMonth;
                    if($for == "android"){
                        $msgToReturn = $minMonth." ماه پیش ";
                    }else
                        $msgToReturn .= $minMonth."<div style='color: grey;margin-right: 4px; margin-left: 4px'> ماه پیش </div>";
                }
            }else{
                $minYear = $cYear - $iYear;
                if($for == "android")
                    $msgToReturn = $minYear." سال پیش ";
                else
                    $msgToReturn .= $minYear."<div style='color: grey;margin-right: 4px; margin-left: 4px'> سال پیش </div>";
            }
        }

    }
    if($for != "android")
        $msgToReturn .= "</div>";

    return  $msgToReturn ;
}

function cYear(){
    date_default_timezone_set("Iran");
    return date("Y");
}
function cMonth(){
    date_default_timezone_set("Iran");
    return date("m");
}
function cDay(){
    date_default_timezone_set("Iran");
    return date("d");
}
function cHour(){
    date_default_timezone_set("Iran");
    return date("H");
}
function cMinute(){
    date_default_timezone_set("Iran");
    return date("i");
}


function addTrace($location,$type){
    require_once "conf.php";
    $conn = new mysqli(hdts_db_host, hdts_db_user, hdts_db_password, hdts_db_name);
    $conn->set_charset("utf8_mb4");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $sql = "INSERT INTO `".hdts_db_prefix."hdts_trace`(`id`, `trace_type`, `trace_location`,`trace_time`) VALUES (null,'".$type."','".$location."','".cDate()."')";
    $conn->query($sql);
}

function getViewsADay(){
    require_once "conf.php";
    $conn = new mysqli(hdts_db_host, hdts_db_user, hdts_db_password, hdts_db_name);
    $conn->set_charset("utf8_mb4");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $sql = "SELECT * FROM `".hdts_db_prefix."hdts_trace` WHERE `trace_type`='view' ";
    $result = $conn->query($sql);
    if($result->num_rows > 0){
        $output = array();
        while($row =$result->fetch_assoc()){
            $clm  =array();
            $time =  $row['trace_time'];
            $day = getDayOfTime($time);
            $output[] = $day;
        }
        $most = getMost($output);

        return $most;
    }else{
        return "0";
    }

}

function getViewsADayForChart(){
    require_once "conf.php";
    $conn = new mysqli(hdts_db_host, hdts_db_user, hdts_db_password, hdts_db_name);
    $conn->set_charset("utf8_mb4");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $sql = "SELECT * FROM `".hdts_db_prefix."hdts_trace` WHERE `trace_type`='view' ";
    $result = $conn->query($sql);
    if($result->num_rows > 0){
        $output = array();
        while($row =$result->fetch_assoc()){
            $clm  =array();
            $time =  $row['trace_time'];
            $day = getDayOfTime($time);
            $output[] = $day;
        }
        $most = getMostWithKey($output);

        return $most;
    }else{
        return "0";
    }

}

function getViewsAMonthForChart(){
    require_once "conf.php";
    $conn = new mysqli(hdts_db_host, hdts_db_user, hdts_db_password, hdts_db_name);
    $conn->set_charset("utf8_mb4");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $sql = "SELECT * FROM `".hdts_db_prefix."hdts_trace` WHERE `trace_type`='view' ";
    $result = $conn->query($sql);
    if($result->num_rows > 0){
        $output = array();
        while($row =$result->fetch_assoc()){
            $clm  =array();
            $time =  $row['trace_time'];
            $day = getMonthOfTime($time);
            $output[] = $day;
        }
        $most = getMostWithKey($output);

        return $most;
    }else{
        return "0";
    }

}

function getMessagesADayForChart(){
    require_once "conf.php";
    $conn = new mysqli(hdts_db_host, hdts_db_user, hdts_db_password, hdts_db_name);
    $conn->set_charset("utf8_mb4");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $sql = "SELECT * FROM `".hdts_db_prefix."hdts_trace` WHERE `trace_type`='msg' ";
    $result = $conn->query($sql);
    if($result->num_rows > 0){
        $output = array();
        while($row =$result->fetch_assoc()){
            $clm  =array();
            $time =  $row['trace_time'];
            $day = getDayOfTime($time);
            $output[] = $day;
        }
        $most = getMostWithKey($output);

        return $most;
    }else{
        return "0";
    }

}

function getMessagesAMonthForChart(){
    require_once "conf.php";
    $conn = new mysqli(hdts_db_host, hdts_db_user, hdts_db_password, hdts_db_name);
    $conn->set_charset("utf8_mb4");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $sql = "SELECT * FROM `".hdts_db_prefix."hdts_trace` WHERE `trace_type`='msg' ";
    $result = $conn->query($sql);
    if($result->num_rows > 0){
        $output = array();
        while($row =$result->fetch_assoc()){
            $clm  =array();
            $time =  $row['trace_time'];
            $day = getMonthOfTime($time);
            $output[] = $day;
        }
        $most = getMostWithKey($output);

        return $most;
    }else{
        return "0";
    }

}

function getViewsAMonth(){
    require_once "conf.php";
    $conn = new mysqli(hdts_db_host, hdts_db_user, hdts_db_password, hdts_db_name);
    $conn->set_charset("utf8_mb4");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $sql = "SELECT * FROM `".hdts_db_prefix."hdts_trace` WHERE `trace_type`='view' ";
    $result = $conn->query($sql);
    if($result->num_rows > 0){
        $output = array();
        while($row =$result->fetch_assoc()){
            $clm  =array();
            $time =  $row['trace_time'];
            $day = getMonthOfTime($time);
            $output[] = $day;
        }
        $most = getMost($output);

        return $most;
    }else{
        return "0";
    }

}

function getMessagesADay(){
    require_once "conf.php";
    $conn = new mysqli(hdts_db_host, hdts_db_user, hdts_db_password, hdts_db_name);
    $conn->set_charset("utf8_mb4");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $sql = "SELECT * FROM `".hdts_db_prefix."hdts_trace` WHERE `trace_type`='msg' ";
    $result = $conn->query($sql);
    if($result->num_rows > 0){
        $output = array();
        while($row =$result->fetch_assoc()){
            $clm  =array();
            $time =  $row['trace_time'];
            $day = getDayOfTime($time);
            $output[] = $day;
        }
        $most = getMost($output);

        return $most;
    }else{
        return "0";
    }

}

function getMessagesAMonth(){
    require_once "conf.php";
    $conn = new mysqli(hdts_db_host, hdts_db_user, hdts_db_password, hdts_db_name);
    $conn->set_charset("utf8_mb4");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $sql = "SELECT * FROM `".hdts_db_prefix."hdts_trace` WHERE `trace_type`='msg' ";
    $result = $conn->query($sql);
    if($result->num_rows > 0){
        $output = array();
        while($row =$result->fetch_assoc()){
            $clm  =array();
            $time =  $row['trace_time'];
            $day = getMonthOfTime($time);
            $output[] = $day;
        }
        $most = getMost($output);

        return $most;
    }else{
        return "0";
    }

}

function getViewsAndMessages(){
        $vad = getViewsADay();
        $vam = getViewsAMonth();
        $mad = getMessagesADay();
        $mam = getMessagesAMonth();
        $output = array("vad"=>$vad,"vam"=>$vam,"mad"=>$mad,"mam"=>$mam);
        echo json_encode($output,JSON_UNESCAPED_UNICODE);
}

function getViewsAndMessagesForChart(){
        $vad = getViewsADayForChart();
        $vam = getViewsAMonthForChart();
        $mad = getMessagesADayForChart();
        $mam = getMessagesAMonthForChart();
        $output = array("vad"=>$vad,"vam"=>$vam,"mad"=>$mad,"mam"=>$mam);
        echo json_encode($output,JSON_UNESCAPED_UNICODE);
}


function getDayOfTime($time){
    $dashPos = strpos($time,"-");
    $yearToMonth = substr($time,$dashPos+1,strlen($time));
    $dashDashPos = strpos($yearToMonth,"-");
    $MonthToDay = substr($yearToMonth,$dashDashPos+1,strlen($yearToMonth));
    $spacePos = strpos($MonthToDay," ");
    return substr($MonthToDay,0,$spacePos);
}
function getMonthOfTime($time){
    $dashPos = strpos($time,"-");
    $yearToMonth = substr($time,$dashPos+1,strlen($time));
    $dashDashPos = strpos($yearToMonth,"-");
    $month = substr($yearToMonth,0,$dashDashPos);
    return $month;
}

function getMost($time){
    $time;
    $timeCount = array();
    $values=array_count_values($time) ;
    $pre = "";
    $val = "";
    foreach ($values as $key=>$value) {
         $f =  $value;
         $timeCount[] = $f;
    }
    foreach ($timeCount as $t){
        if($pre != ""){
           if($t > $pre){
               $pre = $t;
           }
        }else{
            $pre = $t;
        }
        foreach ($values as $key=>$value) {
            $f =  $value;
            if($f == $pre){
                $val = $key;
            }
        }
    }
    return $pre;
}

function getMostWithKey($time){
    $timeCount = array();
    $values=array_count_values($time) ;
    foreach ($values as $key=>$value) {
         $f =  array("key"=>"$key","value"=>"$value");
         $timeCount[] = $f;
    }

    return $timeCount;
}

function sNotification($fFrom,$tTitle,$mMessage,$tType){
//    $from = isset($_REQUEST['from']) ? $_REQUEST['from'] : $fFrom;
//    $title = isset($_REQUEST['title']) ? $_REQUEST['title'] : $tTitle;
//    $msg = isset($_REQUEST['msg']) ? $_REQUEST['msg'] : $mMessage;
//    $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : $tType;
    $from = $fFrom;
    $title = $tTitle;
    $msg = $mMessage;
    $type =  $tType;

    require_once "conf.php";
    define( 'API_ACCESS_KEY'
        , 'AAAAvbBFfTQ:APA91bEZNJWbXKS9l6f9eU5oBFXj1Ahy5UO1MaDUXSsT8UHo8zXV7mboMCV0Ixj-_IgQzcT7idRFNNBHAvx7nks0CGQ38K5NyIEOXi4pC1KGp0gMUUpe9kZJ4eGPzZ1WTXOmuJM-pvtL');
    $conn = new mysqli(hdts_db_host, hdts_db_user, hdts_db_password, hdts_db_name);
    $conn->set_charset("utf8_mb4");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $sql = "SELECT * FROM `".hdts_db_prefix."hdts_admin`";
    $devices = $conn->query($sql);
    $allDevice = array();
    if($devices->num_rows > 0){
        while($row = $devices->fetch_assoc()){
            $device = $row['fcm_code'];
            $allDevice[] = $device;
        }
    }
    $url = 'https://fcm.googleapis.com/fcm/send';
    $data = array("registration_ids" => $allDevice,
        "data"=>array(
            "msg_title"=>$title,
            "msg"=>$msg,
            "msg_type"=>$type,
            "msg_from"=>$from
        )
    );
    $data_string = json_encode($data,JSON_UNESCAPED_UNICODE);
    $headers = array ( 'Authorization: key=' . API_ACCESS_KEY, 'Content-Type: application/json' );
    $ch = curl_init();
    curl_setopt( $ch,CURLOPT_URL,$url );
    curl_setopt( $ch,CURLOPT_POST, true );
    curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch,CURLOPT_POSTFIELDS, $data_string);
    $result = curl_exec($ch);
//    echo json_encode($result);
}

?>