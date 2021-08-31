<?php
/**
 * Facebook Toolbox
 *
 * @package    BardCanvas
 * @subpackage single_signon
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * @var config $config
 * @var module $current_module self
 * 
 * $_POST params:
 * @param string "method" login only by now
 * 
 * For register method:
 * @param int    "id"
 * @param string "token"
 * 
 * @return string
 */

use hng2_base\account;
use hng2_base\config;
use hng2_base\device;
use hng2_base\module;
use hng2_modules\single_signon\accounts_repository_extender;

include "../../config.php";
include "../../includes/bootstrap.inc";
session_start();
header("Content-Type: text/plain; chrset=utf-8");

if( $settings->get("engine.enabled") != "true" && ! $account->_is_admin ) die("ERROR_ENGINE_DISABLED");

if( empty($_SESSION["single_signon_csrf_token"]) ) die($current_module->language->messages->session_token_not_found);

if( empty($_POST) ) die($language->errors->invalid_call);
if( ! is_array($_POST) ) die($language->errors->invalid_call);
if( empty($_POST["method"]) ) die($language->errors->invalid_call);

# Here we need to check all possible places for the Facebook App Id!
$app_id = $settings->get("modules:single_signon.facebook_app_id");
if( empty($app_id) ) $app_id = $settings->get("modules:fbcomments.facebook_app_id");
if( empty($app_id) ) die($current_module->language->messages->facebook->missing_app_id);

# Same with the Facebook App Secret key!
$app_secret = $settings->get("modules:single_signon.facebook_app_secret");
if( empty($app_secret) ) die($current_module->language->messages->facebook->missing_app_secret);

if( $_POST["method"] == "login" )
{
    if( empty($_POST["id"])    ) die($current_module->language->messages->facebook->missing_account_id);
    if( empty($_POST["token"]) ) die($current_module->language->messages->facebook->missing_access_token);
    
    include_once __DIR__ . "/../lib/facebook-php-sdk/facebook.php";
    $fb_params = array(
        'appId'              => $app_id,
        'secret'             => $app_secret,
        'fileUpload'         => false,
        'allowSignedRequest' => true
    );
    $facebook     = new Facebook($fb_params);
    $access_token = $_POST["token"];
    
    try
    {
        $fbprofile = $facebook->api("/{$_POST["id"]}?fields=id,name,email");
    }
    catch(\Exception $e)
    {
        die( $current_module->language->messages->facebook->error_on_remote_call . $e->getMessage() );
    }
    
    # Check for existence and creation
    
    $repository = new accounts_repository_extender();
    
    $register_device = false;
    if( $account->_exists )
    {
        # We have an open session and we're linking...
        $xaccount = $repository->get_by_social_network("facebook", $_POST["id"]);
        if( ! is_null($xaccount) && $xaccount->id_account != $account->id_account )
            die($current_module->language->messages->facebook->already_linked);
        
        $account->set_engine_pref("facebook:id", $_POST["id"]);
        $register_device = true;
    }
    else
    {
        $account = $repository->get_by_social_network("facebook", $_POST["id"]);
        
        # Lookup by mangled numeric id to avoid dupes
        if( is_null($account) )
        {
            $id = "20" . $_POST["id"];
            $account = new account($id);
            if( ! $account->_exists ) $account = null;
        }
        
        # Last check before creation
        if( is_null($account) && $settings->get("modules:accounts.register_enabled") != "true" )
            die($current_module->language->messages->user_registration_disabled);
        
        # Create
        if( is_null($account) )
        {
            $account = $repository->auto_create("facebook", $fbprofile["id"], $fbprofile["name"], $fbprofile["email"]);
            $repository->fetch_and_set_facebook_avatar($fbprofile["id"], $account->id_account, $account->user_name);
            $register_device = true;
        }
    }
    
    # Session opening and new device detection
    
    $device = new device($account->id_account);
    if( $settings->get("modules:accounts.enforce_device_registration") != "true" )
    {
        if( ! $device->_exists )
        {
            $device->set_new($account);
            $device->save();
            $device->enable();
            $device_return = "OK";
        }
        else
        {
            $device->ping();
            $device_return = "OK";
        }
    }
    else # $settings->get("modules:accounts.enforce_device_registration") == "true"
    {
        if( $register_device )
        {
            # Even though device registration is enforced, we'll allow it the first time.
            $device->set_new($account);
            $device->save();
            $device->enable();
            $device_return = "OK";
        }
        else
        {
            if( ! $device->_exists )
            {
                $device->set_new($account);
                $device->save();
                $device->enable();
                $device_return = "OK";
            }
            else
            {
                switch( $device->state )
                {
                    case "disabled":
                        die("ERROR_DEVICE_DISABLED");
                        break;
                    
                    case "enabled":
                        $device->ping();
                        $device_return = "OK";
                        break;
                    
                    case "unregistered":
                    default:
                        $device->set_new($account);
                        $device->save();
                        $device->enable();
                        $device_return = "OK";
                }
            }
        }
    }
    
    if( $device_return != "OK" ) die("Device is not registered. Please check your email and register the device.");
    
    $account->open_session($device);
    
    # Finished
    
    unset( $_SESSION["single_signon_csrf_token"] );
    die("OK");
}

if( $_POST["method"] == "unlink" )
{
    if( ! $account->_exists ) die($language->errors->access_denied);
    if( empty($account->engine_prefs["facebook:id"]) ) die($current_module->language->messages->facebook->account_not_linked);
    
    $account->set_engine_pref("facebook:id", "");
    die("OK");
}

echo $language->errors->invalid_call;
