<?php

//setup the forum head
function forumheader($location)
{
    echo "<div class='f-header'>
  <div class='f-logo'>
  <table width='100%' cellspacing='6'>
    <tr>
      <td align='left' valign='top'><a href='/forums'>" . T_("FORUM_WELCOME") . "</a></td>
      <td align='right' valign='top'><img src='images/forum/help.png'  alt='' />&nbsp;<a href='/faq'>" . T_("FORUM_FAQ") . "</a>&nbsp; &nbsp;&nbsp;<img src='images/forum/search.png' alt='' />&nbsp;<a href='/forums?action=search'>" . T_("SEARCH") . "</a></td>
    </tr>
    <tr>
      <td align='left' valign='bottom'>&nbsp;</td>
      <td align='right' valign='bottom'><b>" . T_("FORUM_CONTROL") . "</b> &middot; <a href='/forums?action=viewunread'>" . T_("FORUM_NEW_POSTS") . "</a> &middot; <a href='?catchup'>" . T_("FORUM_MARK_READ") . "</a></td>
    </tr>
  </table>
  </div>
</div>
<br />";
    print("<div class='f-location'><div class='f-nav'>" . T_("YOU_ARE_IN") . ": &nbsp;<a href='/forums'>" . T_("FORUMS") . "</a> <b style='vertical-align:middle'>/ $location</b></div></div>");
}

// Mark all forums as read
function catch_up()
{
    global $CURUSER;

    if (!$CURUSER) {
        return;
    }

    $userid = $CURUSER["id"];
    $res = DB::run("SELECT id, lastpost FROM forum_topics");
    while ($arr = $res->fetch(PDO::FETCH_ASSOC)) {
        $topicid = $arr["id"];
        $postid = $arr["lastpost"];
        $r = DB::run("SELECT id,lastpostread FROM forum_readposts WHERE userid=? and topicid=?", [$userid, $topicid]);
        if ($r->rowCount() == 0) {
            DB::run("INSERT INTO forum_readposts (userid, topicid, lastpostread) VALUES(?, ?, ?), [$userid, $topicid, $postid]");
        } else {
            $a = $r->fetch(PDO::FETCH_ASSOC);
            if ($a["lastpostread"] < $postid) {
                DB::run("UPDATE forum_readposts SET lastpostread=$postid WHERE id=?", [$a["id"]]);
            }

        }
    }
}

// Returns the minimum read/write class levels of a forum
function get_forum_access_levels($forumid)
{
    $res = DB::run("SELECT minclassread, minclasswrite FROM forum_forums WHERE id=?", [$forumid]);
    if ($res->rowCount() != 1) {
        return false;
    }

    $arr = $res->fetch(PDO::FETCH_ASSOC);
    return array("read" => $arr["minclassread"], "write" => $arr["minclasswrite"]);
}

// Returns the forum ID of a topic, or false on error
function get_topic_forum($topicid)
{
    $res = DB::run("SELECT forumid FROM forum_topics WHERE id=?", [$topicid]);
    if ($res->rowCount() != 1) {
        return false;
    }

    $arr = $res->fetch(PDO::FETCH_LAZY);
    return $arr[0];
}

// Returns the ID of the last post of a forum
function update_topic_last_post($topicid)
{
    $res = DB::run("SELECT id FROM forum_posts WHERE topicid=? ORDER BY id DESC LIMIT 1", [$topicid]);
    $arr = $res->fetch(PDO::FETCH_LAZY) or showerror(T_("FORUM_ERROR"), "No post found");
    $postid = $arr[0];
    DB::run("UPDATE forum_topics SET lastpost=? WHERE id=?", [$postid, $topicid]);
}

function get_forum_last_post($forumid)
{
    $res = DB::run("SELECT lastpost FROM forum_topics WHERE forumid=? ORDER BY lastpost DESC LIMIT 1", [$forumid]);
    $arr = $res->fetch(PDO::FETCH_LAZY);
    $postid = $arr[0];
    if ($postid) {
        return $postid;
    } else {
        return 0;
    }

}

//Top forum posts

function forumpostertable($res)
{
    print("<br /><table class='f-topten' width='160' cellspacing='0'><tr><td>\n");
    print("<table class='ttable_headinner' width='100%'>");

    ?>

    <tr class='ttable_head'>
      <th width='10' align='center'>
      <font size='1'><?php echo T_("FORUM_RANK"); ?></font>
      </th>
      <th width='140' align='center'>
      <font size='1'><?php echo T_("FORUM_USER"); ?></font>
      </th>
      <th width='10' align='center'>
      <font size='1'><?php echo T_("FORUM_POST"); ?></font>
      </th>
    </tr>

    <?php

    $num = 0;
    while ($a = $res->fetch(PDO::FETCH_ASSOC)) {
        ++$num;
        print("<tr class='t-row'><td align='center' class='ttable_col1'>$num</td><td class='ttable_col2' style='text-align: justify'><a href='/accountdetails?id=$a[id]'><b>$a[username]</b></a></td><td align='center' class='ttable_col1'>$a[num]</td></tr>\n");
    }

    if ($num == 0) {
        print("<tr class='t-row'><td align='center' class='ttable_col1' colspan='3'><b>No Forum Posters</b></td></tr>");
    }

    print("</table>");
    print("</td></tr></table>\n");
}

// Inserts a quick jump menu
function insert_quick_jump_menu($currentforum = 0)
{
    global $CURUSER;
    print("<div style='text-align:right'><form method='get' action='?' name='jump'>\n");
    print("<input type='hidden' name='action' value='viewforum' />\n");
    $res = DB::run("SELECT * FROM forum_forums ORDER BY name");

    if ($res->rowCount() > 0) {
        print(T_("FORUM_JUMP") . ": ");
        print("<select class='styled' name='forumid' onchange='if(this.options[this.selectedIndex].value != -1){ forms[jump].submit() }'>\n");

        while ($arr = $res->fetch(PDO::FETCH_ASSOC)) {
            if (get_user_class() >= $arr["minclassread"] || (!$CURUSER && $arr["guest_read"] == "yes")) {
                print("<option value='" . $arr["id"] . "'" . ($currentforum == $arr["id"] ? " selected='selected'>" : ">") . $arr["name"] . "</option>\n");
            }

        }

        print("</select>\n");
        print("<input type='submit' value='" . T_("GO") . "' />\n");
    }

    // print("<input type='submit' value='Go!'>\n");
    print("</form>\n</div>");
}

// Inserts a compose frame
function insert_compose_frame($id, $newtopic = true)
{
    global $maxsubjectlength;

    if ($newtopic) {
        $res = DB::run("SELECT name FROM forum_forums WHERE id=$id");
        $arr = $res->fetch(PDO::FETCH_ASSOC) or showerror(T_("FORUM_ERROR"), T_("FORUM_BAD_FORUM_ID"));
        $forumname = stripslashes($arr["name"]);

        print("<p align='center'><b>" . T_("FORUM_NEW_TOPIC") . " <a href='/forums?action=viewforum&amp;forumid=$id'>$forumname</a></b></p>\n");
    } else {
        $res = DB::run("SELECT * FROM forum_topics WHERE id=$id");
        $arr = $res->fetch(PDO::FETCH_ASSOC) or showerror(T_("FORUM_ERROR"), T_("FORUMS_NOT_FOUND_TOPIC"));
        $subject = stripslashes($arr["subject"]);
        print("<p align='center'>" . T_("FORUM_REPLY_TOPIC") . ": <a href='/forums?action=viewtopic&amp;topicid=$id'>$subject</a></p>");
    }

    # Language Marker #
    print("<p align='center'>" . T_("FORUM_RULES") . "\n");
    print("<br />" . T_("FORUM_RULES2") . "<br /></p>\n");

    #begin_frame("Compose Message", true);
    print("<fieldset class='download'>");
    print("<legend><b>Compose Message</b></legend>");
    print("<div>");
    print("<form name='Form' method='post' action='?action=post'>\n");
    if ($newtopic) {
        print("<input type='hidden' name='forumid' value='$id' />\n");
    } else {
        print("<input type='hidden' name='topicid' value='$id' />\n");
    }

    if ($newtopic) {
        print("<center><br /><table cellpadding='3' cellspacing='0'><tr><td><strong>Subject:</strong>  <input type='text' size='70' maxlength='$maxsubjectlength' name='subject' /></td></tr>");
        print("<tr><td align='center'>");
        textbbcode("Form", "body");
        print("</td></tr><tr><td align='center'><br /><input type='submit' value='" . T_("SUBMIT") . "' /><br /><br /></td></tr></table>
			");

    }
    print("<br /></center>");
    print("</form>\n");
    print("</div>");
    print("</fieldset><br />");
    #end_frame();

    insert_quick_jump_menu();
}

//LASTEST FORUM POSTS
function latestforumposts()
{
    print("<div class='f-border f-latestpost'><table width='100%' cellspacing='0'><tr class='f-title'>" .
        "<th align='left'  width=''>Latest Topic Title</th>" .
        "<th align='center' width='47'>Replies</th>" .
        "<th align='center' width='47'>Views</th>" .
        "<th align='center' width='85'>Author</th>" .
        "<th align='right' width='150'>Last Post</th>" .
        "</tr>");

/// HERE GOES THE QUERY TO RETRIEVE DATA FROM THE DATABASE AND WE START LOOPING ///
    $for = DB::run("SELECT * FROM forum_topics ORDER BY lastpost DESC LIMIT 5");

    if ($for->rowCount() == 0) {
        print("<tr class='f-row'><td class='alt1' align='center' colspan='5'><b>No Latest Topics</b></td></tr>");
    }

    while ($topicarr = $for->fetch(PDO::FETCH_ASSOC)) {
// Set minclass
        $res = DB::run("SELECT name,minclassread,guest_read FROM forum_forums WHERE id=$topicarr[forumid]");
        $forum = $res->fetch(PDO::FETCH_ASSOC);

        if ($forum && get_user_class() >= $forum["minclassread"] || $forum["guest_read"] == "yes") {
            $forumname = "<a href='?action=viewforum&amp;forumid=$topicarr[forumid]'><b>" . htmlspecialchars($forum["name"]) . "</b></a>";

            $topicid = $topicarr["id"];
            $topic_title = stripslashes($topicarr["subject"]);
            $topic_userid = $topicarr["userid"];
// Topic Views
            $views = $topicarr["views"];
// End

/// GETTING TOTAL NUMBER OF POSTS ///
            $res = DB::run("SELECT COUNT(*) FROM forum_posts WHERE topicid=?", [$topicid]);
            $arr = $res->fetch(PDO::FETCH_LAZY);
            $posts = $arr[0];
            $replies = max(0, $posts - 1);

/// GETTING USERID AND DATE OF LAST POST ///
            $res = DB::run("SELECT * FROM forum_posts WHERE topicid=? ORDER BY id DESC LIMIT 1", [$topicid]);
            $arr = $res->fetch(PDO::FETCH_ASSOC);
            $postid = 0 + $arr["id"];
            $userid = 0 + $arr["userid"];
            $added = utc_to_tz($arr["added"]);

/// GET NAME OF LAST POSTER ///
            $res = DB::run("SELECT id, username FROM users WHERE id=$userid");
            if ($res->rowCount() == 1) {
                $arr = $res->fetch(PDO::FETCH_ASSOC);
                $username = "<a href='/accountdetails?id=$userid'>" . class_user($arr['username']) . "</a>";
            } else {
                $username = "Unknown[$topic_userid]";
            }

/// GET NAME OF THE AUTHOR ///
            $res = DB::run("SELECT username FROM users WHERE id=?", [$topic_userid]);
            if ($res->rowCount() == 1) {
                $arr = $res->fetch(PDO::FETCH_ASSOC);
                $author = "<a href='/accountdetails?id=$topic_userid'>" . class_user($arr['username']) . "</a>";
            } else {
                $author = "Unknown[$topic_userid]";
            }

/// GETTING THE LAST INFO AND MAKE THE TABLE ROWS ///
            $r = DB::run("SELECT lastpostread FROM forum_readposts WHERE userid=$userid AND topicid=$topicid");
            $a = $r->fetch(PDO::FETCH_LAZY);
            $new = !$a || $postid > $a[0];
            $subject = "<a href='/forums?action=viewtopic&amp;topicid=$topicid'><b>" . stripslashes(encodehtml($topicarr["subject"])) . "</b></a>";

            print("<tr class='f-row'><td class='f-img' width='100%'>$subject</td>" .
                "<td class='alt2' align='center'>$replies</td>" .
                "<td class='alt3' align='center'>$views</td>" .
                "<td class='alt2' align='center'>$author</td>" .
                "<td class='alt3' align='right'><small>by&nbsp;$username<br /></small><small style='white-space: nowrap'>$added</small></td>");

            print("</tr>");
        } // while
    }
    print("</table></div><br />");
} // end function
