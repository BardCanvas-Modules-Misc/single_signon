<?php
/**
 * Styles and scripts for the module
 *
 * @package    BardCanvas
 * @subpackage single_signon
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * @var config   $config
 * @var module   $this_module
 * @var template $template
 *
 * @var post_record  $post
 * @var media_record $item
 */

use hng2_base\config;
use hng2_base\module;
use hng2_base\template;
use hng2_media\media_record;
use hng2_modules\posts\post_record;

global $settings, $template;

if( $template->layout == "embeddable" ) return;
if( $template->layout == "admin" ) return;

$app_id = $config->globals["facebook_app_id"];
if( empty($app_id) ) $app_id = $settings->get("modules:single_signon.facebook_app_id");
$app_secret = $settings->get("modules:single_signon.facebook_app_secret");

if( ! empty($app_id) && ! empty($app_secret) )
{
    $config->globals["facebook_app_id"]     = $app_id;
    $config->globals["facebook_app_secret"] = $app_secret;
    
    if( ! $template->get("facebook_js_sdk_loaded") )
    {
        $lang = $settings->get("engine.default_language");
        $template->set("facebook_js_sdk_loaded", true); ?>
        
        <!-- Facebook SDK - preloaded by single_signon -->
        
        <script>
            (function(d, s, id) {
                var js, fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id)) return;
                js = d.createElement(s); js.id = id;
                js.src = "//connect.facebook.net/<?= $lang ?>/sdk.js#xfbml=1&version=v2.9&appId=<?= $app_id ?>";
                fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'facebook-jssdk'));
        </script>
        
        <!-- Facebook Single Sign On functions -->
        
        <script type="text/javascript" src="<?= $config->full_root_path ?>/single_signon/media/facebook_functions~v1.0.2-1.js"></script>
        
        <?
    }
}
