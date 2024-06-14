<?php
namespace hng2_modules\single_signon;

use hng2_base\account;
use hng2_base\accounts_repository;
use hng2_base\device;
use hng2_tools\cli_colortags;

class twitch_api_client
{
    protected $client_id;
    protected $client_secret;
    
    /**
     * @var accounts_repository 
     */
    protected $accounts_repo = null;
    
    /**
     * @throws \Exception
     */
    public function __construct()
    {
        global $settings;
        
        $this->client_id     = $settings->get("modules:single_signon.twitch_app_id");
        $this->client_secret = $settings->get("modules:single_signon.twitch_app_secret");
        
        if(empty($this->client_id)) throw new \Exception("Client id unset");
        if(empty($this->client_secret)) throw new \Exception("Client secret unset");
        
        $this->accounts_repo = new accounts_repository();
    }
    
    /**
     * @param string $code
     * 
     * @return array = [
     *     "access_token"  => "xxx",
     *     "expires_in"    => 15372,
     *     "refresh_token" => "xxx",
     *     "scope"         => ["scope", "scope", "..."],
     *     "token_type     => "bearer"
     * ]
     * @throws \Exception
     */
    public function validate_auth_code($code)
    {
        global $modules;
        
        $this_module = $modules["single_signon"];
        $redir_uri   = "{$this_module->get_url(true)}/scripts/twitch.php";
        
        $params = array(
            "client_id"     => $this->client_id,
            "client_secret" => $this->client_secret,
            "code"          => $code,
            "grant_type"    => "authorization_code",
            "redirect_uri"  => $redir_uri,
        );
        
        $res = $this->post("https://id.twitch.tv/oauth2/token", $params);
        
        if( $res["status"] ) throw new \Exception("{$res["status"]} {$res["message"]}");
        return $res;
    }
    
    /**
     * @param string $user_token
     * 
     * @return array = [
     *     "id"                => 123456789,
     *     "login"             => "username",
     *     "display_name"      => "display name",
     *     "profile_image_url" => "https://static-cdn.jtvnw.net/jtv_user_pictures/xxx.png",
     *     "email"             => "some@email.com" 
     * ]
     * @throws \Exception
     */
    public function get_user_account($user_token)
    {
        $headers = array(
            "Authorization: Bearer $user_token",
            "Client-Id: {$this->client_id}"
        );
        
        $res = $this->get("https://api.twitch.tv/helix/users", array(), $headers);
        
        if( $res["status"] ) throw new \Exception("{$res["status"]} {$res["message"]}");
        return current($res["data"]);
    }
    
    protected function get($url, $params, $headers = null)
    {
        if( ! empty($params) ) $url .= "?" . http_build_query($params);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,            $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        if( is_array($headers) ) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $res = curl_exec($ch);
        
        if( curl_error($ch) ) throw new \Exception("Communications error: " . curl_error($ch));
        
        $obj = @json_decode($res, true);
        
        if( ! (is_object($obj) || is_array($obj)) ) throw new \Exception("Invalid API response: $res");
        
        if( $obj["error"] ) throw new \Exception("{$obj["error"]} {$obj["error_description"]}");
        
        return $obj;
    }
    
    /**
     * @param string $url
     * @param array  $params
     * 
     * @return array
     * @throws \Exception
     */
    protected function post($url, $params, $headers = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,            $url);
        curl_setopt($ch, CURLOPT_POST,           1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,     http_build_query($params));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        if( is_array($headers) ) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $res = curl_exec($ch);
        
        if( curl_error($ch) ) throw new \Exception("Communications error: " . curl_error($ch));
        
        $obj = @json_decode($res, true);
        
        if( ! (is_object($obj) || is_array($obj)) ) throw new \Exception("Invalid API response: $res");
        
        if( $obj["error"] ) throw new \Exception("{$obj["error"]} {$obj["error_description"]}");
        
        return $obj;
    }
    
    /**
     * @param array $twitch_user
     * @param array $twitch_token
     * @param bool  $open_session
     * 
     * @return account|null
     * @throws \Exception
     */
    public function find_local_account($twitch_user, $twitch_token, $open_session = true)
    {
        $filter = array("
                          id_account in (
                              select aep.id_account
                              from   account_engine_prefs aep
                              where  aep.name  = 'twitch:username'
                              and    aep.value = '\"{$twitch_user["login"]}\"'
                          )
                        ");
        $list = $this->accounts_repo->find($filter, 1, 0, "creation_date");
        if( empty($list) ) return null;
        
        # Account found! Let's open the session
        
        $account = new account($list[0]->id_account);
        
        if( $open_session )
        {
            $device  = new device($account->id_account);
            if( ! $device->_exists )
            {
                $device->set_new($account);
                $device->state = "enabled";
                $device->save();
            }
            
            $account->open_session($device);
            
            # Let's save incoming details
            
            $fields = array(
                "twitch:user_id"       => $twitch_user["id"],
                "twitch:username"      => $twitch_user["login"],
                "twitch:access_token"  => $twitch_token["access_token"],
                "twitch:expires_in"    => $twitch_token["expires_in"],
                "twitch:refresh_token" => $twitch_token["refresh_token"],
            );
            
            foreach($fields as $field => $value)
                if(empty($account->engine_prefs[$field]))
                    $account->set_engine_pref($field, $value);
        }
        
        return $account;
    }
    
    /**
     * @param array $twitch_user
     * @param array $twitch_token
     * 
     * @return account
     * @throws \Exception
     */
    public function create_local_account($twitch_user, $twitch_token)
    {
        global $config, $settings, $modules;
        
        $current_module = $modules["single_signon"];
        
        $country = $settings->get("modules:accounts.default_country");
        if( empty($country) ) $country = "us";
        
        $account                = new account();
        $account->user_name     = wp_sanitize_filename($twitch_user["login"]);
        $account->password      = md5(randomPassword());
        $account->display_name  = preg_replace('/[^a-zA-Z0-9 _.-]/', "", $twitch_user["display_name"]);
        $account->email         = $twitch_user["email"];
        $account->country       = strtolower($country);
        
        if( ! empty($account->email) )
        {
            $filter = array("(email = '{$account->email}' or alt_email = '{$account->email}')");
            $count = $this->accounts_repo->get_record_count($filter);
            if( $count > 0 ) throw new \Exception("Another account exists with the same email address");
        }
        
        $filter = array("user_name like '{$account->user_name}%'");
        $count = $this->accounts_repo->get_record_count($filter);
        if( $count > 0 ) $account->user_name .= ($count + 1);
        
        $filter = array("display_name like '{$account->display_name}%'");
        $count = $this->accounts_repo->get_record_count($filter);
        if( $count > 0 ) $account->display_name .= " " . ($count + 1);
        
        $account->set_new_id();
        $account->save();
        
        $account->activate($config::NEWCOMER_USER_LEVEL);
        $account->level   = $config::NEWCOMER_USER_LEVEL;
        
        $fields = array(
            "twitch:user_id"       => $twitch_user["id"],
            "twitch:username"      => $twitch_user["login"],
            "twitch:access_token"  => $twitch_token["access_token"],
            "twitch:expires_in"    => $twitch_token["expires_in"],
            "twitch:refresh_token" => $twitch_token["refresh_token"],
        );
        
        $config->globals["@single_sign_on.new_account_record"] = $account;
        $config->globals["@single_sign_on.new_account_fields"] = $fields;
        $current_module->load_extensions("twitch_api_client", "after_creating_account");
        $fields = $config->globals["@single_sign_on.new_account_fields"];
        
        foreach($fields as $field => $value)
            if(empty($account->engine_prefs[$field]))
                $account->set_engine_pref($field, $value);
        
        return $account;
    }
}
