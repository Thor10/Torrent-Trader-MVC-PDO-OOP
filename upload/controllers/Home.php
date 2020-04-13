<?php
  class Home extends Controller {
    
    public function __construct(){
        // $this->userModel = $this->model('User');
    }
    
    public function index(){
		// Set Current User
		// $curuser = $this->userModel->setCurrentUser();
		// Set Current User
		// $db = new Database;
dbconn(true);
global $site_config, $CURUSER;
loggedinonly ();
stdhead(T_("HOME"));

//check
if (file_exists("/check") && $CURUSER["class"] == 7){
	show_error_msg("WARNING", "/check still exists, please delete or rename the file as it could pose a security risk<br /><br /><a href='/check'>View /check</a> - Use to check your config!<br /><br />",0);
}
//intro
if (file_exists("pdointro.php") && $CURUSER["class"] == 7){
	show_error_msg("UPDATE", "Any mods must be PDO to work here is a page to assist<br /><br /><a href='pdointro.php'>Guide to update old mods!</a> - !<br /><br />",0);
}

//Site Notice
if ($site_config['SITENOTICEON']){
	begin_frame(T_("NOTICE"));
	echo $site_config['SITENOTICE'];
	end_frame();
}

//Site News
if ($site_config['NEWSON'] && $CURUSER['view_news'] == "yes"){
	begin_frame(T_("NEWS"));
	$res = DB::run("SELECT news.id, news.title, news.added, news.body, users.username FROM news LEFT JOIN users ON news.userid = users.id ORDER BY added DESC LIMIT 10");
	if ($res->rowCount() > 0){
		print("<table width='100%' border='0' cellspacing='0' cellpadding='0'><tr><td>\n<ul>");
		$news_flag = 0;

		while($array = $res->fetch(PDO::FETCH_LAZY)){

			if (!$array["username"])
				$array["username"] = T_('UNKNOWN_USER');

			$numcomm = get_row_count("comments", "WHERE news='".$array['id']."'");

			// Show first 2 items expanded
			if ($news_flag < 2) {
				$disp = "block";
				$pic = "minus";
			} else {
				$disp = "none";
				$pic = "plus";
			}

			print("<br /><a href=\"javascript: klappe_news('a".$array['id']."')\"><img border=\"0\" src=\"".$site_config["SITEURL"]."/images/$pic.gif\" id=\"pica".$array['id']."\" alt=\"Show/Hide\" />");
			print("&nbsp;<b>". $array['title'] . "</b></a> - <b>".T_("POSTED").":</b> " . date("d-M-y", utc_to_tz_time($array['added'])) . " <b>".T_("BY").":</b> ".class_user($array['username'])."");

			print("<div id=\"ka".$array['id']."\" style=\"display: $disp;\"> ".format_comment($array["body"])." <br /><br />".T_("COMMENTS")." (<a href='/comments?type=news&amp;id=".$array['id']."'>".number_format($numcomm)."</a>)</div><br /> ");

			$news_flag++;
		}
		print("</ul></td></tr></table>\n");
	}else{
		echo "<br /><b>".T_("NO_NEWS")."</b>";
	}
	end_frame();
}



if ($site_config['SHOUTBOX'] && !($CURUSER['hideshoutbox'] == 'yes')){
	begin_frame(T_("SHOUTBOX"));
	echo '<iframe name="shout_frame" src="/shoutbox" frameborder="0" marginheight="0" marginwidth="0" width="99%" height="210" scrolling="no" align="middle"></iframe>';
	printf(T_("SHOUTBOX_REFRESH"), 5)."<br />";
	end_frame();
}

// latest torrents
begin_frame(T_("LATEST_TORRENTS"));

print("<br /><center><a href='torrentsmain'>".T_("BROWSE_TORRENTS")."</a> - <a href='torrentssearch'>".T_("SEARCH_TORRENTS")."</a></center><br />");

if ($site_config["MEMBERSONLY"] && !$CURUSER) {
	echo "<br /><br /><center><b>".T_("BROWSE_MEMBERS_ONLY")."</b></center><br /><br />";
} else {
	$query = "SELECT torrents.id, torrents.anon, torrents.announce, torrents.category, torrents.leechers, torrents.nfo, torrents.seeders, torrents.name, torrents.times_completed, torrents.size, torrents.added, torrents.comments, torrents.numfiles, torrents.filename, torrents.owner, torrents.external, torrents.freeleech, categories.name AS cat_name, categories.image AS cat_pic, categories.parent_cat AS cat_parent, users.username, users.privacy, IF(torrents.numratings < 2, NULL, ROUND(torrents.ratingsum / torrents.numratings, 1)) AS rating FROM torrents LEFT JOIN categories ON category = categories.id LEFT JOIN users ON torrents.owner = users.id WHERE visible = 'yes' AND banned = 'no' ORDER BY id DESC LIMIT 25";
	$res = DB::run($query);
	if ($res->rowCount()) {
		torrenttable($res);
	}else {

		print("<div class='f-border'>");
		print("<div class='f-cat' width='100%'>".T_("NOTHING_FOUND")."</div>");
		print("<div>");
		print T_("NO_UPLOADS");
		print("</div>");
		print("</div>");

	}
	if ($CURUSER)
		DB::run("UPDATE users SET last_browse=".gmtime()." WHERE id=?", [$CURUSER['id']]);

}
end_frame();


if ($site_config['DISCLAIMERON']){
	begin_frame(T_("DISCLAIMER"));
	echo T_("DISCLAIMERTXT");
	end_frame();
}

stdfoot();
	}
}