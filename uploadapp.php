<?php
/**
 *   https://09source.kicks-ass.net:8443/svn/installer09/
 *   Licence Info: GPL
 *   Copyright (C) 2010 Installer09 v.2
 *   A bittorrent tracker source based on TBDev.net/tbsource/bytemonsoon.
 *   Project Leaders: Mindless,putyn,kidvision.
 **/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'bittorrent.php');
require_once(INCL_DIR.'user_functions.php');
require_once INCL_DIR.'pager_functions.php';
dbconn(false);
loggedinorreturn();
$lang = array_merge( load_language('global'), load_language('uploadapp') );
$HTMLOUT = '';

// Fill in application
if (isset($_POST["form"]) != "1") {
    $res = sql_query("SELECT status FROM uploadapp WHERE userid = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
    $arr = mysql_fetch_assoc($res);
    if ($CURUSER['class'] >= UC_UPLOADER)
        stderr($lang['uploadapp_user_error'], $lang['uploadapp_alreadyup']);
    elseif ($arr['status'] == 'pending')
        stderr($lang['uploadapp_user_error'], $lang['uploadapp_pending']);
    elseif ($arr['status'] == 'rejected')
        stderr($lang['uploadapp_user_error'], $lang['uploadapp_rejected']);
    else {

        $HTMLOUT .="<h1 align='center'>{$lang['uploadapp_application']}</h1>
        <table width='750' border='1' cellspacing='0' cellpadding='10'><tr><td>
        <form action='{$INSTALLER09['baseurl']}/uploadapp.php' method='post' enctype='multipart/form-data'>
        <table border='1' cellspacing='0' cellpadding='5' align='center'>";

        if ($CURUSER['downloaded'] > 0)
            $ratio = $CURUSER['uploaded'] / $CURUSER['downloaded'];
        elseif ($CURUSER['uploaded'] > 0)
            $ratio = 1;
        else
            $ratio = 0;

        $res = sql_query("SELECT connectable FROM peers WHERE userid=$CURUSER[id]")or sqlerr(__FILE__, __LINE__);
        if ($row = mysql_fetch_row($res)) {
            $connect = $row[0];
            if ($connect == 'yes')
                $connectable = 'Yes';
            else
                $connectable = 'No';
        } else
            $connectable = 'Pending';
                                                                                                
        $HTMLOUT .="<tr>
        <td class='rowhead'>{$lang['uploadapp_username']}</td>
        <td><input name='userid' type='hidden' value='".$CURUSER['id'] ."' />".$CURUSER['username'] ."</td>
        </tr>
        <tr>
        <td class='rowhead'>{$lang['uploadapp_joined']}</td><td>".get_date($CURUSER['added'], '', 0, 1) ."</td>
        </tr>
        <tr>
        <td class='rowhead'>{$lang['uploadapp_ratio']}</td><td>".($ratio >= 1 ? 'Yes' : 'No') ."</td>
        </tr>
        <tr>
        <td class='rowhead'>{$lang['uploadapp_connectable']}</td><td><input name='connectable' type='hidden' value='$connectable' />$connectable</td>
        </tr>
        <tr>
        <td class='rowhead'>{$lang['uploadapp_upspeed']}</td><td><input type='text' name='speed' size='19' /></td>
        </tr>
        <tr>
        <td class='rowhead'>{$lang['uploadapp_offer']}</td><td><textarea name='offer' cols='80' rows='1'></textarea></td>
        </tr>
        <tr>
        <td class='rowhead'>{$lang['uploadapp_why']}</td><td><textarea name='reason' cols='80' rows='2'></textarea></td>
        </tr>
        <tr>
        <td class='rowhead'>{$lang['uploadapp_uploader']}</td><td><input type='radio' name='sites' value='yes' />{$lang['uploadapp_yes']}
       <input name='sites' type='radio' value='no' checked='checked' />{$lang['uploadapp_no']}</td>
       </tr>
        <tr>
        <td class='rowhead'>{$lang['uploadapp_sites']}</td><td><textarea name='sitenames' cols='80' rows='1'></textarea></td>
        </tr>
        <tr>
        <td class='rowhead'>{$lang['uploadapp_scene']}</td><td><input type='radio' name='scene' value='yes' />{$lang['uploadapp_yes']}
	   <input name='scene' type='radio' value='no' checked='checked' />{$lang['uploadapp_no']}</td>
       </tr>
        <tr>
        <td colspan='2'>
        <br />
        &nbsp;&nbsp;{$lang['uploadapp_create']}
        <br />
        <input type='radio' name='creating' value='yes' />{$lang['uploadapp_yes']}
    	<input name='creating' type='radio' value='no' checked='checked' />{$lang['uploadapp_no']}
        <br /><br />
        &nbsp;&nbsp;{$lang['uploadapp_seeding']}
        <br />
        <input type='radio' name='seeding' value='yes' />{$lang['uploadapp_yes']}
     	<input name='seeding' type='radio' value='no' checked='checked' />{$lang['uploadapp_no']}
        <br /><br />
        <input name='form' type='hidden' value='1' />
        
         
        <div align='center'><input type='submit' name='Submit' value='{$lang['uploadapp_send']}' /></div></td>
        </tr>
        </table></form>
        </td></tr></table>";
    }
    
    // Process application
} else {
    $app['userid'] = 0 + $_POST['userid'];
    $app['connectable'] = $_POST['connectable'];
    $app['speed'] = $_POST['speed'];
    $app['offer'] = $_POST['offer'];
    $app['reason'] = $_POST['reason'];
    $app['sites'] = $_POST['sites'];
    $app['sitenames'] = $_POST['sitenames'];
    $app['scene'] = $_POST['scene'];
    $app['creating'] = $_POST['creating'];
    $app['seeding'] = $_POST['seeding'];

    if (!is_valid_id($app['userid']))
        stderr($lang['uploadapp_error'], $lang['uploadapp_tryagain']);
    if (!$app['speed'])
        stderr($lang['uploadapp_error'], $lang['uploadapp_speedblank']);
    if (!$app['offer'])
        stderr($lang['uploadapp_error'], $lang['uploadapp_offerblank']);
    if (!$app['reason'])
    stderr($lang['uploadapp_error'], $lang['uploadapp_reasonblank']);
    if ($app['sites'] == 'yes' && !$app['sitenames'])
        stderr($lang['uploadapp_error'], $lang['uploadapp_sitesblank']);
    
    $res = sql_query("INSERT INTO uploadapp(userid,applied,connectable,speed,offer,reason,sites,sitenames,scene,creating,seeding) VALUES({$app['userid']}, ".implode(",", array_map("sqlesc", array(time(), $app['connectable'], $app['speed'], $app['offer'], $app['reason'], $app['sites'], $app['sitenames'], $app['scene'], $app['creating'], $app['seeding']))) .")") ;
    $mc1->delete_value('new_uploadapp_');
    if (!$res) {
        if (mysql_errno() == 1062)
            stderr($lang['uploadapp_error'], $lang['uploadapp_twice']);
        else
            stderr($lang['uploadapp_error'], $lang['uploadapp_tryagain']);
    } else {
        $subject = sqlesc("Uploader application");
        $msg = sqlesc("An uploader application has just been filled in by [url={$INSTALLER09['baseurl']}/userdetails.php?id=$CURUSER[id]][b]$CURUSER[username][/b][/url]. Click [url={$INSTALLER09['baseurl']}/uploadapps.php][b]Here[/b][/url] to go to the uploader applications page.");
        $dt = sqlesc(time());
        $subres = sql_query('SELECT id FROM users WHERE class = 6') or sqlerr(__FILE__, __LINE__);
        while ($arr = mysql_fetch_assoc($subres))
        sql_query("INSERT INTO messages(sender, receiver, added, msg, subject, poster) VALUES(0, $arr[id], $dt, $msg, $subject, 0)") or sqlerr(__FILE__, __LINE__);
        stderr('Application sent', 'Your application has succesfully been sent to the staff.');
        stderr($lang['uploadapp_appsent'], $lang['uploadapp_success']);
    }
}
print stdhead('Uploader application page') . $HTMLOUT . stdfoot();
?>