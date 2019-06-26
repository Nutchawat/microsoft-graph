<?php

namespace App\TokenStore;

class TokenCache {

  protected $oauthClient;

  public function __construct()
  {
    // Initialize the OAuth client
    $this->oauthClient = new \League\OAuth2\Client\Provider\GenericProvider([
      'clientId'                => env('OAUTH_APP_ID'),
      'clientSecret'            => env('OAUTH_APP_PASSWORD'),
      'redirectUri'             => env('OAUTH_REDIRECT_URI'),
      'urlAuthorize'            => env('OAUTH_AUTHORITY').env('OAUTH_AUTHORIZE_ENDPOINT'),
      'urlAccessToken'          => env('OAUTH_AUTHORITY').env('OAUTH_TOKEN_ENDPOINT'),
      'urlResourceOwnerDetails' => '',
      'scopes'                  => env('OAUTH_SCOPES')
    ]);
  }

  public function storeTokens($accessToken)
  {
    session([
      'microsoft-graph-accessToken' => $accessToken->getToken(),
      'microsoft-graph-refreshToken' => $accessToken->getRefreshToken(),
      'microsoft-graph-tokenExpires' => $accessToken->getExpires(),
    ]);
  }

  public function clearTokens()
  {
    session()->forget('microsoft-graph-accessToken');
    session()->forget('microsoft-graph-refreshToken');
    session()->forget('microsoft-graph-tokenExpires');
  }

  public function getAccessToken()
  {
    // Check if tokens exist
    if (empty(session('microsoft-graph-accessToken')) ||
        empty(session('microsoft-graph-refreshToken')) ||
        empty(session('microsoft-graph-tokenExpires'))) {
      try {
        $token = $this->oauthClient->getAccessToken('password', [
          'username' => env('OAUTH_USERNAME'),
          'password' => env('OAUTH_PASSWORD'),
          'scope'    => env('OAUTH_SCOPES')
        ]);

        $this->storeTokens($token);

        return $token->getToken();
      }
      catch (League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
        return '';
      }
    } else {
      $now = time() + 300;
      if (session('microsoft-graph-tokenExpires') <= $now) {
        try {
          $newToken = $this->oauthClient->getAccessToken('refresh_token', [
            'refresh_tograph-ken' => session('microsoft-graph-refreshToken')
          ]);

          $this->storeTokens($newToken);

          return $newToken->getToken();
        }
        catch (League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
          return '';
        }
      }

      return session('microsoft-graph-accessToken');
    }

    return '';
  }
}