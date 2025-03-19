<?php
/**
 * Telegram Toolbox
 *
 * @package    BardCanvas
 * @subpackage single_signon
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 * 
 * @var config $config
 * @var module $current_module self
 * 
 * $_REQUEST params:
 * @param string $method     login, register, link, unlink
 * @param array  $data = [
 *     "id"         => 123456789,
 *     "first_name" => "xxxxxxx",
 *     "last_name"  => "xxxxxxx",
 *     "username"   => "xxxxxxx",
 *     "photo_url"  => "xxxxxxx",
 *     "auth_date"  => 123456789,
 *     "hash"       => "xxxxxxx",
 * ] 
 * 
 * @return string
 */

use hng2_base\config;
use hng2_base\module;
use hng2_modules\single_signon\telegram_toolbox;
use hng2_tools\cli;
use hng2_tools\cli_colortags;

include "../../config.php";
include "../../includes/bootstrap.inc";
header("Content-Type: text/plain; chrset=utf-8");

#
# Segment: preinits
#

$telegram_bot   = str_replace("@", "", $settings->get("modules:single_signon.telegram_bot_username"));
$telegram_token = $settings->get("modules:single_signon.telegram_bot_token");

#
# Segment: inits
#

$logdate = date("Ymd");
$logtime = date("Y-m-d H:i:s");
cli::$output_file = "{$config->logfiles_location}/telegram_auth-$logdate.log";
cli::$output_to_file_only = true;

$ip   = get_remote_address();
$host = @gethostbyaddr($ip); if(empty($host)) $host = $ip;
$loc  = forge_geoip_location($ip);

if( empty($telegram_bot) || empty($telegram_token) )
{
    cli_colortags::write("<red>[$logtime] $ip - $host - $loc</red>\n");
    cli_colortags::write("<red>Incomplete configuration: bot username or token unset.</red>\n\n");
    die($current_module->language->messages->telegram->unconfigured);
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

if( $_REQUEST["mode"] != "unlink" && ! is_array($_REQUEST["data"]) )
{
    cli_colortags::write("<red>[$logtime] $ip - $host - $loc</red>\n");
    cli_colortags::write("<red>REQUEST is not an array.</red>\n\n");
    die($language->errors->invalid_call);
}

$mode = trim(stripslashes($_REQUEST["mode"]));
if( ! in_array($mode, array("login", "register", "link", "unlink")) ) throw_fake_501();

$data = $_REQUEST["data"];
$hash = $data["hash"];
unset($data["hash"]);

$toolbox = new telegram_toolbox();

if( $mode == "login" )
{
    try
    {
        $toolbox->validate_incoming_data($data, $hash, $telegram_token);
    }
    catch(\Exception $e)
    {
        cli_colortags::write("<red>[$logtime] $ip - $host - $loc</red>\n");
        cli_colortags::write("<light_purple>Login attempt failed: {$e->getMessage()}</light_purple>\n");
        cli_colortags::write("<light_purple>~ Account id:    {$data["id"]}</light_purple>\n");
        cli_colortags::write("<light_purple>~ User:          {$data["first_name"]} {$data["last_name"]} (@{$data["username"]})</light_purple>\n");
        cli_colortags::write("<light_purple>~ Incoming data: {$config->globals["@tg_auth:in_data"]}</light_purple>\n");
        cli_colortags::write("<light_purple>~ Computed data: {$config->globals["@tg_auth:my_data"]}</light_purple>\n");
        cli_colortags::write("<light_purple>~ Incoming hash: {$config->globals["@tg_auth:in_hash"]}</light_purple>\n");
        cli_colortags::write("<light_purple>~ Computed hash: {$config->globals["@tg_auth:my_hash"]}</light_purple>\n\n");
        die(sprintf($current_module->language->messages->telegram->login_attempt_failed, $e->getMessage()));
    }
    
    $xaccount = $toolbox->find_local_account($data["id"], $data["username"]);
    if( ! is_null($xaccount) )
    {
        cli_colortags::write("<red>[$logtime] $ip - $host - $loc</red>\n");
        cli_colortags::write("<green>@{$data["username"]} logged in.</green>\n");
        
        $config->globals["@single_signon:working_account"] = $xaccount;
        $current_module->load_extensions("telegram_toolbox", "after_linking_account");
        
        die("OK");
    }
    
    die($current_module->language->messages->telegram->account_not_found);
}

if( $mode == "register" )
{
    try
    {
        $toolbox->validate_incoming_data($data, $hash, $telegram_token);
    }
    catch(\Exception $e)
    {
        cli_colortags::write("<red>[$logtime] $ip - $host - $loc</red>\n");
        cli_colortags::write("<light_purple>Registration attempt failed: {$e->getMessage()}</light_purple>\n");
        cli_colortags::write("<light_purple>~ Account id: {$data["id"]}</light_purple>\n");
        cli_colortags::write("<light_purple>~ User:          {$data["first_name"]} {$data["last_name"]} (@{$data["username"]})</light_purple>\n");
        cli_colortags::write("<light_purple>~ Incoming data: {$config->globals["@tg_auth:in_data"]}</light_purple>\n");
        cli_colortags::write("<light_purple>~ Computed data: {$config->globals["@tg_auth:my_data"]}</light_purple>\n");
        cli_colortags::write("<light_purple>~ Incoming hash: {$config->globals["@tg_auth:in_hash"]}</light_purple>\n");
        cli_colortags::write("<light_purple>~ Computed hash: {$config->globals["@tg_auth:my_hash"]}</light_purple>\n\n");
        die(sprintf($current_module->language->messages->telegram->registration_attempt_failed, $e->getMessage()));
    }
    
    $xaccount = $toolbox->find_local_account($data["id"], $data["username"]);
    if( ! is_null($xaccount) )
    {
        $config->globals["@single_signon:working_account"] = $xaccount;
        $current_module->load_extensions("telegram_toolbox", "after_creating_account");
        die("OK");
    }
    
    $xaccount = $toolbox->create_local_account($data["id"], $data["first_name"], $data["last_name"], $data["username"], $data["photo_url"]);
    $xaccount = $toolbox->find_local_account($data["id"], $data["username"]);
    
    $config->globals["@single_signon:working_account"]  = $xaccount;
    $config->globals["@single_signon:working_provider"] = "Telegram";
    $config->globals["@single_signon:provider_icon"]    = "🔵";
    $current_module->load_extensions("telegram_toolbox", "after_creating_account");
    
    die("OK");
}

if( $mode == "link" )
{
    try
    {
        $toolbox->validate_incoming_data($data, $hash, $telegram_token);
    }
    catch(\Exception $e)
    {
        cli_colortags::write("<red>[$logtime] $ip - $host - $loc</red>\n");
        cli_colortags::write("<light_purple>Linking attempt failed: {$e->getMessage()}</light_purple>\n");
        cli_colortags::write("<light_purple>~ Account id:    {$data["id"]}</light_purple>\n");
        cli_colortags::write("<light_purple>~ User:          {$data["first_name"]} {$data["last_name"]} (@{$data["username"]})</light_purple>\n");
        cli_colortags::write("<light_purple>~ Incoming data: {$config->globals["@tg_auth:in_data"]}</light_purple>\n");
        cli_colortags::write("<light_purple>~ Computed data: {$config->globals["@tg_auth:my_data"]}</light_purple>\n");
        cli_colortags::write("<light_purple>~ Incoming hash: {$config->globals["@tg_auth:in_hash"]}</light_purple>\n");
        cli_colortags::write("<light_purple>~ Computed hash: {$config->globals["@tg_auth:my_hash"]}</light_purple>\n\n");
        die(sprintf($current_module->language->messages->telegram->linking_attempt_failed, $e->getMessage()));
    }
    
    $xaccount = $toolbox->find_local_account($data["id"], $data["username"], false);
    if( ! is_null($xaccount) && $xaccount->id_account != $account->id_account )
        die($current_module->language->messages->telegram->account_already_exists);
    
    $account->set_engine_pref("telegram:user_id",  $data["id"]);
    $account->set_engine_pref("telegram:username", $data["username"]);
    
    die("OK");
}

if( $mode == "unlink" )
{
    if( empty($account->email) ) die($current_module->language->messages->telegram->missing_account_email);
    
    $account->set_engine_pref("telegram:user_id",  "");
    $account->set_engine_pref("telegram:username", "");
    
    die("OK");
}

throw_fake_501();
