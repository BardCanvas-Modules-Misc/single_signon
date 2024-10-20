
function SSO_google_unlink()
{
    if( ! confirm($_GENERIC_CONFIRMATION) ) return;
    
    let url = $_FULL_ROOT_PATH + '/single_signon/scripts/google.php?mode=unlink';
    
    $.blockUI(blockUI_default_params);
    $.get(url, function(response)
    {
        if( response !== 'OK' )
        {
            $.unblockUI();
            throw_notification(response, 'error');
            return;
        }
        
        location.href = '/accounts/edit_account.php';
    });
}
