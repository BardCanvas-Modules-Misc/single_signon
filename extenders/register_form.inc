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
?>

<!-- Single Sign On connect -->

<div class="sso_connect framed_content aligncenter">
    <? if( empty($account->engine_prefs["facebook:id"]) ): ?>
        
        <p>
            <?= $this_module->language->facebook->invitation ?>
        </p>
        <p>
            <span class="framed_content pseudo_link" style="color: white; background-color: #3B5998; border: none;"
                 onclick="facebook_login_do( $(this).closest('form'), '<?= $_GET["redir_url"] ?>' )"><i class="fa fa-facebook-square"></i>
                <?= $this_module->language->facebook->connect ?></span>
        </p>
        
    <? else: ?>
        
        <p>
            <?= $this_module->language->facebook->already_connected ?>
        </p>
        <p>
            <span class="framed_content state_ko">
                <i class="fa fa-info-circle"></i>
                <?= $this_module->language->facebook->cannot_connect_other ?>
            </span>
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
                    <a href="<?= $config->full_root_path ?>/accounts/edit_account.php"
                    ><i class="fa fa-pencil-square-o"></i>
                        <?= $this_module->language->edit_my_profile ?></a>
                </p>
            </div>
        </div>
        
    <? endif; ?>
</div>
