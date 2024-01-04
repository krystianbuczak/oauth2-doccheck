<?php

declare(strict_types=1);

namespace krystianbuczak\OAuth2\Client\Provider;

use krystianbuczak\OAuth2\Client\Provider\Exception\DocCheckIdentityProviderException;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class DocCheck extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * @var string[] List of available language versions of DocCheck page.
     */
    public array $availableLanguageVersions = [
        'de',
        'com',
        'fr',
        'nl',
        'it',
        'es',
    ];

    /**
     * @var string[] List of available templates of DocCheck login page.
     */
    public array $availableTemplates = [
        'login_s',
        'login_m',
        'login_l',
        'login_xl',
        'fullscreen_dc',
        's_mobile',
    ];

    /**
     * Base URL.
     *
     * @var string
     */
    public string $baseUrl = 'https://login.doccheck.com';

    /**
     * Get authorization url to begin OAuth flow.
     *
     * @return string Authorization url.
     */
    public function getBaseAuthorizationUrl(): string
    {
        return $this->baseUrl . '/code/';
    }

    /**
     * Get access token url to retrieve token
     *
     * @param array $params Query params
     *
     * @return string Access token url.
     */
    public function getBaseAccessTokenUrl(array $params): string
    {
        return $this->baseUrl . '/service/oauth/access_token';
    }

    /**
     * Get provider url to fetch user details
     *
     * @param AccessToken $token
     *
     * @return string Resource owner details url.
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return $this->baseUrl . '/service/oauth/user_data/v2/';
    }

    /**
     * Get the default scopes used by this provider.
     *
     * @param array $options Options to build query string
     *
     * @return array Array of params to be used in authorization url.
     */
    protected function getAuthorizationParameters(array $options): array
    {
        $params = parent::getAuthorizationParameters($options);
        unset($params['scope'], $params['response_type'], $params['approval_prompt']);

        if (isset($params['dc_language']) && !$this->isLanguageAllowed($params['dc_language'])) {
            $params['dc_language'] = 'com';
        }

        if (isset($params['dc_template']) && !$this->isTemplateAllowed($params['dc_template'])) {
            $params['dc_template'] = 's_mobile';
        }

        return $params;
    }

    /**
     * Get the default scopes used by DocCheck provider.
     *
     * The scope of the provisioned data is defined by the consent form set up by the DocCheck support team.
     * @link https://docs.doccheck.com/login-implementation/oauth/configuration.html
     *
     * @return array Default scopes.
     */
    protected function getDefaultScopes(): array
    {
        return [];
    }

    /**
     * Check a provider response for errors.
     *
     * @link   https://docs.doccheck.com/login-implementation/oauth/error-codes.html
     *
     * @throws DocCheckIdentityProviderException|\League\OAuth2\Client\Provider\Exception\IdentityProviderException
     *
     * @param ResponseInterface $response Response to check.
     * @param array $data Parsed response data
     *
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if ($response->getStatusCode() >= 400) {
            throw DocCheckIdentityProviderException::clientException($response, $data);
        }

        if (isset($data['error'])) {
            throw DocCheckIdentityProviderException::oauthException($response, $data);
        }
    }

    /**
     * Generate a user object from a successful user details request.
     *
     * @param array $response API response.
     * @param AccessToken $token Token used to fetch user details.
     *
     * @return ResourceOwnerInterface Resource owner.
     */
    protected function createResourceOwner(array $response, AccessToken $token): ResourceOwnerInterface
    {
        return (new DocCheckResourceOwner($response))->setBaseUrl($this->baseUrl);
    }

    /**
     * Check if language is allowed.
     *
     * @param string $language Language version code.
     *
     * @return bool True if language version is allowed, false otherwise.
     */
    protected function isLanguageAllowed(string $language): bool
    {
        return in_array($language, $this->availableLanguageVersions, true);
    }

    /**
     * Check if template is allowed.
     *
     * @param string $template Template version code.
     *
     * @return bool True if template name is allowed, false otherwise.
     */
    protected function isTemplateAllowed(string $template): bool
    {
        return in_array($template, $this->availableTemplates, true);
    }
}
