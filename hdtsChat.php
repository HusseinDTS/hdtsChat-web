<?php
/*
    Plugin Name: کام آرت چت
    Plugin URI: http://kamart.ir/
    Description: چت برای سایت وردپرس
    Version: 1.1.2
    Author: حسین دوستی فرد
    Author URI: http://kamart.ir/
*/
ini_set('display_errors', 0);
$cURL = $_SERVER['REQUEST_URI'];
$function = getFunctionName();
$adminp = "/wp-admin/";
$loginp = "/wp-login";
$wpp = "/wp-";


if($function != null){
    include_once 'model/php/function.php';
    if(function_exists($function)){
        echoJson($function());
        die;
    }
}
if(!strpos($cURL,$adminp) && !strpos($cURL,$loginp) && !is_admin()){
  add_action('wp_footer','footer');  
//  footer();
}



function create_chat_table()
{
    global $table_prefix, $wpdb;
        $sql = "CREATE TABLE IF NOT EXISTS `". $table_prefix . "hdts_chats` ( ";
        $sql .= "  `id`  int(11)   NOT NULL auto_increment, ";
        $sql .= "  `msg_from`  varchar(999)   NOT NULL, ";
        $sql .= "  `msg_to`  varchar(999)   NOT NULL, ";
        $sql .= "  `msg`  text(9999)   NOT NULL, ";
        $sql .= "  `msg_type`  varchar(4)   NOT NULL, ";
        $sql .= "  `msg_status`  varchar(7)   NOT NULL, ";
        $sql .= "  `msg_date`  varchar(1000)   NOT NULL, ";
        $sql .= "  `notify`  varchar(7)   NOT NULL, ";
        $sql .= "  PRIMARY KEY `order_id` (`id`) ";
        $sql .= ") ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1 ; ";
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);
}

function create_trace_table()
{
    global $table_prefix, $wpdb;
        $sql = "CREATE TABLE IF NOT EXISTS `". $table_prefix . "hdts_trace` ( ";
        $sql .= "  `id`  int(11)   NOT NULL auto_increment, ";
        $sql .= "  `trace_type`  varchar(11)   NOT NULL, ";
        $sql .= "  `trace_location`  varchar(999)   NOT NULL, ";
        $sql .= "  `trace_time`  varchar(999)   NOT NULL, ";
        $sql .= "  PRIMARY KEY `order_id` (`id`) ";
        $sql .= ") ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1 ; ";
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);
}

function create_users_table()
{
    global $table_prefix, $wpdb;
        $sql = "CREATE TABLE IF NOT EXISTS `". $table_prefix . "hdts_users` ( ";
        $sql .= "  `id`  int(11)   NOT NULL auto_increment, ";
        $sql .= "  `user_agent`  text(9999)   NOT NULL, ";
        $sql .= "  `user_ip`  varchar(999)   NOT NULL, ";
        $sql .= "  `user_location`  varchar(9999)   NOT NULL, ";
        $sql .= "  PRIMARY KEY `order_id` (`id`) ";
        $sql .= ") ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1 ; ";
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);
}

function create_admin_table()
{
    global $table_prefix, $wpdb;
        $sql = "CREATE TABLE IF NOT EXISTS `". $table_prefix . "hdts_admin` ( ";
        $sql .= "  `id`  int(11)   NOT NULL auto_increment, ";
        $sql .= "  `developer_name`  varchar(11)   NOT NULL, ";
        $sql .= "  `show_email`  varchar(9999)   NOT NULL, ";
        $sql .= "  `fcm_code`  text(9999)   NOT NULL, ";
        $sql .= "  PRIMARY KEY `order_id` (`id`) ";
        $sql .= ") ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1 ; ";

        $sqlq = "INSERT INTO `wp_hdts_admin`(`id`, `developer_name`, `show_email`, `fcm_code`) VALUES (null,'HusseinDTS','false','null') ";
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);
        dbDelta($sqlq);
}

function create_tables(){
    create_chat_table();
    create_admin_table();
    create_users_table();
    create_trace_table();

}
register_activation_hook( __FILE__, 'create_tables' );


function echoJson($result){
    echo json_encode($result , JSON_UNESCAPED_UNICODE);
}

function getFunctionName(){
    if(isset($_REQUEST["hdtc"])){
        return $_REQUEST["hdtc"];
    }else{
        return null;
    }
}
function footer(){
    require('pages/chat_btn.html');
}



function create()
{
    global $table_prefix, $wpdb;

    $tblname = 'pin';
    $wp_track_table = $table_prefix . "$tblname ";

    #Check to see if the table exists already, if not, then create it

    if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table) 
    {

        $sql = "CREATE TABLE `". $wp_track_table . "` ( ";
        $sql .= "  `id`  int(11)   NOT NULL auto_increment, ";
        $sql .= "  `pincode`  int(128)   NOT NULL, ";
        $sql .= "  PRIMARY KEY `order_id` (`id`) "; 
        $sql .= ") ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ; ";
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }
}

 register_activation_hook( __FILE__, 'create_plugin_database_table' );



?>