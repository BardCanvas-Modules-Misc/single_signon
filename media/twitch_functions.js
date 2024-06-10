
function twitch_disconnect($form)
{
    var message = $('#twitch_disconnect_prompt').text();
    if( ! confirm(message) ) return;
    
    var url    = $_FULL_ROOT_PATH + '/single_signon/scripts/twitch.php';
    var params = {
        method:  'unlink',
        wasuuup: wasuuup()
    };
    
    $form.block(blockUI_default_params);
    $.post(url, params, function(response)
    {
        if( response !== 'OK' )
        {
            $form.unblock();
            alert( response );
            
            return;
        }
        
        location.reload();
    });
}
