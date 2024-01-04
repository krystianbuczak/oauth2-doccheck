<?php

namespace krystianbuczak\OAuth2\Client\Test\Provider;

use krystianbuczak\OAuth2\Client\Provider\DocCheckResourceOwner;
use krystianbuczak\OAuth2\Client\Provider\Exception\DocCheckIdentityProviderException;
use PHPUnit\Framework\TestCase;

class DocCheckResourceOwnerTest extends TestCase
{
    /**
     * @throws DocCheckIdentityProviderException
     */
    public function testOwnerFullDataWithHtmlEntities(): void
    {
        $mockedResponse = [
            'valid' => '1',
            'uniquekey' => '730996',
            'email' => 'mock.name@example.com',
            'address_name_first' => 'J&uuml;rgen',
            'address_name_last' => 'van Kirk-L&auml;ngley',
            'address_name_title' => 'Prof',
            'address_city' => 'K&ouml;ln',
            'address_street' => 'Vogelsanger Stra&szlig;e 66',
            'address_postal_code' => '09M456',
            'address_country_id' => '18',
            'address_country_iso' => 'DE',
            'address_gender' => 'm',
            'occupation_discipline_id' => '12',
            'occupation_profession_id' => '15',
            'occupation_profession_parent_id' => '37',
        ];
        $owner = new DocCheckResourceOwner($mockedResponse);

        $this->assertEquals('730996', $owner->getId());
        $this->assertEquals('mock.name@example.com', $owner->getEmail());
        $this->assertEquals('Jürgen', $owner->getFirstName());
        $this->assertEquals('van Kirk-Längley', $owner->getLastName());
        $this->assertEquals('Prof', $owner->getTitle());
        $this->assertEquals('Köln', $owner->getCity());
        $this->assertEquals('Vogelsanger Straße 66', $owner->getStreet());
        $this->assertEquals('09M456', $owner->getPostalCode());
        $this->assertEquals(18, $owner->getCountryId());
        $this->assertIsInt($owner->getCountryId());
        $this->assertEquals('DE', $owner->getCountryIso());
        $this->assertEquals('m', $owner->getGender());
        $this->assertEquals(12, $owner->getOccupationDisciplineId());
        $this->assertIsInt($owner->getOccupationDisciplineId());
        $this->assertEquals(15, $owner->getOccupationProfessionId());
        $this->assertIsInt($owner->getOccupationProfessionId());
        $this->assertEquals(37, $owner->getOccupationProfessionParentId());
        $this->assertIsInt($owner->getOccupationProfessionParentId());
    }

    /**
     * @throws DocCheckIdentityProviderException
     */
    public function testValidOwnerHaveNoData(): void
    {
        $mockedResponse = [
            'valid' => '1',
            'uniquekey' => '730996',
        ];
        $owner = new DocCheckResourceOwner($mockedResponse);
        $this->assertEquals('730996', $owner->getId());
        $this->assertNull($owner->getEmail());
        $this->assertNull($owner->getFirstName());
        $this->assertNull($owner->getLastName());
        $this->assertNull($owner->getTitle());
        $this->assertNull($owner->getCity());
        $this->assertNull($owner->getStreet());
        $this->assertNull($owner->getPostalCode());
        $this->assertNull($owner->getCountryId());
        $this->assertNull($owner->getCountryId());
        $this->assertNull($owner->getCountryIso());
        $this->assertEquals('u', $owner->getGender());
        $this->assertNull($owner->getOccupationDisciplineId());
        $this->assertNull($owner->getOccupationProfessionId());
        $this->assertNull($owner->getOccupationProfessionParentId());
    }

    /**
     * @throws DocCheckIdentityProviderException
     */
    public function testGenderUnexpectedLabel(): void
    {
        $owner = new DocCheckResourceOwner(['address_gender' => 'x']);
        $this->expectExceptionMessage('Unexpected gender label. Allowed values are: m/f/c/o/u.');
        $owner->getGender(true);
    }

    /**
     * @throws DocCheckIdentityProviderException
     */
    public function testNoUniqueid(): void
    {
        $owner = new DocCheckResourceOwner(['uniqueid' => '']);
        $this->expectExceptionMessage('Resource owner unique key is missing.');
        $owner->getId();
    }
}
