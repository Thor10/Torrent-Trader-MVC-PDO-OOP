<?php
  class Taketheme extends Controller {
    
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
 loggedinonly();
 
 $updateset = array();
 
 $stylesheet = $_POST['stylesheet'];
 $language = $_POST['language'];
 
 if (is_valid_id($stylesheet))
     $updateset[] = "stylesheet = '$stylesheet'";
 if (is_valid_id($language))
     $updateset[] = "language = '$language'";

 if (count($updateset))
     DB::run("UPDATE `users` SET " . implode(', ', $updateset) . " WHERE `id` =?", [$CURUSER["id"]]);
 
 if (empty($_SERVER["HTTP_REFERER"]))
 {
     header("Location: index.php"); 
     return;
 }     
 
 header("Location: " . $_SERVER["HTTP_REFERER"]);
}
}