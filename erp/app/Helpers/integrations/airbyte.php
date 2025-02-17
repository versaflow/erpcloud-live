<?php

function getMicrosoftRefreshToken() {
    /*
    get code
    
    https://login.microsoftonline.com/common/oauth2/v2.0/authorize?
client_id=a7e182e3-0b55-43c8-8d0a-1c2fb5ce49d8
&response_type=code
&redirect_uri=https%3A%2F%2Fcloudtelecoms.co.za
&response_mode=query
&scope=openid%20offline_access%20https%3A%2F%2Fads.microsoft.com%2Fmsads.manage
&state=12345
    */
    $clientId     = 'a7e182e3-0b55-43c8-8d0a-1c2fb5ce49d8';
    $clientSecret = 'RGS8Q~sL0WZlZos0~p--URHAR6ZH6vdCA3L.ycAm';
    $redirectUri  = 'https://cloudtelecoms.co.za';
    $code         = 'M.C106_BL2.2.e631e64e-9226-c2bb-bd09-69916cf13932';
    $tenantId     = 'common';
    // Microsoft Graph API OAuth token endpoint
    $tokenEndpoint = "https://login.microsoftonline.com/$tenantId/oauth2/v2.0/token";

    // Request parameters
    $params = array(
        'client_id'     => $clientId,
        'scope'         => 'https://ads.microsoft.com/msads.manage',
        'code'          => $code,
        'redirect_uri'  => $redirectUri,
        'grant_type'    => 'authorization_code',
        'client_secret' => $clientSecret,
    );

    // Initialize cURL session
    $ch = curl_init($tokenEndpoint);

    // Set cURL options
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute cURL session
    $response = curl_exec($ch);

    // Close cURL session
    curl_close($ch);

    // Decode the JSON response
    $tokenData = json_decode($response, true);

    // Check for errors
    if (isset($tokenData['error'])) {
        // Handle error
        return false;
    } else {
        // Return the refresh token
        return $tokenData['refresh_token'];
    }
}


?>
