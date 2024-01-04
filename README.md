# DocCheck Provider for OAuth 2.0 Client

This package provides [DocCheck](https://www.doccheck.com/) OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

To install, use composer:

```
composer require krystianbuczak/oauth2-doccheck
```

## Usage

Usage is the same as The League's OAuth client, using `\krystianbuczak\OAuth2\Client\Provider\DocCheck` as the provider.

### Authorization Code Flow

```php
$provider = new krystianbuczak\OAuth2\Client\Provider\DocCheck([
    'clientId'          => '{DocCheck-client-id}',
    'clientSecret'      => '{DocCheck-client-secret}',
    'redirectUri'       => 'https://example.com/callback-url',
]);

if (!isset($_GET['code'])) {

    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: '.$authUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    // Optional: Now you have a token you can look up a users profile data
    try {

        // We got an access token, let's now get the user's details
        $user = $provider->getResourceOwner($token);

        // Use these details to create a new profile
        printf('Hello %s!', $user->getFirstName());

    } catch (Exception $e) {

        // Failed to get user details
        exit('Oh dear...');
    }

    // Use this to interact with an API on the users behalf
    echo $token->getToken();
}
```


### Retrieving DocCheck user data

Once you have an access token, you can retrieve the current user's profile data using the `getResourceOwner` method:

```php
$member = $provider->getResourceOwner($token);
```

The `getResourceOwner` will return an instance of `krystianbuczak\OAuth2\Client\Provider\DocCheckResourceOwner` which has some helpful getter methods to access basic member details.

#### A note about obtaining the resource owner's email address

> The email has to be fetched by the provider in a separate request, it is not one of the profile fields.

```php
$user = $provider->getResourceOwner($token);
$email = $user->getEmail();
```

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## Credits

- [Krystian Buczak](https://github.com/krystianbuczak)


## License

The MIT License (MIT). Please see [License File](https://github.com/krystianbuczak/oauth2-doccheck/blob/master/LICENSE) for more information.
