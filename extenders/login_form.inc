<?php
/**
 * Accounts extender: login form additions
 *
 * @package    BardCanvas
 * @subpackage single_signon
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * @var config $config
 * @var module $this_module self
 */

use hng2_base\config;
use hng2_base\module;

global $config, $settings, $mem_cache;

$app_id     = $settings->get("modules:single_signon.facebook_app_id");
$app_secret = $settings->get("modules:single_signon.facebook_app_secret");
$output     = array();

if( ! empty($app_id) && ! empty($app_secret) )
{
    $config->globals["facebook_app_id"]     = $app_id;
    $config->globals["facebook_app_secret"] = $app_secret;
    
    $output[] = "
        <div class=\"aligncenter sso_facebook\" style=\"margin: 10px 0\">
            <div class=\"framed_content pseudo_link\" style=\"color: white; background-color: #3B5998; border: none;\"
                 onclick=\"facebook_login_do( $(this).closest('form'), '{$_GET["redir_url"]}' )\"><i class=\"fa fa-facebook-square\"></i>
                {$this_module->language->login_with_facebook}</div>
        </div>
    
    
    ";
}

$app_id = $settings->get("modules:single_signon.twitch_app_id");

if( ! empty($app_id) )
{
    $state_code = "L" . md5($config->website_key . microtime(true));
    $mem_key    = "@single_signon:twitch.alru.$state_code";
    $mem_ttl    = 60 * 60;
    $mem_cache->set($mem_key, $_SERVER["REQUEST_URI"], 0, $mem_ttl);
    
    $twitch_url = sprintf(
        "https://id.twitch.tv/oauth2/authorize?client_id=%s&response_type=code&scope=%s&redirect_uri=%s&state=%s",
        $app_id,
        urlencode("user:read:email"),
        urlencode("{$this_module->get_url(true)}/scripts/twitch.php"),
        urlencode($state_code)
    );
    
    $output[] = "
        <div class=\"aligncenter sso_twitch\" style=\"margin: 10px 0\">
            <a class=\"framed_content pseudo_link\" style=\"display: block; color: white; background-color: #9146FF; border: none;\"
               href=\"{$twitch_url}\"><i class=\"fa fa-twitch\"></i>
                {$this_module->language->login_with_twitch}</a>
        </div>
    ";
}

$telegram_bot   = str_replace("@", "", $settings->get("modules:single_signon.telegram_bot_username"));
$telegram_token = $settings->get("modules:single_signon.telegram_bot_token");
if( ! empty($telegram_bot) && ! empty($telegram_token) )
{
    $output[] = "
        <div class='aligncenter sso_telegram' style='margin: 10px 0 0 0'>
            <script async src='https://telegram.org/js/telegram-widget.js?22'
                    data-telegram-login='{$telegram_bot}'
                    data-size='large'
                    data-onauth='SSO_telegram_login(user)'
                    data-request-access='write'></script>
        </div>
    ";
}

$client_id     = $settings->get("modules:single_signon.google_client_id");
$client_secret = $settings->get("modules:single_signon.google_client_secret");
if( ! empty($client_id) && ! empty($client_secret) )
{
    $redir_url = $_REQUEST["goto"];
    if( empty($redir_url) ) $redir_url = $_SERVER["REQUEST_URI"];
    $redir_url = urlencode($redir_url);
    $go_url    = "{$this_module->get_url()}/scripts/google.php?mode=login&redir_url=$redir_url";
    $output[] = "
        <div class=\"aligncenter sso_google\" style=\"margin: 10px 0\">
            <a class=\"framed_content pseudo_link\" style=\"display: block; color: black; background: white; border-color: #E64035;\"
               href=\"{$go_url}\"><img src='{$this_module->get_url()}/media/google-icon.png'
                style='position: relative; top: 0.1em; height: 1.2em;'>
                {$this_module->language->login_with_google}</a>
        </div>
    ";
}


if( ! empty($output) )
{
    echo "<div class=\"aligncenter sso_or\" style=\"margin: 10px 0\">";
    echo "————— {$this_module->language->or} —————";
    echo "</div>";
    echo implode("\n", $output);
}