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

global $config, $settings;

$app_id = $config->globals["facebook_app_id"];
if( empty($app_id) ) $app_id = $settings->get("modules:single_signon.facebook_app_id");
if( empty($app_id) ) return;

$app_secret = $settings->get("modules:single_signon.facebook_app_secret");
if( empty($app_secret) ) return;

$config->globals["facebook_app_id"]     = $app_id;
$config->globals["facebook_app_secret"] = $app_secret;
?>

<div class="aligncenter" style="margin: 10px 0">
    <div class="framed_content pseudo_link" style="color: white; background-color: #3B5998; border: none;"
          onclick="facebook_login_do( $(this).closest('form') )"><i class="fa fa-facebook-square"></i>
          <?= $this_module->language->login_with_facebook ?></div>
</div>
