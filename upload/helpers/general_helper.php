<?php
// Function To Display Error Messages taken from forum
function showerror($heading = "Error", $text, $sort = "Error")
{
    stdhead("$sort: $heading");
    begin_frame("<font class='error'>$sort: $heading</font>");
    echo $text;
    end_frame();
    stdfoot();
    die;
}

// Function To Display Error Messages
function show_error_msg($title, $message, $wrapper = "1")
{
    if ($wrapper) {
        ob_start();
        ob_clean();
        stdhead($title);
    }
    begin_frame("<font class='error'>" . htmlspecialchars($title) . "</font>");
    print("<center><b>" . $message . "</b></center>\n");
    end_frame();

    if ($wrapper) {
        stdfoot();
        die();
    }
}

// Function To Count A Data Established In A Data Table
function get_row_count($table, $suffix = "")
{
    $suffix = !empty($suffix) ? ' ' . $suffix : '';
    $row = DB::run("SELECT COUNT(*) FROM $table $suffix")->fetchColumn();
    return $row;
}

function array_map_recursive($callback, $array)
{
    $ret = array();
    if (!is_array($array)) {
        return $callback($array);
    }

    foreach ($array as $key => $val) {
        $ret[$key] = array_map_recursive($callback, $val);
    }
    return $ret;
}

// Automatic Original Redirection Function
function autolink($al_url, $al_msg)
{
    stdhead();
    begin_frame("");
    echo "\n<meta http-equiv=\"refresh\" content=\"3; url=$al_url\">\n";
    echo "<b>$al_msg</b>\n";
    echo "\n<b>Redirecting ...</b>\n";
    echo "\n[ <a href='$al_url'>link</a> ]\n";
    end_frame();
    stdfoot();
    exit;
}

function write_log($text)
{
    $text = $text;
    $added = get_date_time();
    DB::run("INSERT INTO log (added, txt) VALUES (?,?)", [$added, $text]);
}

/// each() replacement for php 7+. Change all instances of each() to thisEach() in all TT files. each() deprecated as of 7.2
function thisEach(&$arr)
{
    $key = key($arr);
    $result = ($key === null) ? false : [$key, current($arr), 'key' => $key, 'value' => current($arr)];
    next($arr);
    return $result;
}

function mksize($s, $precision = 2)
{
    $suf = array("B", "kB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB");

    for ($i = 1, $x = 0; $i <= count($suf); $i++, $x++) {
        if ($s < pow(1024, $i) || $i == count($suf)) // Change 1024 to 1000 if you want 0.98GB instead of 1,0000MB
        {
            return number_format($s / pow(1024, $x), $precision) . " " . $suf[$x];
        }

    }
}

function CutName($vTxt, $Car)
{
    if (strlen($vTxt) > $Car) {
        return substr($vTxt, 0, $Car) . "...";
    }
    return $vTxt;
}

function searchfield($s)
{
    return preg_replace(array('/[^a-z0-9]/si', '/^\s*/s', '/\s*$/s', '/\s+/s'), array(" ", "", "", " "), $s);
}

function strtobytes($str)
{
    $str = trim($str);
    if (!preg_match('!^([\d\.]+)\s*(\w\w)?$!', $str, $matches)) {
        return 0;
    }

    $num = $matches[1];
    $suffix = strtolower($matches[2]);
    switch ($suffix) {
        case "tb": // TeraByte
            return $num * 1099511627776;
        case "gb": // GigaByte
            return $num * 1073741824;
        case "mb": // MegaByte
            return $num * 1048576;
        case "kb": // KiloByte
            return $num * 1024;
        case "b": // Byte
        default:
            return $num;
    }
}

function navmenu()
{
    ?>
        <br />
          <div class="f-border">
            <table cellpadding='0' cellspacing='3' width='100%'>
            <tr class="f-title">
            <th width='100%' height="32" align='center'>
            <?php print("<a href='/account'><b>" . T_("YOUR_PROFILE") . "</b></a>");?>
            &nbsp;|&nbsp;
            <?php print("<a href='/account?action=edit_settings&amp;do=edit'><b>" . T_("YOUR_SETTINGS") . "</b></a>");?>
            &nbsp;|&nbsp;
            <?php print("<a href='/account?action=changepw'><b>" . T_("CHANGE_PASS") . "</b></a>");?>
            &nbsp;|&nbsp;
            <?php print("<a href='/account?action=mytorrents'><b>" . T_("YOUR_TORRENTS") . "</b></a>");?>
            &nbsp;|&nbsp;
            <?php print("<a href='/mailbox'><b>" . T_("YOUR_MESSAGES") . "</b></a>");?>
             &nbsp;|&nbsp;
            <?php print("<a href='/snatched'><b>" . T_("YOUR_SNATCHLIST") . "</b></a>");?>
            </th>
            </tr>
            </table>
          </div>
        <br />
        <?php
} //end func

function navmenuu()
{
    ?>
        <br />
          <div class="f-border">
            <table cellpadding='0' cellspacing='3' width='100%'>
            <tr class="f-title">
            <th width='100%' height="32" align='center'>
            <?php print("<a href='/account'><b>" . T_("YOUR_PROFILE") . "</b></a>");?>
            &nbsp;|&nbsp;
            <?php print("<a href='/account?action=edit_settings&amp;do=edit'><b>" . T_("YOUR_SETTINGS") . "</b></a>");?>
            &nbsp;|&nbsp;
            <?php print("<a href='/account?action=changepw'><b>" . T_("CHANGE_PASS") . "</b></a>");?>
            &nbsp;|&nbsp;
            <?php print("<a href='/account?action=mytorrents'><b>" . T_("YOUR_TORRENTS") . "</b></a>");?>
            &nbsp;|&nbsp;
            <?php print("<a href='/mailbox'><b>" . T_("YOUR_MESSAGES") . "</b></a>");?>
               </th>
            </tr>
            </table>
          </div>
        <!--<br />-->
        <?php
} //end func

function uploadimage($x, $imgname, $tid)
{
    global $site_config;

    $imagesdir = $site_config["torrent_dir"] . "/images";

    $allowed_types = &$site_config["allowed_image_types"];

    if (!($_FILES["image$x"]["name"] == "")) {
        if ($imgname != "") {
            $img = "$imagesdir/$imgname";
            $del = unlink($img);
        }

        $y = $x + 1;

        $im = getimagesize($_FILES["image$x"]["tmp_name"]);

        if (!$im[2]) {
            show_error_msg(T_("ERROR"), "Invalid Image $y.", 1);
        }

        if (!array_key_exists($im['mime'], $allowed_types)) {
            show_error_msg(T_("ERROR"), T_("INVALID_FILETYPE_IMAGE"), 1);
        }

        if ($_FILES["image$x"]["size"] > $site_config['image_max_filesize']) {
            show_error_msg(T_("ERROR"), sprintf(T_("INVAILD_FILE_SIZE_IMAGE"), $y), 1);
        }

        $uploaddir = "$imagesdir/";

        $ifilename = $tid . $x . $allowed_types[$im['mime']];

        $copy = copy($_FILES["image$x"]["tmp_name"], $uploaddir . $ifilename);

        if (!$copy) {
            show_error_msg(T_("ERROR"), sprintf(T_("ERROR_UPLOADING_IMAGE"), $y), 1);
        }

        return $ifilename;
    }
} //end func
