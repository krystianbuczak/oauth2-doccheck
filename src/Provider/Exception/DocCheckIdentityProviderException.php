<?php

namespace krystianbuczak\OAuth2\Client\Provider\Exception;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Message\ResponseInterface;

final class DocCheckIdentityProviderException extends IdentityProviderException
{
    /**
     * Creates client exception from response.
     *
     * @param ResponseInterface $response
     * @param array<string> $data Parsed response data
     *
     * @return IdentityProviderException
     */
    public static function clientException(ResponseInterface $response, array $data): IdentityProviderException
    {
        return self::fromResponse(
            $response,
            $data['error_description'] ?? $response->getReasonPhrase()
        );
    }

    /**
     * Creates oauth exception from response.
     *
     * @param ResponseInterface $response
     * @param array<string> $data Parsed response data
     *
     * @return IdentityProviderException
     */
    public static function oauthException(ResponseInterface $response, array $data): IdentityProviderException
    {
        return self::fromResponse(
            $response,
            $data['error_description'] ?? $response->getReasonPhrase()
        );
    }

    /**
     * Creates identity exception from response.
     *
     * @param ResponseInterface $response
     * @param string|null $message
     *
     * @return IdentityProviderException
     */
    protected static function fromResponse(
        ResponseInterface $response,
        ?string $message = null
    ): IdentityProviderException {
        return new self($message, $response->getStatusCode(), (string)$response->getBody());
    }
}
