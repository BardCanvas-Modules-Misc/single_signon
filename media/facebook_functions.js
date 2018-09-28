/**
 * Executes a Facebook login
 *
 * @param {jQuery} $form
 * @param {string} redir_url
 */
function facebook_login_do($form, redir_url)
{
    if( typeof redir_url === 'undefined' ) redir_url = '';
    
    $form.block(blockUI_medium_params);
    FB.login(function()
    {
        FB.api('/me', function(response)
        {
            if( response.error )
            {
                alert( response.error.message );
                $form.unblock();
                
                return;
            }
            
            var user = response;
            FB.getLoginStatus(function(response)
            {
                if (response.status !== 'connected')
                {
                    alert('Facebook account is not connected. Aborting login.');
                    $form.unblock();
                    
                    return;
                }
                
                var accessToken = response.authResponse.accessToken;
                
                var url    = $_FULL_ROOT_PATH + '/single_signon/scripts/facebook.php';
                var params = {
                    method:  'login',
                    id:      user.id,
                    token:   accessToken,
                    wasuuup: wasuuup()
                };
                
                $.post(url, params, function(response)
                {
                    if( response != 'OK' )
                    {
                        $form.unblock();
                        alert( response );
                        
                        return;
                    }
                    
                    if( redir_url != '' )
                        location.href = redir_url;
                    else if( location.href.indexOf('/accounts/register.php') >= 0 )
                        location.href = $_FULL_ROOT_PATH + '/?loggedin=true';
                    else
                        location.reload();
                });
            });
        });
    }, {scope: 'email'});
}

function facebook_disconnect($form)
{
    var message = $('#facebook_disconnect_prompt').text();
    if( ! confirm(message) ) return;
    
    var url    = $_FULL_ROOT_PATH + '/single_signon/scripts/facebook.php';
    var params = {
        method:  'unlink',
        wasuuup: wasuuup()
    };
    
    $form.block(blockUI_default_params);
    $.post(url, params, function(response)
    {
        if( response != 'OK' )
        {
            $form.unblock();
            alert( response );
            
            return;
        }
        
        location.reload();
    });
}
