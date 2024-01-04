<?php

declare(strict_types=1);

namespace krystianbuczak\OAuth2\Client\Provider;

use krystianbuczak\OAuth2\Client\Provider\Exception\DocCheckIdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

class DocCheckResourceOwner implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;

    /**
     * Fallback gender value.
     */
    public const DEFAULT_GENDER = 'u';

    /**
     * Mapped gender labels.
     *
     * @var string[]
     *
     * @link https://docs.doccheck.com/login-implementation/oauth/user_data_endpoint_return_values.html
     */
    protected array $genderLabels = [
        'm' => 'male',
        'f' => 'female',
        'c' => 'company',
        'o' => 'other',
        'u' => 'unknown',
    ];

    /**
     * Base URL
     *
     * @var string
     */
    protected string $baseUrl;

    /**
     * Raw response
     *
     * @var array
     */
    protected array $response;

    /**
     * Creates new resource owner.
     *
     * @param array $response
     */
    public function __construct(array $response = [])
    {
        $this->response = $response;
    }

    /**
     * Get resource owner city.
     *
     * @return string|null City name string (max 255 chars) or null if not set.
     */
    public function getCity(): ?string
    {
        $value = $this->getValueByKey($this->response, 'address_city');
        return empty($value) ? $value : html_entity_decode($value);
    }

    /**
     * Get resource owner country ID.
     *
     * @link https://service.doccheck.com/service/info/codes.php?scope=country&language=com
     *
     * @return int|null Country ID or null if not set.
     */
    public function getCountryId(): ?int
    {
        $value = $this->getValueByKey($this->response, 'address_country_id');
        return $value !== null ? (int)$value : null;
    }

    /**
     * Get resource owner country ISO.
     *
     * @return string|null Country ISO code uppercase string (max 2 chars) or null if not set.
     */
    public function getCountryIso(): ?string
    {
        return $this->getValueByKey($this->response, 'address_country_iso');
    }

    /**
     * Get resource owner gender.
     *
     * @param bool $returnLabel
     *
     * @return string Gender value (m/f/c/o/u) or full label.
     *
     * @throws DocCheckIdentityProviderException
     */
    public function getGender(bool $returnLabel = false): string
    {
        $value = $this->getValueByKey($this->response, 'address_gender', self::DEFAULT_GENDER);
        if (!$returnLabel) {
            return $value;
        }
        if (isset($this->genderLabels[$value])) {
            $value = $this->genderLabels[$value];
        } else {
            throw new DocCheckIdentityProviderException(
                'Unexpected gender label. Allowed values are: m/f/c/o/u.',
                0,
                $this->response
            );
        }
        return $value;
    }

    /**
     * Get resource owner email.
     *
     * @return string|null Email address string (max 255 chars) or null if not set.
     */
    public function getEmail(): ?string
    {
        return $this->getValueByKey($this->response, 'email');
    }

    /**
     * Get resource owner first name.
     *
     * @return string|null First name string (max 255 chars) or null if not set.
     */
    public function getFirstName(): ?string
    {
        $value = $this->getValueByKey($this->response, 'address_name_first');
        return empty($value) ? $value : html_entity_decode($value);
    }

    /**
     * Get resource owner last name.
     *
     * @return string|null Last name string (max 255 chars) or null if not set.
     */
    public function getLastName(): ?string
    {
        $value = $this->getValueByKey($this->response, 'address_name_last');
        return empty($value) ? $value : html_entity_decode($value);
    }

    /**
     * Get resource owner title prefix.
     *
     * @return string|null Title string (max 255 chars) or null if not set.
     */
    public function getTitle(): ?string
    {
        $value = $this->getValueByKey($this->response, 'address_name_title');
        return empty($value) ? $value : html_entity_decode($value);
    }

    /**
     * Get resource owner postal code.
     *
     * @return string|null Postal code string (max 255 chars) or null if not set.
     */
    public function getPostalCode(): ?string
    {
        return $this->getValueByKey($this->response, 'address_postal_code');
    }

    /**
     * Get resource owner street.
     *
     * @return string|null Street string (max 255 chars) or null if not set.
     */
    public function getStreet(): ?string
    {
        $value = $this->getValueByKey($this->response, 'address_street');
        return empty($value) ? $value : html_entity_decode($value);
    }

    /**
     * Get resource owner occupation discipline ID.
     *
     * @link https://service.doccheck.com/service/info/codes_v2.php?scope=discipline&language=en
     *
     * @return int|null Discipline (DocCheck ID) or null if not set.
     */
    public function getOccupationDisciplineId(): ?int
    {
        $value = $this->getValueByKey($this->response, 'occupation_discipline_id');
        return $value !== null ? (int)$value : null;
    }

    /**
     * Get resource owner occupation profession ID.
     *
     * @link https://service.doccheck.com/service/info/codes_v2.php?scope=discipline&language=en
     *
     * @return int|null Profession category (DocCheck ID) or null if not set.
     */
    public function getOccupationProfessionId(): ?int
    {
        $value = $this->getValueByKey($this->response, 'occupation_profession_id');
        return $value !== null ? (int)$value : null;
    }

    /**
     * Get resource owner occupation profession parent ID.
     *
     * @link https://service.doccheck.com/service/info/codes_v2.php?scope=profession&language=en
     *
     * @return int|null Profession parent category (DocCheck ID) or null if not set.
     */
    public function getOccupationProfessionParentId(): ?int
    {
        $value = $this->getValueByKey($this->response, 'occupation_profession_parent_id');
        return $value !== null ? (int)$value : null;
    }

    /**
     * Get resource owner unique key.
     *
     * @return string Unique key string (max 50 chars).
     *
     * @throws DocCheckIdentityProviderException If unique key is missing.
     */
    public function getId(): string
    {
        $id = $this->getValueByKey($this->response, 'uniquekey', false);
        if ($id === false) {
            throw new DocCheckIdentityProviderException(
                'Resource owner unique key is missing.',
                0,
                $this->response
            );
        }
        return $id;
    }

    /**
     * Set base URL for fetching resource owner details.
     *
     * @param string $baseUrl Base URL string.
     *
     * @return ResourceOwnerInterface Resource owner object.
     */
    public function setBaseUrl(string $baseUrl): ResourceOwnerInterface
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    /**
     * Return all of the owner details available as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->response;
    }
}
