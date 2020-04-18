<?php
  class Accountrecover extends Controller {
    
    public function __construct(){
        // $this->userModel = $this->model('User');
    }
    
    public function index(){
		// Set Current User
		// $curuser = $this->userModel->setCurrentUser();
		// Set Current User
		// $db = new Database;
dbconn();
global $site_config, $CURUSER;
$kind = '0';

if (is_valid_id($_POST["id"]) && strlen($_POST["secret"]) == 32) {
    $password = $_POST["password"];
    $password1 = $_POST["password1"];
    if (empty($password) || empty($password1)) {
        $kind = T_("ERROR");
        $msg =  T_("NO_EMPTY_FIELDS");
    } elseif ($password != $password1) {
        $kind = T_("ERROR");
        $msg = T_("PASSWORD_NO_MATCH");
    } else {
	$n = get_row_count("users", "WHERE `id`=".intval($_POST["id"])." AND MD5(`secret`) = ".sqlesc($_POST["secret"]));
	if ($n != 1)
		show_error_msg(T_("ERROR"), T_("NO_SUCH_USER"));
        $newsec = mksecret();
        $wantpassword = password_hash($password, PASSWORD_BCRYPT);
        DB::run("UPDATE `users` SET `password` =?, `secret` =? WHERE `id`=? AND secret =?", [$wantpassword, $newsec, $_POST['id'], $_POST["secret"]]);
        $kind = T_("SUCCESS");
        $msg =  T_("PASSWORD_CHANGED_OK");
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_GET["take"] == 1) {
    $email = trim($_POST["email"]);

    if (!validemail($email)) {
        $msg = T_("EMAIL_ADDRESS_NOT_VAILD");
        $kind = T_("ERROR");
    }else{
        $arr = DB::run("SELECT id, username, email FROM users WHERE email=? LIMIT 1", [$email])->fetch();
        if (!$arr) {
            $msg = T_("EMAIL_ADDRESS_NOT_FOUND");
            $kind = T_("ERROR");
        }

        if ($arr) {
              $sec = mksecret();
            $secmd5 = md5($sec);
            $id = $arr['id'];

            $body = T_("SOMEONE_FROM")." " . $_SERVER["REMOTE_ADDR"] . " ".T_("MAILED_BACK")." ($email) ".T_("BE_MAILED_BACK")." \r\n\r\n ".T_("ACCOUNT_INFO")." \r\n\r\n ".T_("USERNAME").": ".class_user($arr["username"])." \r\n ".T_("CHANGE_PSW")."\n\n$site_config[SITEURL]/accountrecover?id=$id&secret=$secmd5\n\n\n".$site_config["SITENAME"]."\r\n";
            
            @sendmail($arr["email"], T_("ACCOUNT_DETAILS"), $body, "", "-f".$site_config['SITEEMAIL']);
            $res2 =DB::run("UPDATE `users` SET `secret` =? WHERE `email`=? LIMIT 1", [$sec, $email]);
            $msg = sprintf(T_('MAIL_RECOVER'), htmlspecialchars($email));
            $kind = T_("SUCCESS");
        }
    }
}

stdhead();

begin_frame(T_("RECOVER_ACCOUNT"));
if ($kind != "0") {
    show_error_msg("Notice", "$kind: $msg", 0);
}

if (is_valid_id($_GET["id"]) && strlen($_GET["secret"]) == 32) {?>
<form method="post" action="/accountrecover">
<table border="0" cellspacing="0" cellpadding="5">
    <tr>
        <td>
            <b><?php echo T_("NEW_PASSWORD"); ?></b>:
        </td>
        <td>
            <input type="hidden" name="secret" value="<?php echo $_GET['secret']; ?>" />
            <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>" />
            <input type="password" size="40" name="password" />
        </td>
    </tr>
    <tr>
        <td>
            <b><?php echo T_("REPEAT"); ?></b>:
        </td>
        <td>
            <input type="password" size="40" name="password1" />
        </td>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td><input type="submit" value="<?php echo T_("SUBMIT"); ?>" /></td>
    </tr>
</table>
</form>
<?php } else { echo T_("USE_FORM_FOR_ACCOUNT_DETAILS"); ?>

<form method="post" action="/accountrecover?take=1">
    <table border="0" cellspacing="0" cellpadding="5">
        <tr>
            <td><b><?php echo T_("EMAIL_ADDRESS"); ?>:</b></td>
            <td><input type="text" size="40" name="email" />&nbsp;<input type="submit" value="<?php echo T_("SUBMIT");?>" /></td>
        </tr>
    </table>
</form>

<?php
}
end_frame();
stdfoot();
    }
}