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

if( ! $template->get("facebook_root_div_rendered") )
{
    $app_id = $config->globals["facebook_app_id"];
    if( empty($app_id) ) $app_id = $settings->get("modules:single_signon.facebook_app_id");
    
    if( ! empty($app_id) )
    {
        $config->globals["facebook_app_id"] = $app_id;
        $template->set("facebook_root_div_rendered", true);
        echo "
            
            <!-- Facebook Root div - rendered by fbcomments post-footer -->
            <div id='fb-root'></div>
            
        ";
    }
}
