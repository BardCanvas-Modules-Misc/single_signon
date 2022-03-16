<?php
namespace hng2_modules\single_signon;

use hng2_base\account;
use hng2_base\accounts_repository;
use hng2_base\config;

class accounts_repository_extender extends accounts_repository
{
    /**
     * @param string $social_network_key facebook|twitter|...
     * @param int    $remote_id_account  Account id on remote system
     * 
     * @return null|account
     */
    public function get_by_social_network($social_network_key, $remote_id_account)
    {
        global $database;
        
        $res = $database->query("
            select id_account from account_engine_prefs
            where name = '$social_network_key:id' and value = '\"$remote_id_account\"'
        ");
        
        if( $database->num_rows($res) == 0 ) return null;
        
        $row = $database->fetch_object($res);
        $id  = $row->id_account;
        
        return new account($id);
    }
    
    /**
     * @param $social_network_key
     * @param $remote_id_account
     * @param $user_display_name
     * @param $user_email
     * 
     * @return account
     */
    public function auto_create($social_network_key, $remote_id_account, $user_display_name, $user_email)
    {
        $suffix    = "";
        $user_name = wp_sanitize_filename($user_display_name);
        if( strlen($user_name) <= 8 ) $user_name = "fbu" . mt_rand(10000, 99999);
        while( true )
        {
            $rows = $this->get_record_count(array("user_name" => $user_name.$suffix));
            if( $rows > 0 ) $suffix++;
            else            break;
        }
        
        $ip       = get_remote_address();
        $country  = get_geoip_country_code($ip);
        $id_acct  = "99" . $remote_id_account;
        
        $account = new account();
        $account->id_account   = $id_acct;
        $account->user_name    = $user_name.$suffix;
        $account->password     = md5(uniqid());
        $account->display_name = $user_display_name;
        $account->email        = $user_email;
        $account->country      = empty($country) ? "US" : $country;
        $account->level        = config::NEWCOMER_USER_LEVEL;
        $account->state        = "enabled";
        
        # First recheck if the account exists.
        $tmp_account = new account($id_acct);
        if( $tmp_account->_exists ) return $tmp_account;
        
        # Now save it.
        try
        {
            $account->save();
        }
        catch(\Exception $e)
        {
            # Note: here, the account already exists but isn't linked. We'll just relink it.
            $account = new account("99" . $remote_id_account);
        }
        
        $account->set_engine_pref("$social_network_key:id", $remote_id_account);
        
        return $account;
    }
    
    public function fetch_and_set_facebook_avatar($facebook_id_account, $id_account, $user_name)
    {
        global $config, $database, $mem_cache;
        
        $url = "https://graph.facebook.com/$facebook_id_account/picture?type=large&w‌​idth=720&height=720";
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,            $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $contents = curl_exec($ch);
        
        if( curl_error($ch) ) return;
        
        $target_dir = "{$config->datafiles_location}/user_avatars/{$user_name}";
        if( ! is_dir($target_dir) )
        {
            if( ! @mkdir($target_dir, 0777, true) ) return;
            
            @chmod($target_dir, 0777);
        }
        
        $random      = date("YmdHis");
        $target_file = "{$target_dir}/facebook_avatar_{$random}.jpg";
        file_put_contents($target_file, $contents);
        @chmod($target_file, 0777);
        
        $avatar = basename($target_file);
        $database->exec("update account set avatar = '$avatar' where user_name = '$user_name'");
        $mem_cache->delete("account:{$id_account}");
    }
}
