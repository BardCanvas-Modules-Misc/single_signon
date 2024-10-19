<?php
namespace hng2_modules\single_signon;

use hng2_base\account;
use hng2_base\accounts_repository;
use hng2_base\device;
use hng2_tools\cli_colortags;

class telegram_toolbox
{
    /**
     * @var accounts_repository 
     */
    protected $accounts_repo = null;
    
    public function __construct()
    {
        $this->accounts_repo = new accounts_repository();
    }
    
    public function validate_incoming_data(
        $auth_date, $first_name, $hash, $id, $last_name, $photo_url, $username, $telegram_token
    ) {
        $data_check_arr = array(
            "auth_date=$auth_date"   ,
            "first_name=$first_name" ,
            "id=$id"                 ,
            "last_name=$last_name"   ,
            "photo_url=$photo_url"   ,
            "username=$username"     ,
        );
        sort($data_check_arr);
        $data_check_string = implode("\n", $data_check_arr);
        $secret_key        = hash('sha256', $telegram_token, true);
        $my_hash           = hash_hmac('sha256', $data_check_string, $secret_key);
        
        if (strcmp($hash, $my_hash) !== 0) throw new \Exception("Data mismatch");
        
        if( (time() - $auth_date) > 86400 ) throw new \Exception("Data outdated");
    }
    
    /**
     * @param int    $id
     * @param string $username
     * @param bool   $open_session
     * 
     * @return account|null
     * @throws \Exception
     */
    public function find_local_account($id, $username, $open_session = true)
    {
        $filter = array("
                      id_account in (
                          select aep.id_account
                          from account_engine_prefs aep
                          where aep.name  = 'telegram:user_id'
                          and   aep.value = '{$id}'
                      )
                  ");
        $list = $this->accounts_repo->find($filter, 1, 0, "creation_date");
        if( empty($list) ) return null;
        
        $account = new account($list[0]->id_account);
        $account->set_engine_pref("telegram:username", $username);
        
        if( $open_session )
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
        
        return $account;
    }
    
    /**
     * @param number $id
     * @param string $first_name
     * @param string $last_name
     * @param string $username
     * @param string $photo_url
     * 
     * @return account
     * @throws \Exception
     */
    public function create_local_account($id, $first_name, $last_name, $username, $photo_url)
    {
        global $config, $settings;
        
        $country = $settings->get("modules:accounts.default_country");
        if( empty($country) ) $country = "us";
        
        $account                = new account();
        $account->user_name     = wp_sanitize_filename($username);
        $account->password      = ":invalid:";
        $account->display_name  = preg_replace('/[^a-zA-Z0-9 _.-]/', "", trim("$first_name $last_name"));
        $account->country       = strtolower($country);
        
        if( empty($account->display_name) ) $account->display_name = $account->user_name;
        
        $filter = array("user_name like '{$account->user_name}%'");
        $count = $this->accounts_repo->get_record_count($filter);
        if( $count > 0 ) $account->user_name .= ($count + 1);
        
        $filter = array("display_name like '{$account->display_name}%'");
        $count = $this->accounts_repo->get_record_count($filter);
        if( $count > 0 ) $account->display_name .= " " . ($count + 1);
        
        if( ! empty($photo_url) && filter_var($photo_url, FILTER_VALIDATE_URL) )
            $account->avatar = $photo_url;
        
        $account->set_new_id();
        $account->save();
        
        $account->activate($config::NEWCOMER_USER_LEVEL);
        $account->level = $config::NEWCOMER_USER_LEVEL;
        $account->set_engine_pref("telegram:user_id",  $id);
        $account->set_engine_pref("telegram:username", $username);
        
        return $account;
    }
}