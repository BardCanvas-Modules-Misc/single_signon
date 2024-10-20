<?php
/**
 * Accounts extender: Register form additions
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

global $config, $settings, $template, $account, $xaccount;

# This is an admin creating an account manually.
if( $template->layout == "admin" ) return;

$segments = array();

$app_id = $settings->get("modules:single_signon.facebook_app_id");
if( ! empty($app_id) )
{
    $segments["facebook"] = "
        <p>
            {$this_module->language->facebook->invitation}
        </p>
        <p>
            <span class='framed_content pseudo_link' style='color: white; background-color: #3B5998; border: none;'
                 onclick=\"facebook_login_do( $(this).closest('form'), '{$_GET["redir_url"]}' )\"><i class=\"fa fa-facebook-square\"></i>
                {$this_module->language->facebook->connect}</span>
        </p>
    ";
}

$app_id = $settings->get("modules:single_signon.twitch_app_id");
if( ! empty($app_id) )
{
    $state_code = "R" . md5($config->website_key . microtime(true));
    $twitch_url = sprintf(
        "https://id.twitch.tv/oauth2/authorize?client_id=%s&response_type=code&scope=%s&redirect_uri=%s&state=%s",
        $app_id,
        urlencode("user:read:email"),
        urlencode("{$this_module->get_url(true)}/scripts/twitch.php"),
        urlencode($state_code)
    );
    
    $segments["twitch"] = "
        <p>
            {$this_module->language->twitch->invitation}
        </p>
        <p>
            <a class='framed_content pseudo_link' style='color: white; background-color: #9146FF; border: none;'
               href='$twitch_url'><i class=\"fa fa-twitch\"></i>
               {$this_module->language->twitch->connect}</a>
        </p>
    ";
}


$telegram_bot   = str_replace("@", "", $settings->get("modules:single_signon.telegram_bot_username"));
$telegram_token = $settings->get("modules:single_signon.telegram_bot_token");
if( ! empty($telegram_bot) && ! empty($telegram_token) )
{
    $segments["telegram"] = "
        <p>
            {$this_module->language->telegram->invitation}
        </p>
        <p>
            <script async src='https://telegram.org/js/telegram-widget.js?22'
                    data-telegram-login='{$telegram_bot}'
                    data-size='medium'
                    data-onauth='SSO_telegram_register(user)'
                    data-request-access='write'></script>
        </p>
    ";
}

$client_id     = $settings->get("modules:single_signon.google_client_id");
$client_secret = $settings->get("modules:single_signon.google_client_secret");
if( ! empty($client_id) && ! empty($client_secret) )
{
    $redir_url = urlencode($_REQUEST["redir_url"]);
    $segments["google"] = "
        <p>
            {$this_module->language->google->invitation}
        </p>
        <p>
            <a class='framed_content pseudo_link' style='color: black; background-color: white; border-color: #E64035;'
               href='{$this_module->get_url()}/scripts/google.php?mode=register&redir_url=$redir_url'
               ><img src='{$this_module->get_url()}/media/google-icon.png' style='vertical-align: top; height: 1.2em;'>
               {$this_module->language->google->connect}</a>
        </p>
    ";
}



if( ! empty($segments) )
{
    if( $account->_exists )
    {
        $supported = ucwords(implode(" / ", array_keys($segments)));
        ?>
            <!-- Single Sign On - already logged in -->
            
            <div class="sso_connect framed_content  state_highlight aligncenter">
                <i class="fa fa-warning"></i>
                <?= sprintf($this_module->language->logged_in->info, $supported) ?>
                <br><br>
                <a class="framed_content inlined state_ok go_to_my_profile"
                   href="<?= $config->full_root_path ?>/accounts/edit_account.php">
                    <i class="fa fa-pencil-square-o"></i>
                    <?= $this_module->language->logged_in->go_to_my_account ?>
                </a>
                &nbsp;
                <a class="framed_content inlined state_ko logout"
                   href="<?= $config->full_root_path ?>/accounts/scripts/logout.php">
                    <i class="fa fa-sign-out"></i>
                    <?= $this_module->language->logged_in->logout ?>
                </a>
            </div>
        <?
    }
    else
    {
        $cols  = count($segments);
        $class = $cols <= 1 ? "" : "multicol";
        if( $cols >= 3 ) $class .= " cols-3";
        else if( $cols > 1 ) $class .= " cols-2";
        ?>
            <!-- Single Sign On connect -->
            
            <div class="sso_connect framed_content aligncenter <?= $class ?>">
                <?
                foreach($segments as $provider => $text)
                {
                    $class  = $cols > 1 ? "col" : "";
                    $class .= " " . $provider;
                    ?>
                    <div class="<?= $class ?>">
                        <?= $text ?>
                    </div>
                    <?
                }
                ?>
            </div>
        <?
    }
}
