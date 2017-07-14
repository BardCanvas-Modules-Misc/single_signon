<?php
/**
 * Pre template rendering
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

global $settings, $template, $account;

if( $template->layout == "embeddable" ) return;
if( $template->layout == "admin" ) return;

if( empty($_SESSION) ) session_start();
$_SESSION["single_signon_csrf_token"] = uniqid("", true);

if( ! empty($template->page_extra_metas["fb:app_id"]) ) return;

$app_id = $config->globals["facebook_app_id"];
if( empty($app_id) ) $app_id = $settings->get("modules:single_signon.facebook_app_id");
if( empty($app_id) ) return;

$config->globals["facebook_app_id"] = $app_id;
$template->page_extra_metas["fb:app_id"] = "<meta property='fb:app_id' content='$app_id'>";
