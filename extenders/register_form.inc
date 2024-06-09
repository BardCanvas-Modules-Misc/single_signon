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

$app_id = $settings->get("modules:single_signon.facebook_app_id");
if( ! empty($app_id) )
{
    ?>
    
    <!-- Single Sign On connect -->
    
    <div class="sso_connect framed_content aligncenter">
        
        <p>
            <?= $this_module->language->facebook->invitation ?>
        </p>
        <p>
            <span class="framed_content pseudo_link" style="color: white; background-color: #3B5998; border: none;"
                 onclick="facebook_login_do( $(this).closest('form'), '<?= $_GET["redir_url"] ?>' )"><i class="fa fa-facebook-square"></i>
                <?= $this_module->language->facebook->connect ?></span>
        </p>
        
    </div>
    
    <?
}
