<?php
/**
 * Google Toolbox
 *
 * @package    BardCanvas
 * @subpackage single_signon
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 * 
 * @var config $config
 * @var module $current_module self
 * 
 * $_REQUEST params:
 * @param string $mode      login, register, link, unlink
 * @param string $code      when coming from Google
 * @param string $redir_url provided when registering or logging in
 */

use hng2_base\account;
use hng2_base\accounts_repository;
use hng2_base\config;
use hng2_base\module;
use hng2_modules\single_signon\google_toolbox;
use hng2_tools\cli;
use hng2_tools\cli_colortags;

include "../../config.php";
include "../../includes/bootstrap.inc";

session_start();
header("Content-Type: text/html; chrset=utf-8");

#
# Segment: preinits
#

$client_id     = $settings->get("modules:single_signon.google_client_id");
$client_secret = $settings->get("modules:single_signon.google_client_secret");
$accounts_repo = new accounts_repository();

#
# Segment: inits
#

$logdate = date("Ymd");
$logtime = date("Y-m-d H:i:s");
cli::$output_file = "{$config->logfiles_location}/google_auth-$logdate.log";
cli::$output_to_file_only = true;

$ip   = get_remote_address();
$host = @gethostbyaddr($ip); if(empty($host)) $host = $ip;
$loc  = forge_geoip_location($ip);

if( empty($client_id) || empty($client_secret) )
{
    cli_colortags::write("<red>[$logtime] $ip - $host - $loc</red>\n");
    cli_colortags::write("<red>Incomplete configuration: Google client id or secret unset.</red>\n\n");
    die($current_module->language->messages->google->unconfigured);
}

if( empty($_REQUEST) )
{
    cli_colortags::write("<red>[$logtime] $ip - $host - $loc</red>\n");
    cli_colortags::write("<red>Empty REQUEST.</red>\n\n");
    die($language->errors->invalid_call);
}

if( ! is_array($_REQUEST) )
{
    cli_colortags::write("<red>[$logtime] $ip - $host - $loc</red>\n");
    cli_colortags::write("<red>REQUEST is not an array.</red>\n\n");
    die($language->errors->invalid_call);
}

#
# Segment: mode selection
#

$mode    = trim(stripslashes($_REQUEST["mode"]));
$toolbox = new google_toolbox();
$repo    = new accounts_repository();


if( $mode == "login" )
{
    $_SESSION["single_signon_session_mode"]  = "login";
    $_SESSION["single_signon_redir_url"]     = $_REQUEST["redir_url"];
    $_SESSION["single_signon_country_code"]  = get_geoip_country_code($ip);
    
    $url = $toolbox->get_google_redirect_url();
    header("Location: $url");
    die("<html><body><a href='$url'>{$language->click_here_to_continue}</a></html>");
}

if( $mode == "register" )
{
    $_SESSION["single_signon_session_mode"]  = "register";
    $_SESSION["single_signon_redir_url"]     = $_REQUEST["redir_url"] != "" ? $_REQUEST["redir_url"] : "/";
    $_SESSION["single_signon_country_code"]  = get_geoip_country_code($ip);
    
    $url = $toolbox->get_google_redirect_url();
    header("Location: $url");
    die("<html><body><a href='$url'>{$language->click_here_to_continue}</a></html>");
}

if( $mode == "link" )
{
    $_SESSION["single_signon_session_mode"]    = "link";
    $_SESSION["single_signon_redir_url"]       = "/accounts/edit_account.php";
    $_SESSION["single_signon_country_code"]    = get_geoip_country_code($ip);
    $_SESSION["single_signon_linking_account"] = $account->id_account;
    
    $url = $toolbox->get_google_redirect_url();
    header("Location: $url");
    die("<html><body><a href='$url'>{$language->click_here_to_continue}</a></html>");
}

if( $mode == "unlink" )
{
    if( empty($account->email) ) die($current_module->language->messages->telegram->missing_account_email);
    
    $account->set_engine_pref("google:token", "");
    
    die("OK");
}

#
# Segment: coming from google after authorization
#

if( empty($mode) )
{
    $mode = $_SESSION["single_signon_session_mode"];
    if( ! in_array($mode, array("register", "login", "link")) )
    {
        # Throw an error message in a full page popup.
        unset($_SESSION["single_signon_session_mode"], $_SESSION["single_signon_redir_url"], $_SESSION["single_signon_country_code"]);
        $template->set_page_title($current_module->language->messages->google->error_popup->title);
        $error    = $current_module->language->messages->google->error_popup->missing_mode->caption;
        $pcontent = $current_module->language->messages->google->error_popup->missing_mode->content;
        $template->page_contents_include = "popup.inc";
        include "{$template->abspath}/popup.php";
        exit;
    }
    
    $code = $_REQUEST["code"];
    if( empty($code) )
    {
        # Throw an error message in a full page popup.
        $template->set_page_title($current_module->language->messages->google->error_popup->title);
        $error    = $current_module->language->messages->google->error_popup->missing_code->caption;
        $pcontent = $current_module->language->messages->google->error_popup->missing_code->content;
        $template->page_contents_include = "popup.inc";
        include "{$template->abspath}/popup.php";
        exit;
    }
    
    if( $mode == "register" )
    {
        try
        {
            $token   = $toolbox->get_access_token($code);
            $profile = $toolbox->get_user_profile($token);
        }
        catch(\Exception $e)
        {
            # Throw an error message in a full page popup.
            $template->set_page_title($current_module->language->messages->google->error_popup->title);
            $error    = $e->getMessage();
            $pcontent = $current_module->language->messages->google->error_popup->api_call_failed->content;
            $template->page_contents_include = "popup.inc";
            include "{$template->abspath}/popup.php";
            exit;
        }
        
        if( $toolbox->account_exists($profile["email"]) )
        {
            # Throw an error message in a full page popup.
            $template->set_page_title($current_module->language->messages->google->error_popup->account_exists->title);
            $error    = $current_module->language->messages->google->error_popup->account_exists->caption;
            $pcontent = $current_module->language->messages->google->error_popup->account_exists->content;
            $template->page_contents_include = "popup.inc";
            include "{$template->abspath}/popup.php";
            exit;
        }
        
        $profile["country_code"] = strtolower($_SESSION["single_signon_country_code"]);
        $toolbox->create_account($profile);
        $url = $_SESSION["single_signon_redir_url"];
        if( empty($url) ) $url = "/";
        unset($_SESSION["single_signon_session_mode"], $_SESSION["single_signon_redir_url"], $_SESSION["single_signon_country_code"]);
        header("Location: $url");
        die("<html><body><a href='$url'>{$language->click_here_to_continue}</a></html>");
    }
    
    if( $mode == "login" )
    {
        try
        {
            $token   = $toolbox->get_access_token($code);
            $profile = $toolbox->get_user_profile($token);
        }
        catch(\Exception $e)
        {
            # Throw an error message in a full page popup.
            $template->set_page_title($current_module->language->messages->google->error_popup->title);
            $error    = $e->getMessage();
            $pcontent = $current_module->language->messages->google->error_popup->api_call_failed->content;
            $template->page_contents_include = "popup.inc";
            include "{$template->abspath}/popup.php";
            exit;
        }
        
        if( ! $toolbox->account_exists($profile["email"]) )
        {
            # Throw an error message in a full page popup.
            $template->set_page_title($current_module->language->messages->google->error_popup->account_not_exists->title);
            $error    = $current_module->language->messages->google->error_popup->account_not_exists->caption;
            $pcontent = $current_module->language->messages->google->error_popup->account_not_exists->content;
            $template->page_contents_include = "popup.inc";
            include "{$template->abspath}/popup.php";
            exit;
        }
        
        try
        {
            $account = $toolbox->get_account($profile["email"]);
        }
        catch(\Exception $e)
        {
            # Throw an error message in a full page popup.
            $template->set_page_title($current_module->language->messages->google->error_popup->multiple_accounts_found->title);
            $error    = $current_module->language->messages->google->error_popup->multiple_accounts_found->caption;
            $pcontent = $current_module->language->messages->google->error_popup->multiple_accounts_found->content;
            $template->page_contents_include = "popup.inc";
            include "{$template->abspath}/popup.php";
            exit;
        }
        
        if( is_null($account) )
        {
            # Throw an error message in a full page popup.
            $template->set_page_title($current_module->language->messages->google->error_popup->account_not_exists->title);
            $error    = $current_module->language->messages->google->error_popup->account_not_exists->caption;
            $pcontent = $current_module->language->messages->google->error_popup->account_not_exists->content;
            $template->page_contents_include = "popup.inc";
            include "{$template->abspath}/popup.php";
            exit;
        }
        
        $toolbox->open_session($account);
        $url = $_SESSION["single_signon_redir_url"];
        if( empty($url) ) $url = "/";
        unset($_SESSION["single_signon_session_mode"], $_SESSION["single_signon_redir_url"], $_SESSION["single_signon_country_code"]);
        header("Location: $url");
        die("<html><body><a href='$url'>{$language->click_here_to_continue}</a></html>");
    }
    
    if( $mode == "link" )
    {
        try
        {
            $token   = $toolbox->get_access_token($code);
            $profile = $toolbox->get_user_profile($token);
        }
        catch(\Exception $e)
        {
            # Throw an error message in a full page popup.
            $template->set_page_title($current_module->language->messages->google->error_popup->title);
            $error    = $e->getMessage();
            $pcontent = $current_module->language->messages->google->error_popup->api_call_failed->content;
            $template->page_contents_include = "popup.inc";
            include "{$template->abspath}/popup.php";
            exit;
        }
        
        $linking_account_id = $_SESSION["single_signon_linking_account"];
        if( empty($linking_account_id) )
        {
            # Throw an error message in a full page popup.
            $template->set_page_title($current_module->language->messages->google->error_popup->linking_account_id_unset->title);
            $error    = $current_module->language->messages->google->error_popup->linking_account_id_unset->caption;
            $pcontent = $current_module->language->messages->google->error_popup->linking_account_id_unset->content;
            $template->page_contents_include = "popup.inc";
            include "{$template->abspath}/popup.php";
            exit;
        }
        
        $linking_account = new account($linking_account_id);
        
        if( ! $linking_account->_exists )
        {
            # Throw an error message in a full page popup.
            $template->set_page_title($current_module->language->messages->google->error_popup->linking_account_not_found->title);
            $error    = $current_module->language->messages->google->error_popup->linking_account_not_found->caption;
            $pcontent = $current_module->language->messages->google->error_popup->linking_account_not_found->content;
            $template->page_contents_include = "popup.inc";
            include "{$template->abspath}/popup.php";
            exit;
        }
        
        #
        # Update the account email
        #
        
        $filter = array("id_account <> '$linking_account_id' and (email = '{$profile["email"]}' or alt_email = '{$profile["email"]}')");
        $found  = $accounts_repo->get_record_count($filter);
        
        if( $found > 0 )
        {
            # Throw an error message in a full page popup.
            $template->set_page_title($current_module->language->messages->google->error_popup->multiple_accounts_found->title);
            $error    = $current_module->language->messages->google->error_popup->multiple_accounts_found->caption;
            $pcontent = $current_module->language->messages->google->error_popup->multiple_accounts_found->content;
            $template->page_contents_include = "popup.inc";
            include "{$template->abspath}/popup.php";
            exit;
        }
        
        $old_main_email = $linking_account->email;
        $have_changes   = false;
        
        if( $linking_account->email != $profile["email"] )
        {
            $linking_account->email = $profile["email"];
            $have_changes = true;
        }
        
        if( $linking_account->alt_email == $profile["email"] )
        {
            $linking_account->alt_email = "";
            $have_changes = true;
        }
        
        if( empty($linking_account->alt_email) && $old_main_email != $profile["email"] )
        {
            $linking_account->alt_email = $old_main_email;
            $have_changes = true;
        }
        
        $linking_account->set_engine_pref("google:token", $profile["token"]);
        
        if( $have_changes )
        {
            $linking_account->save();
            send_notification(
                $linking_account->id_account,
                "success",
                trim($current_module->language->messages->google->mails_updated)
            );
        }
        
        unset($_SESSION["single_signon_session_mode"], $_SESSION["single_signon_redir_url"], $_SESSION["single_signon_country_code"]);
        $url = "/accounts/edit_account.php";
        header("Location: $url");
        die("<html><body><a href='$url'>{$language->click_here_to_continue}</a></html>");
    }
}

throw_fake_501();
