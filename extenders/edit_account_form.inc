<?php
/**
 * Accounts extender: Edit form additions
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

global $config, $settings, $template, $account, $xaccount, $mem_cache;

if( $template->layout == "admin" )
{
    # Segment: This is an admin editing an account manually.
    
    $segments = array();
    
    if( ! empty($xaccount->engine_prefs["facebook:id"]) )
    {
        $segments["facebook"] = "
            <p>
                {$this_module->language->facebook->user_connected}
            </p>
            <p>
                <a href='https://www.facebook.com/{$xaccount->engine_prefs["facebook:id"]}'
                   target='_blank'><i class='fa fa-external-link-square'></i>
                   {$this_module->language->facebook->go_to_user_facebook_page}</a>
            </p>
        ";
    }
    
    if( ! empty($xaccount->engine_prefs["twitch:username"]) )
    {
        $segments["twitch"] = "
            <p>
                {$this_module->language->twitch->user_connected}
            </p>
            <p>
                <a href='https://twitch.tv/{$xaccount->engine_prefs["twitch:username"]}'
                   target='_blank'><i class='fa fa-external-link-square'></i>
                   {$this_module->language->twitch->go_to_user_twitch_page}</a>
            </p>
        ";
    }
    
    if( ! empty($segments) )
    {
        $cols  = count($segments);
        $class = $cols <= 1 ? "" : "multicol";
        if( $cols >= 3 ) $class .= " cols-3";
        else if( $cols > 1 ) $class .= " cols-2";
        ?>
            <!-- Single Sign On links -->
            
            <div class="sso_connect framed_content aligncenter <?= $class ?>">
                <?
                foreach($segments as $provider => $text)
                {
                    $class = $cols > 1 ? "col" : "";
                    $class .= "$provider ";
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
    
    return;
}

# Segment: This is the user editing their account.

$segments = array();

$app_id = $settings->get("modules:single_signon.facebook_app_id");
if( ! empty($app_id) )
{
    if( empty($account->engine_prefs["facebook:id"]) )
    {
        $segments["facebook"] = "
            <p>
                {$this_module->language->facebook->not_connected}
            </p>
            <p>
                <span class='framed_content pseudo_link' style='color: white; background-color: #3B5998; border: none;'
                      onclick=\"facebook_login_do( $(this).closest('form') )\"><i class='fa fa-facebook-square'></i>
                      {$this_module->language->facebook->connect}</span>
            </p>
        ";
    }
    else
    {
        $prompt = unindent($this_module->language->facebook->disconnect_prompt);
        $segments["facebook"] = "
            <p>
                {$this_module->language->facebook->already_connected}
            </p>
            <p>
                <a href='https://www.facebook.com/{$account->engine_prefs["facebook:id"]}'
                   target='_blank'><i class='fa fa-external-link-square'></i>
                   {$this_module->language->facebook->go_to_my_facebook_page}</a>
            </p>
            <p>
                <span class='pseudo_link' onclick=\"facebook_disconnect( $(this).closest('form') )\"><i class='fa fa-unlink'></i>
                      {$this_module->language->facebook->disconnect}</span>
            </p>
            <div id='facebook_disconnect_prompt' style='display: none;'>$prompt</div>
        ";
    }
}

$app_id = $settings->get("modules:single_signon.twitch_app_id");
if( ! empty($app_id) )
{
    if( empty($account->engine_prefs["twitch:username"]) )
    {
        $sess_token = "B" . md5($config->website_key . "/single_signon/twitch/" . microtime(true));
        $mem_key    = "@single_signon.temp_token:" . $sess_token;
        $mem_data   = $account->id_account;
        $mem_ttl    = 60 * 60;
        $mem_cache->set($mem_key, $mem_data, 0, $mem_ttl);
        
        $state_code = $sess_token;
        $twitch_url = sprintf(
            "https://id.twitch.tv/oauth2/authorize?client_id=%s&response_type=code&scope=%s&redirect_uri=%s&state=%s",
            $app_id,
            urlencode("user:read:email"),
            urlencode("{$this_module->get_url(true)}/scripts/twitch.php"),
            urlencode($state_code)
        );
        
        $segments["twitch"] = "
            <p>
                {$this_module->language->twitch->not_connected}
            </p>
            <p>
                <a class='framed_content pseudo_link' style='color: white; background-color: #9146FF; border: none;'
                   href='$twitch_url'><i class=\"fa fa-twitch\"></i>
                   {$this_module->language->twitch->connect}</a>
            </p>
        ";
    }
    else
    {
        $prompt = unindent($this_module->language->twitch->disconnect_prompt);
        $segments["twitch"] = "
            <p>
                {$this_module->language->twitch->already_connected}
            </p>
            <p>
                <a href='https://twitch.tv/{$account->engine_prefs["twitch:username"]}'
                   target='_blank'><i class='fa fa-external-link-square'></i>
                   {$this_module->language->twitch->go_to_my_twitch_page}</a>
            </p>
            <p>
                <span class='pseudo_link' onclick=\"twitch_disconnect( $(this).closest('form') )\"><i class='fa fa-unlink'></i>
                      {$this_module->language->twitch->disconnect}</span>
            </p>
            <div id='twitch_disconnect_prompt' style='display: none;'>$prompt</div>
        ";
    }
}

if( ! empty($segments) )
{
    $cols  = count($segments);
    $class = $cols <= 1 ? "" : "multicol";
    if( $cols >= 3 ) $class .= " cols-3";
    else if( $cols > 1 ) $class .= " cols-2";
    ?>
        <!-- Single Sign On links -->
        
        <div class="sso_connect framed_content aligncenter <?= $class ?>">
            <?
            foreach($segments as $provider => $text)
            {
                $class = $cols > 1 ? "col" : "";
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
