<?php

namespace App\TokenStore;

class TokenCache {

  protected $site;
  protected $oauthClient;

  public function __construct($email)
  {
    $this->site = explode("@", $email)[1];

    if ($this->site == "neo-corporate.com")
    {
      $tenant_id = env('OAUTH_TENANT_ID_NEOC');
      $client_id = env('OAUTH_APP_ID_NEOC');
      $client_secret = env('OAUTH_APP_PASSWORD_NEOC');
    } else if ($this->site == "neo-factory.biz")
    {
      $tenant_id = env('OAUTH_TENANT_ID_NEOF');
      $client_id = env('OAUTH_APP_ID_NEOF');
      $client_secret = env('OAUTH_APP_PASSWORD_NEOF');
    }
    // Initialize the OAuth client
    $this->oauthClient = new \League\OAuth2\Client\Provider\GenericProvider([
      'clientId'                => $client_id,
      'clientSecret'            => $client_secret,
      'redirectUri'             => env('OAUTH_REDIRECT_URI'),
      'urlAuthorize'            => env('OAUTH_AUTHORITY').$tenant_id.env('OAUTH_AUTHORIZE_ENDPOINT'),
      'urlAccessToken'          => env('OAUTH_AUTHORITY').$tenant_id.env('OAUTH_TOKEN_ENDPOINT'),
      'urlResourceOwnerDetails' => '',
      'scopes'                  => env('OAUTH_SCOPES')
    ]);
  }

  public function storeTokens($accessToken)
  {
    session([
      'microsoft-graph-accessToken-'.$this->site => $accessToken->getToken(),
      'microsoft-graph-refreshToken-'.$this->site => $accessToken->getRefreshToken(),
      'microsoft-graph-tokenExpires-'.$this->site => $accessToken->getExpires(),
    ]);
  }

  public function clearTokens()
  {
    session()->forget('microsoft-graph-accessToken-'.$this->site);
    session()->forget('microsoft-graph-refreshToken-'.$this->site);
    session()->forget('microsoft-graph-tokenExpires-'.$this->site);
  }

  public function getAccessToken()
  {
    // Check if tokens exist
    if (empty(session('microsoft-graph-accessToken-'.$this->site)) ||
        empty(session('microsoft-graph-refreshToken-'.$this->site)) ||
        empty(session('microsoft-graph-tokenExpires-'.$this->site))) {
      try {
        $token = $this->oauthClient->getAccessToken('client_credentials', [
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
      if (session('microsoft-graph-tokenExpires-'.$this->site) <= $now) {
        try {
          $newToken = $this->oauthClient->getAccessToken('refresh_token', [
            'refresh_token' => session('microsoft-graph-refreshToken-'.$this->site)
          ]);

          $this->storeTokens($newToken);

          return $newToken->getToken();
        }
        catch (League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
          return '';
        }
      }

      return session('microsoft-graph-accessToken-'.$this->site);
    }

    return '';
  }
}