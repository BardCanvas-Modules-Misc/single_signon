<?php
/**
 * Twitch Toolbox
 *
 * @package    BardCanvas
 * @subpackage single_signon
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 * 
 * @var config $config
 * @var module $current_module self
 * 
 * @return string
 */

use hng2_base\config;
use hng2_base\device;
use hng2_base\module;
use hng2_modules\single_signon\twitch_api_client;
use hng2_tools\cli;
use hng2_tools\cli_colortags;

include "../../config.php";
include "../../includes/bootstrap.inc";
header("Content-Type: text/plain; chrset=utf-8");

#
# Segment: inits
#

$logdate = date("Ymd");
$logtime = date("Y-m-d H:i:s");
cli::$output_file = "{$config->logfiles_location}/twitch_auth-$logdate.log";
cli::$output_to_file_only = true;

$ip   = get_remote_address();
$host = @gethostbyaddr($ip); if(empty($host)) $host = $ip;
$loc  = forge_geoip_location($ip);

if( empty($_REQUEST) )
{
    cli_colortags::write("<red>[$logtime] $ip - $host - $loc</red>\n");
    cli_colortags::write("<red>Invalid call.</red>\n\n");
    die($language->errors->invalid_call);
}

if( ! is_array($_REQUEST) )
{
    cli_colortags::write("<red>[$logtime] $ip - $host - $loc</red>\n");
    cli_colortags::write("<red> - Invalid call.</red>\n");
    die($language->errors->invalid_call);
}

try
{
    $client = new twitch_api_client();
}
catch(\Exception $e)
{
    cli_colortags::write("<light_red>[$logtime] $ip - $host - $loc</light_red>\n");
    cli_colortags::write("<light_red>Error initializing Twitch API client: {$e->getMessage()}</light_red>\n\n");
    die($current_module->language->messages->twitch->unconfigured);
}

#
# Segment: account unlinking
#

if( $_REQUEST["method"] == "unlink" )
{
    if( ! $account->_exists ) die($language->errors->access_denied);
    if( empty($account->engine_prefs["twitch:username"]) ) die($current_module->language->messages->twitch->account_not_linked);
    
    cli_colortags::write("<light_gray>[$logtime] $ip - $host - $loc</light_gray>\n");
    cli_colortags::write("<light_gray>User #{$account->id_account} (@{$account->user_name}) unlinked from '{$account->engine_prefs["twitch:username"]}'.</light_gray>\n\n");
    
    $fields = array(
        "twitch:user_id"       => "",
        "twitch:username"      => "",
        "twitch:access_token"  => "",
        "twitch:expires_in"    => "",
        "twitch:refresh_token" => "",
    );
    
    foreach($fields as $field => $value) $account->set_engine_pref($field, $value);
    
    die("OK");
}

#
# Segment: account linking
#

if( $_REQUEST["error"] == "access_denied" ) throw_fake_401();

$back_link = sprintf($current_module->language->messages->twitch->go_back, $settings->get("engine.website_name"));

if( ! empty($_REQUEST["error"]) )
{
    cli_colortags::write("<light_red>[$logtime] $ip - $host - $loc</light_red>\n");
    cli_colortags::write("<light_red>Twitch error while linking account: {$_REQUEST["error"]} {$_REQUEST["error_description"]}</light_red>\n\n");
    
    header("Content-Type: text/html; chrset=utf-8");
    die(unindent("
        <!DOCTYPE HTML>
        <html><head>
        <title>{$current_module->language->messages->twitch->incoming_error}</title>
        </head><body>
        <h1>{$_REQUEST["error"]}</h1>
        <p>{$_REQUEST["error_description"]}</p>
        <p><a href='{$config->full_root_path}'>{$back_link}</a></p>
        </body></html>
    "));
}

$code  = $_REQUEST["code"];
$scope = $_REQUEST["scope"];
$state = $_REQUEST["state"];

if( empty($code) || empty($scope) || empty($state) )
{
    cli_colortags::write("<red>[$logtime] $ip - $host - $loc</red>\n");
    cli_colortags::write("<red>Missing Twitch arguments on account linking.</red>\n\n");
    header("Content-Type: text/html; chrset=utf-8");
    die(unindent("
        <!DOCTYPE HTML>
        <html><head>
        <title>{$current_module->language->messages->twitch->incoming_error}</title>
        </head><body>
        <h1>Missing arguments</h1>
        <p>The required arguments to process the request are incomplete or missing.</p>
        <p><a href='{$config->full_root_path}'>{$back_link}</a></p>
        </body></html>
    "));
}

#
# Segment: validate the authorization code to get an auth token
#

cli_colortags::write("<cyan>[$logtime] $ip - $host - $loc</cyan>\n");
cli_colortags::write("<cyan>Received authorization code: $code.</cyan>\n");
cli_colortags::write("<cyan>Validating...</cyan>\n");
try
{
    $twitch_token = $client->validate_auth_code($code);
}
catch(\Exception $e)
{
    cli_colortags::write("<red>Error fetching token:</red>\n");
    cli_colortags::write("<red>{$e->getMessage()}</red>\n");
    cli_colortags::write("<red>Aborting.</red>\n\n");
    header("Content-Type: text/html; chrset=utf-8");
    die(unindent("
        <!DOCTYPE HTML>
        <html><head>
        <title>{$current_module->language->messages->twitch->incoming_error}</title>
        </head><body>
        <h1>Error fetching authorization token</h1>
        <p>{$e->getMessage()}</p>
        <p><a href='{$config->full_root_path}'>{$back_link}</a></p>
        </body></html>"
    ));
}

cli_colortags::write("<cyan>OK. Received token data:</cyan>\n");
cli_colortags::write(sprintf("<light_cyan> • Auth token:    %s</light_cyan>\n", substr($twitch_token["access_token"],  0, 4) . "..." . substr($twitch_token["access_token"],  -4)));
cli_colortags::write(sprintf("<light_cyan> • Refresh token: %s</light_cyan>\n", substr($twitch_token["refresh_token"], 0, 4) . "..." . substr($twitch_token["refresh_token"], -4)));
cli_colortags::write(sprintf("<light_cyan> • Expiration:    %s</light_cyan>\n", $twitch_token["expires_in"]));
cli_colortags::write("<cyan>Fetching account info...</cyan>\n");

try
{
    $twitch_user = $client->get_user_account($twitch_token["access_token"]);
}
catch(\Exception $e)
{
    cli_colortags::write("<red>Error fetching token user data:</red>\n");
    cli_colortags::write("<red>{$e->getMessage()}</red>\n");
    cli_colortags::write("<red>Aborting.</red>\n\n");
}

cli_colortags::write("<cyan>OK. {$twitch_user["login"]} data received.</cyan>\n");

$after_login_url = $settings->get("modules:single_signon.twitch_login_ok_redir");
if(empty($after_login_url)) $after_login_url = "{$config->full_root_path}/";

#
# Segment: logging in
#

if( substr($state, 0, 1) == "L")
{
    cli_colortags::write("<light_blue>User attempting to login. Looking up user...</light_blue>\n");
    $account = $client->find_local_account($twitch_user, $twitch_token);
    
    if( is_null($account) )
    {
        # Account not found. Output error with a link to the registration page.
        
        cli_colortags::write("<light_purple>Account not found. Throwing error and exiting.</light_purple>\n\n");
        header("Content-Type: text/html; chrset=utf-8");
        die(unindent("
            <!DOCTYPE HTML>
            <html><head>
            <title>Account not found</title>
            </head><body>
            <h1>Account not found</h1>
            <p>There is no account in our database linked to your Twitch account.</p>
            <p>If you created an account in our website previously and want to link it to your Twitch account,
            please login and go to your account editor.</p>
            <p><a href='{$config->full_root_path}/?show_login_form=true'>Login now</a></p>
            <p>Otherwise, you can create a new account and link it to Twitch from our account creation page.</p>
            <p><a href='{$config->full_root_path}/accounts/register.php'>Register an account now</a></p>
            </body></html>
        "));
    }
    
    # Account found! Let's open the session
    
    $mem_key   = "@single_signon:twitch.alru.$state";
    $mem_res   = $mem_cache->get($mem_key);
    $mem_ttl   = 60 * 60;
    $redir_url = empty($mem_res) ? $after_login_url : $mem_res;
    
    $mem_cache->set($mem_key, $account, 0, $mem_ttl);
    cli_colortags::write("<green>Found account #{$account->id_account} ('{$account->display_name}', @{$account->user_name}).</green>\n");
    cli_colortags::write("<light_green>ALRU {$state} updated.</light_green>\n");
    cli_colortags::write("<green>Opening session and redirecting the user to $redir_url.</green>\n\n");
    
    header("Content-Type: text/html; chrset=utf-8");
    header("Location: {$redir_url}");
    die("<html><body><a href='$redir_url'>{$language->click_here_to_continue}</a></html>");
}

#
# Segment: registering account
#

if( substr($state, 0, 1) == "R" )
{
    cli_colortags::write("<brown>User attempting to register a new account. Looking up user...</brown>\n");
    
    # First let's see if there's another account linked to the twitch user
    $account = $client->find_local_account($twitch_user, $twitch_token);
    
    $mem_key   = "@single_signon:twitch.alru.$state";
    $mem_res   = $mem_cache->get($mem_key);
    $mem_ttl   = 60 * 60;
    $redir_url = empty($mem_res) ? $after_login_url : $mem_res;
    
    if( ! is_null($account) )
    {
        # Account found! Let's open the session
        
        $mem_cache->set($mem_key, $account, 0, $mem_ttl);
        cli_colortags::write("<green>Found account #{$account->id_account} ('{$account->display_name}', @{$account->user_name}).</green>\n");
        cli_colortags::write("<light_green>ALRU {$state} updated.</light_green>\n");
        cli_colortags::write("<green>Session opened. Redirecting the user to $redir_url.</green>\n\n");
        
        header("Content-Type: text/html; chrset=utf-8");
        header("Location: {$redir_url}");
        die("<html><body><a href='$redir_url'>{$language->click_here_to_continue}</a></html>");
    }
    
    #
    # We're clear to register the account.
    #
    
    cli_colortags::write("<brown>No user found. Attempting to create account...</brown>\n");
    try
    {
        $account = $client->create_local_account($twitch_user, $twitch_token);
    }
    catch(\Exception $e)
    {
        cli_colortags::write("<red>Error creating account: {$e->getMessage()}</red>\n");
        cli_colortags::write("<red>Notifying and borting.</red>\n\n");
        header("Content-Type: text/html; chrset=utf-8");
        die(unindent("
            <!DOCTYPE HTML>
            <html><head>
            <title>Account creation error</title>
            </head><body>
            <h1>Unable to create account</h1>
            <p>While attempting to create an account based on your Twitch info, we got the next error:</p>
            <p>{$e->getMessage()}</p>
            <p>Please try again. If the problem persists, head over our contact page and send us a message.</p>
            <p><a href='{$config->full_root_path}/accounts/register.php'>Go back to the registration page.</a></p>
            </body></html>
        "));
    }
    
    $mem_cache->set($mem_key, $account, 0, $mem_ttl);
    cli_colortags::write("<light_green>Found existing account #{$account->id_account} ('{$account->display_name}', @{$account->user_name}).</light_green>\n");
    cli_colortags::write("<light_green>ALRU {$state} updated.</light_green>\n");
    
    # Let's open the session.
    $device = new device($account->id_account);
    if( ! $device->_exists )
    {
        $device->set_new($account);
        $device->state = "enabled";
        $device->save();
    }
    $account->open_session($device);
    
    cli_colortags::write("<light_green>Session opened. Redirecting the user to $redir_url.</light_green>\n\n");
    
    header("Content-Type: text/html; chrset=utf-8");
    header("Location: {$redir_url}");
    die("<html><body><a href='$redir_url'>{$language->click_here_to_continue}</a></html>");
}

#
# Segment: Linking account
#

if( ! $account->_exists ) throw_fake_401();

# The state could be a memcached user id

$mem_key = "@single_signon.temp_token:" . $state;
$mem_res = $mem_cache->get($mem_key);

cli_colortags::write("<white>@{$account->user_name} (#{$account->id_account}) is attempting to link an account.</white>\n");

if( empty($mem_res) )
{
    # It took too long to authenticate.
    
    cli_colortags::write("<light_purple>Memcached account id not found.</light_purple>\n");
    cli_colortags::write("<light_purple>Notifying and borting.</light_purple>\n\n");
    header("Content-Type: text/html; chrset=utf-8");
    die(unindent("
        <!DOCTYPE HTML>
        <html><head>
        <title>Account linking error</title>
        </head><body>
        <h1>Unable to link account</h1>
        <p>We couldn't find the internal token set to identify your account.</p>
        <p>Please try again. If the problem persists, head over our contact page and send us a message.</p>
        <p><a href='{$config->full_root_path}/accounts/edit_account.php'>Go back to account editor.</a></p>
        </body></html>
    "));
}

if( $mem_res != $account->id_account )
{
    cli_colortags::write("<light_purple>Memcached account id mismatch: {$mem_res} != {$account->id_account}.</light_purple>\n");
    cli_colortags::write("<light_purple>Notifying and borting.</light_purple>\n\n");
    header("Content-Type: text/html; chrset=utf-8");
    die(unindent("
        <!DOCTYPE HTML>
        <html><head>
        <title>Account linking error</title>
        </head><body>
        <h1>Unable to link account</h1>
        <p>The internal token tied to your request is not the same as expected.</p>
        <p>Please try again. If the problem persists, head over our contact page and send us a message.</p>
        <p><a href='{$config->full_root_path}/accounts/edit_account.php'>Go back to account editor.</a></p>
        </body></html>
    "));
}

if( ! empty($account->engine_prefs["twitch:username"]) )
{
    cli_colortags::write("<light_purple>Account already linked to another Twitch account: {$account->engine_prefs["twitch:username"]}.</light_purple>\n");
    cli_colortags::write("<light_purple>Notifying and borting.</light_purple>\n\n");
    header("Content-Type: text/html; chrset=utf-8");
    die(unindent("
        <!DOCTYPE HTML>
        <html><head>
        <title>Account linking error</title>
        </head><body>
        <h1>Unable to link account</h1>
        <p>Your Twitch account is already linked to <a href='https://twitch.tv/{$account->engine_prefs["twitch:username"]}'
           target='_blank'>@{$account->engine_prefs["twitch:username"]}</a>.</p>
        <p>Please unlink it if you want to relink it to a different Twitch account.</p>
        <p><a href='{$config->full_root_path}/accounts/edit_account.php'>Go back to account editor.</a></p>
        </body></html>
    "));
}

# Impersonation check

$xaccount = $client->find_local_account($twitch_user, $twitch_token, false);

if( $xaccount->_exists )
{
    cli_colortags::write("<light_purple>Impersonation attempt.</light_purple>\n");
    cli_colortags::write("<light_purple>Open session:</light_purple>\n");
    cli_colortags::write("<light_purple> • #{$account->id_account} (@{$account->user_name} ~ '{$account->display_name}')</light_purple>\n");
    cli_colortags::write("<light_purple>Twitch account:</light_purple>\n");
    cli_colortags::write("<light_purple> • #{$twitch_user["id"]} (@{$twitch_user["login"]} ~ '{$twitch_user["display_name"]}')</light_purple>\n");
    cli_colortags::write("<light_purple>Registered to:</light_purple>\n");
    cli_colortags::write("<light_purple> • #{$xaccount->id_account} (@{$xaccount->user_name} ~ '{$xaccount->display_name}')</light_purple>\n");
    cli_colortags::write("<light_purple>Notifying and borting.</light_purple>\n\n");
    header("Content-Type: text/html; chrset=utf-8");
    die(unindent("
        <!DOCTYPE HTML>
        <html><head>
        <title>Account linking error</title>
        </head><body>
        <h1>Unable to link account</h1>
        <p>Your Twitch account is already linked to <a href='{$config->full_root_path}/user/{$account->user_name}'
           target='_blank'>@{$account->display_name}</a>'s account in our website.</p>
        <p>If you think this is wrong, please head over our contact page and send us a message.</p>
        <p><a href='{$config->full_root_path}/accounts/edit_account.php'>Go back to account editor.</a></p>
        </body></html>
    "));
}

# All good. Let's link it.

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

$after_login_url = "{$config->full_root_url}/accounts/edit_account.php";
cli_colortags::write("<green>Account linked.</green>\n");
cli_colortags::write("<green>Redirecting the user to $after_login_url.</green>\n\n");

header("Content-Type: text/html; chrset=utf-8");
header("Location: {$after_login_url}");
die("<html><body><a href='$after_login_url'>{$language->click_here_to_continue}</a></html>");
