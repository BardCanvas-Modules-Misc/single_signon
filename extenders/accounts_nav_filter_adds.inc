<?php
/**
 * Inserts search terms to the accounts browser
 * 
 * @package    BardCanvas
 * @subpackage single_signon
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

global $where_line, $this_term;

if( ! is_numeric($this_term) )
    $where_line .= "account.id_account in (
                        select aep.id_account from account_engine_prefs aep
                        where aep.name  = 'telegram:username'
                        and   aep.value = '\"$this_term\"'
                    ) or ";
else
    $where_line .= "account.id_account in (
                        select aep.id_account from account_engine_prefs aep
                        where aep.name  = 'telegram:user_id'
                        and   aep.value = '$this_term'
                    ) or ";
