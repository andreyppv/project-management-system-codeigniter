<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all clients related functions
 */
class OAuth extends MY_Controller
{

    /**
     * constructor method
     */
    public function __construct()
    {

        parent::__construct();

        //profiling::
        $this->data['controller_profiling'][] = __function__;

        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'oauth.html';

        //css settings
        $this->data['vars']['css_menu_dashboard'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_dashboard'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-home"></i>';
        
    
    }

    /**
     * This is our re-routing function and is the inital function called
     * 
     */
    function index()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();


        $this->checkForOauth();

        $this->__flmView('admin/main');

    }


    function checkForOauth(){
        $client_id = "97_b164tzz0v88cgkw4cwc0gsg8gsk88ogg4scg4c04g0kgcwco8";
        $client_secret = "5ob1yb3nwxogcg4488wocwko8w8cc480kw44wc8owc88sowckw";

        $db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");
        $userid = intval($this->data['vars']['my_id']);
        if($userid == 0){
            /*Invalid*/
            die('You must be logged in to complete this action.');
        }

        if(!isset($_GET['code'])){
            /*Prompt with time doctor*/
            header("Location: https://webapi.timedoctor.com/oauth/v2/auth?client_id=".$client_id."&response_type=code&redirect_uri=http://pms.isodeveloper.com/admin/oauth");
        }else{
            /*Code is set we need to get auth token, and refresh token.*/
            $code = htmlentities($_GET['code']);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://webapi.timedoctor.com/oauth/v2/token?client_id=".$client_id."&client_secret=".$client_secret."&grant_type=authorization_code&redirect_uri=http://pms.isodeveloper.com/admin/oauth&code=" . $code);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = json_decode(curl_exec($ch), true);
            
            if(!empty($response['error'])){
                $error = $response['error'];
                switch ($error) {
                    case 'invalid_grant':
                        header("Location: https://webapi.timedoctor.com/oauth/v2/auth?client_id=".$client_id."&response_type=code&redirect_uri=http://pms.isodeveloper.com/admin/oauth");
                        break;
                    
                    default:
                        header("Location: https://webapi.timedoctor.com/oauth/v2/auth?client_id=".$client_id."&response_type=code&redirect_uri=http://pms.isodeveloper.com/admin/oauth");
                        break;
                }
            }else{
                $access_token = $response['access_token'];
                $expires_in = $response['expires_in'];
                $token_type = $response['token_type'];
                $refresh_token = $response['refresh_token'];
                
                $prepared = $db->prepare("SELECT `id` FROM oauth WHERE user_id = ?");
                $prepared->execute(array($userid));

                if($prepared->rowCount() == 0){
                    $prepared = $db->prepare("INSERT INTO oauth VALUES (NULL, ?, ?, ?, ?)");
                    $prepared->execute(array($access_token, $refresh_token, date("Y-m-d h:i:s", strtotime("+2 hours")), $userid));
                }else{
                    $row = $prepared->fetch(PDO::FETCH_ASSOC);
                    $prepared = $db->prepare("UPDATE oauth set access_token = ? WHERE id = ?");
                    $prepared->execute(array($access_token, $row['id']));
                    $prepared = $db->prepare("UPDATE oauth set refresh_token = ? WHERE id = ?");
                    $prepared->execute(array($refresh_token, $row['id']));
                    $prepared = $db->prepare("UPDATE oauth set expiresOn = ? WHERE id = ?");
                    $prepared->execute(array(date("Y-m-d h:i:s A", strtotime("+2 hours")), $row['id']));
                }
            }
        }
    }

    function __flmView($view = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //template::
        $this->data['template_file'] = help_verify_template($this->data['template_file']); //complete the view
        $this->__commonAll_View($view);
    }
}

