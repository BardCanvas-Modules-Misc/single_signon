<?php
namespace hng2_modules\single_signon;

use hng2_base\account;
use hng2_base\accounts_repository;
use hng2_base\device;

class google_toolbox
{
    public $client_id;
    public $client_secret;
    public $redirect_uri;
    public $oauth_version;
    
    public $scope = "https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile";
    
    /**
     * @var accounts_repository
     */
    public $repo;
    
    public function __construct()
    {
        global $settings, $modules;
        $current_module = $modules["single_signon"];
        
        $this->client_id     = $settings->get("modules:single_signon.google_client_id");
        $this->client_secret = $settings->get("modules:single_signon.google_client_secret");
        $this->redirect_uri  = $current_module->get_url(true) . "/scripts/google.php";
        $this->oauth_version = "v3";
        
        $this->repo = new accounts_repository();
    }
    
    /**
     * @return string
     */
    public function get_google_redirect_url()
    {
        $params = array(
            'response_type' => 'code',
            'client_id'     => $this->client_id,
            'redirect_uri'  => $this->redirect_uri,
            'scope'         => $this->scope,
            'access_type'   => 'offline',
            'prompt'        => 'consent',
        );
        
        return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
    }
    
    /**
     * @param string $auth_code
     * 
     * @return string
     * @throws \Exception
     */
    public function get_access_token($auth_code)
    {
        // Execute cURL request to retrieve the access token
        $params = array(
            'code'          => $auth_code,
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri'  => $this->redirect_uri,
            'grant_type'    => 'authorization_code',
        );
        
        try
        {
            $res = $this->fetch('https://accounts.google.com/o/oauth2/token', "POST", $params);
        }
        catch(\Exception $e)
        {
            throw new \Exception("Unable to retrieve access token: {$e->getMessage()}");
        }
        
        if( empty($res["access_token"]) )
            throw new \Exception("Missing access token in Google API response");
        
        return $res["access_token"];
    }
    
    /**
     * @param string $access_token
     *
     * @return array = [
     *     "name"    => "Some name",
     *     "email"   => "some@email.com",
     *     "picture" => "https://some/pic.png",
     *     "token"   => "xxxxxxxxxxx"
     * ]
     * @throws \Exception
     */
    public function get_user_profile($access_token)
    {
        $url     = "https://www.googleapis.com/oauth2/{$this->oauth_version}/userinfo";
        $headers = array("Authorization: Bearer {$access_token}");
        
        try
        {
            $profile = $this->fetch($url, "get", array(), $headers);
        }
        catch(\Exception $e)
        {
            throw new \Exception("Unable to fetch user profile: {$e->getMessage()}");
        }
        
        if( empty($profile['email']) )
            throw new \Exception("Google didn't provide the account email");
        
        $google_name_parts   = array();
        $google_name_parts[] = isset($profile['given_name'])  ? preg_replace('/[^a-zA-Z0-9]/s', '', $profile['given_name'])  : '';
        $google_name_parts[] = isset($profile['family_name']) ? preg_replace('/[^a-zA-Z0-9]/s', '', $profile['family_name']) : '';
        
        return array(
            "name"    => trim(implode(' ', $google_name_parts)),
            "email"   => $profile['email'],
            "picture" => isset($profile['picture']) ? $profile['picture'] : '',
            "token"   => $access_token
        );
    }
    
    /**
     * @param string $url
     * @param string $method GET, POST
     * @param array  $params
     * @param array  $headers
     * 
     * @return mixed
     * @throws \Exception
     */
    protected function fetch($url, $method = "GET", $params = array(), $headers = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if( $method == "POST" ) curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if( ! empty($params) )  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        if( ! empty($headers) ) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $response = @json_decode($response, true);
        if( empty($response) ) throw new \Exception("Google API response is not a valid object.");
        
        return $response;
    }
    
    /**
     * @param string $email
     * 
     * @return bool
     */
    public function account_exists($email)
    {
        return $this->repo->get_record_count(array("email = '{$email}' or alt_email = '{$email}'")) > 0;
    }
    
    /**
     * @param string $email
     * 
     * @return account
     * @throws \Exception
     */
    public function get_account($email)
    {
        $list = $this->repo->find(array("email = '{$email}' or alt_email = '{$email}'"), 0, 0, "creation_date");
        
        if( count($list) == 0 ) return null;
        if( count($list) > 1 ) throw new \Exception("Multiple accounts found");
        
        return new account($list[0]->id_account);
    }
    
    /**
     * @param array $profile = [
     *                             "name"         => "Some name",
     *                             "email"        => "some@email.com",
     *                             "picture"      => "https://some/pic.png",
     *                             "token"        => "xxxxxxxxxxx",
     *                             "country_code" => "us",
     *                         ]
     * 
     * @return void
     * @throws \Exception
     */
    public function create_account($profile)
    {
        global $config, $settings, $modules;
        $current_module = $modules["single_signon"];
        
        $country = $profile["country_code"];
        if( empty($country) ) $country = $settings->get("modules:accounts.default_country");
        if( empty($country) ) $country = "us";
        
        $account           = new account();
        $account->password = ":invalid:";
        $account->country  = strtolower($country);
        $account->email    = $profile["email"];
        
        $account->display_name = $profile["name"];
        if( empty($account->display_name) || is_numeric($account->display_name) )
            $account->display_name = "user_" . time();
        
        $account->user_name = wp_sanitize_filename($account->display_name);
        
        if( empty($account->user_name) || strlen($account->user_name) < 5 )
        {
            $account->user_name    = "user_" . time();
            $account->display_name = $account->user_name;
        }
        
        $filter = array("user_name like '{$account->user_name}%'");
        $count = $this->repo->get_record_count($filter);
        if( $count > 0 ) $account->user_name .= ($count + 1);
        
        $filter = array("display_name like '{$account->display_name}%'");
        $count = $this->repo->get_record_count($filter);
        if( $count > 0 ) $account->display_name .= " " . ($count + 1);
        
        if( ! empty($profile["picture"]) && filter_var($profile["picture"], FILTER_VALIDATE_URL) )
            $account->avatar = $profile["picture"];
        
        $account->set_new_id();
        $account->save();
        
        $account->activate($config::NEWCOMER_USER_LEVEL);
        $account->level = $config::NEWCOMER_USER_LEVEL;
        $account->set_engine_pref("google:token",  $profile["token"]);
        
        $config->globals["@single_signon:working_account"]  = $account;
        $config->globals["@single_signon:working_provider"] = "Google";
        $config->globals["@single_signon:provider_icon"]    = "🟡";
        $current_module->load_extensions("google_toolbox", "after_creating_account");
    }
    
    public function open_session($account)
    {
        $device = new device($account->id_account);
        if( ! $device->_exists )
        {
            $device->set_new($account);
            $device->state = "enabled";
            $device->save();
        }
        
        $account->open_session($device);
    }
}
