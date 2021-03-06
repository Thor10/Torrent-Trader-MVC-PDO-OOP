<?php
  class Teamscreate extends Controller {
    
    public function __construct(){
        // $this->userModel = $this->model('User');
    }
    
    public function index(){
		// Set Current User
		// $curuser = $this->userModel->setCurrentUser();
		// Set Current User
		// $db = new Database;
require_once ("helpers/bbcode_helper.php");
dbconn();
global $site_config, $CURUSER;
loggedinonly();

# Todo: Clean this shit up, move to admincp

if (!$CURUSER || $CURUSER["control_panel"]!="yes"){
	 show_error_msg(T_("ERROR"), T_("SORRY_NO_RIGHTS_TO_ACCESS"), 1);
}


$sure = $_GET['sure'];
$del = $_GET['del'];
$team = htmlspecialchars($_GET['team']);
$edited = (int)$_GET['edited'];
$id = (int)$_GET['id'];
$team_name = $_GET['team_name'];
$team_info = $_GET['team_info'];
$team_image = $_GET['team_image'];
$team_description = $_GET['team_description'];
$teamownername = $_GET['team_owner'];
$editid = $_GET['editid'];
$editmembers = $_GET['editmembers'];
$name = $_GET['name'];
$image = $_GET['image'];
$owner = $_GET['owner'];
$info = $_GET['info'];
$add = $_GET['add'];
$added = get_date_time();


stdhead(T_("TEAMS"));
begin_frame(T_("TEAMS_MANAGEMENT"));


//Delete Team
if($sure == "yes") {
	
	$sql = DB::run("UPDATE users SET team=? WHERE team=?", ['0', $del]);

	$sql = DB::run("DELETE FROM teams WHERE id=? LIMIT 1", [$del]);
	echo("Team Successfully Deleted![<a href='teamscreate'>Back</a>]");
	write_log($CURUSER['username']." has deleted team id:$del");
	end_frame();
	stdfoot();
	die();
}

if($del > 0) {
	echo("You and in the truth wish to delete team? ($team) ( <b><a href='teamscreate?del=$del&amp;team=$team&amp;sure=yes'>Yes!</a></b> / <b><a href='teamscreate'>No!</a></b> )");
	end_frame();
	stdfoot();
	die();
}

//Edit Team
if($edited == 1) {
    
    if (!$team_name || !$teamownername|| !$team_info) {
         print 'One or more fields left empty.';
         end_frame();
         stdfoot();
         die;
    }
    
    $team_name = $team_name;
    $team_image = $team_image;
    $teamownername = $teamownername;
    $team_info = $team_info;
    
	$aa = DB::run("SELECT class, id FROM users WHERE username=?", [$teamownername]);
	$ar = $aa->fetch(PDO::FETCH_ASSOC);
	$team_owner = $ar["id"];
	$sql = DB::run("UPDATE teams SET name =?, info =?, owner =?, image =?  WHERE id=?", [$team_name, $team_info, $team_owner, $team_image,$id]);
    DB::run("UPDATE users SET team =? WHERE id=?", [$id, $team_owner]);

	if($sql) {
		echo("<table cellspacing='0' cellpadding='5' width='50%'>");
		echo("<tr><td><b>Successfully Edited</b>[<a href='teamscreate'>Back</a>]</tr>");
		echo("</table>");
		write_log($CURUSER['username']." has edited team ($team_name)");
		end_frame();
		stdfoot();
		die();
	}
}

if($editid > 0) {
	echo("<form name='smolf3d' method='get' action='teamscreate'>");
    echo("<input type='hidden' name='id' value='$editid' />");
    echo("<input type='hidden' name='edited' value='1' />");   
	echo("<table cellspacing='0' cellpadding='5' width='50%'>");
	echo("<tr><td>".T_("TEAM_NAME").": </td><td align='right'><input type='text' size='50' name='team_name' value='$name' /></td></tr>");
	echo("<tr><td>".T_("TEAM_LOGO_URL").": </td><td align='right'><input type='text' size='50' name='team_image' value='$image' /></td></tr>");
	echo("<tr><td>".T_("TEAM_OWNER_NAME").": </td><td align='right'><input type='text' size='50' name='team_owner' value='$owner' /></td></tr>");
	echo("<tr><td valign='top'>".T_("DESCRIPTION").": </td><td align='right'><textarea name='team_info' cols='35' rows='5'>$info</textarea><br />(BBCode is allowed)</td></tr>");
	echo("<tr><td></td><td><div align='right'><input type='submit' value='Update' /></div></td></tr>");
	echo("</table></form>");
	end_frame();
	stdfoot();
	die();
}

//View Members
if($editmembers > 0) {
	echo("<center><table class='table_table' cellspacing='0' align='center'><tr>");
	echo("<th class='table_head'>Username</th><td class='table_head'>".T_("UPLOADED").": </th><th class='table_head'>Downloaded</th></tr>");
	$sql = DB::run("SELECT id,username,uploaded,downloaded FROM users WHERE team=$editmembers");
	while ($row = $sql->fetch(PDO::FETCH_LAZY)) {
		$username = htmlspecialchars($row['username']);
		$uploaded = mksize($row['uploaded']);
		$downloaded = mksize($row['downloaded']);
		
		echo("<tr><td class='table_col1'><a href='/accountdetails?id=$row[id]'>$username</a></td><td class='table_col2'>$uploaded</td><td class='table_col1'>$downloaded</td></tr>");
	}
	echo "</table></center>";
	end_frame();
	stdfoot();
	die();
}


//Add Team
if($add == 'true') {
    
    if (!$team_name || !$teamownername|| !$team_description) {
         print 'One or more fields left empty.';
         end_frame();
         stdfoot();
         die;
    }
    
    $team_name = $team_name;
    $team_description = $team_description;
    $team_image = $team_image;
    $teamownername = $teamownername;
    
	$aa = DB::run("SELECT id FROM users WHERE username =?", [$teamownername]);
	$ar = $aa->fetch(PDO::FETCH_ASSOC);
	$team_owner = $ar["id"];
    
    if ( !$team_owner )
    {
          print 'This user does not exist.';
          end_frame();
          stdfoot();
          die;
    }

	$sql = DB::run("INSERT INTO teams SET name =?, owner =?, info =?, image =?, added =?", [$team_name, $team_owner, $team_description, $team_image, $added]);
    $tid = DB::lastInsertId();

    DB::run("UPDATE users SET team = $tid WHERE id= $team_owner");

/*
	if($sql) {
		write_log($CURUSER['username']." has created new team ($team_name)");
		$success = TRUE;
	}else{
		$success = FALSE;
	}*/
}

print("<b>Add new team:</b>");
print("<br />");
print("<br />");
echo("<form name='smolf3d' method='get' action='teamscreate'>");
echo("<center><table cellspacing='0' cellpadding='5' width='50%'>");
echo("<tr><td>".T_("TEAM").": </td><td align='left'><input type='text' size='50' name='team_name' /></td></tr>");
echo("<tr><td>".T_("TEAM_OWNER_NAME").": </td><td align='left'><input type='text' size='50' name='team_owner' /></td></tr>");
echo("<tr><td valign='top'>".T_("DESCRIPTION").": </td><td align='left'><textarea name='team_description' cols='35' rows='5'></textarea><br />(BBCode is allowed)</td></tr>");
echo("<tr><td>".T_("TEAM_LOGO_URL").": </td><td align='left'><input type='text' size='50' name='team_image' /><input type='hidden' name='add' value='true' /></td></tr>");
echo("<tr><td></td><td><div align='left'><button type='submit' class='btn btn-primary btn-sm'>".T_("TEAM_CREATE")."</button></div></td></tr>");
echo("</table></center>");
if($success == TRUE) {
	print("<b>team successfully added!</b>");
}
echo("<br />");
echo("</form>");

//ELSE Display ".T_("TEAMS")."
print("<b>Current ".T_("TEAMS").":</b>");
print("<br />");
print("<br />");
echo("<center><div class='table-responsive'><table class='table table-striped'>
<thead><tr>");
echo("<th>ID</th><th>".T_("TEAM_LOGO")."</th><th>".T_("TEAM_NAME")."</th><th>".T_("TEAM_OWNER_NAME")."</th><th>".T_("DESCRIPTION")."</th><th>".T_("OTHER")."</th></tr></thead>");
$sql = DB::run( "SELECT * FROM teams");
while ($row = $sql->fetch(PDO::FETCH_LAZY)) {
	$id = (int)$row['id'];
	$name = htmlspecialchars($row['name']);
	$image = htmlspecialchars($row['image']);
	$owner = (int)$row['owner'];
	$info = format_comment($row['info']);
	$OWNERNAME2 = DB::run("SELECT username, class FROM users WHERE id=$owner")->fetch();
	$OWNERNAME = class_user($OWNERNAME2['username']);

	echo("<tbody><tr><td><b>$id</b> </td> <td><img src='$image' alt='' /></td> <td><b>$name</b></td><td><a href='/accountdetails?id=$owner'>$OWNERNAME</a></td><td>$info</td><td><a href='teamscreate?editmembers=$id'>[Members]</a>&nbsp;<a href='teamscreate?editid=$id&amp;name=$name&amp;image=$image&amp;info=$info&amp;owner=$OWNERNAME'>[".T_("EDIT")."]</a>&nbsp;<a href='teamscreate?del=$id&amp;team=$name'>[Delete]</a></td></tr></tbody>");
}
echo "</table></center>";

end_frame();
stdfoot();
}
}