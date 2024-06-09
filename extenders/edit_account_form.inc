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

global $config, $settings, $template, $account, $xaccount;

$app_id = $settings->get("modules:single_signon.facebook_app_id");

if( ! empty($app_id) )
{
    # This is an admin editing an account manually.
    if( $template->layout == "admin" )
    {
        if( ! empty($xaccount->engine_prefs["facebook:id"]) ):
            ?>
            
            <!-- Single Sign On admin info -->
            
            <div class="sso_connect framed_content aligncenter">
                <p>
                    <?= $this_module->language->facebook->user_connected ?>
                </p>
                <p>
                    <a href="https://www.facebook.com/<?= $xaccount->engine_prefs["facebook:id"] ?>"
                       target="_blank"><i class="fa fa-external-link-square"></i>
                        <?= $this_module->language->facebook->go_to_user_facebook_page ?></a>
                </p>
            </div>
            
            <?
        endif;
        
        return;
    }
    ?>
    
    <!-- Single Sign On connect/disconnect -->
    
    <div class="sso_connect framed_content aligncenter">
        <? if( empty($account->engine_prefs["facebook:id"]) ): ?>
            
            <p>
                <?= $this_module->language->facebook->not_connected ?>
            </p>
            <p>
                <span class="framed_content pseudo_link" style="color: white; background-color: #3B5998; border: none;"
                     onclick="facebook_login_do( $(this).closest('form') )"><i class="fa fa-facebook-square"></i>
                    <?= $this_module->language->facebook->connect ?></span>
            </p>
            
        <? else: ?>
            
            <p>
                <?= $this_module->language->facebook->already_connected ?>
            </p>
            <div class="multicol cols-2 aligncenter">
                <div class="col">
                    <p>
                        <a href="https://www.facebook.com/<?= $account->engine_prefs["facebook:id"] ?>"
                           target="_blank"><i class="fa fa-external-link-square"></i>
                            <?= $this_module->language->facebook->go_to_my_facebook_page ?></a>
                    </p>
                </div>
                <div class="col">
                    <p>
                        <span class="pseudo_link" onclick="facebook_disconnect( $(this).closest('form') )"><i class="fa fa-unlink"></i>
                            <?= $this_module->language->facebook->disconnect ?></span>
                    </p>
                </div>
            </div>
            
            <div id="facebook_disconnect_prompt" style="display: none;"><?= unindent($this_module->language->facebook->disconnect_prompt) ?></div>
            
        <? endif; ?>
    </div>
    <?
}
