<?php
/**
 * Inserts Telegram account info on the account row
 * 
 * @package    BardCanvas
 * @subpackage single_signon
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 * 
 * @var account_record $row
 */

use hng2_base\account;
use hng2_base\account_record;

global $row;

$ta  = new account($row->id_account);
$un = $ta->engine_prefs["telegram:username"];
if( empty($un) ) $un = $ta->engine_prefs["telegram:user_id"];

if( ! empty($un) )
{
    if( is_numeric($un) )
        echo "
            <span class='framed_content inlined nowrap tg_user clipboard-copy pseudo_link'
               data-clipboard-text='$un'
               style='margin-top: 5px; border-color: navy !important; color: navy !important; background: white !important;'
               ><i class='fa fa-telegram fa-fw'></i>$un<i class='fa fa-copy fa-fw'></i></span>
        ";
    else
        echo "
            <a class='framed_content inlined nowrap tg_user'
               href='https://t.me/$un' target='_blank'
               style='margin-top: 5px; border-color: navy !important; color: navy !important; background: white !important;'
               ><i class='fa fa-telegram fa-fw'></i>$un</a>
        ";
}
