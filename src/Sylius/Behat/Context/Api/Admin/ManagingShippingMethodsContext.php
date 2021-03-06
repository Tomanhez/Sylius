<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\Behat\Context\Api\Admin;

use ApiPlatform\Core\Api\IriConverterInterface;
use Behat\Behat\Context\Context;
use Sylius\Behat\Client\ApiClientInterface;
use Sylius\Behat\Client\ResponseCheckerInterface;
use Sylius\Component\Addressing\Model\ZoneInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Symfony\Component\VarDumper\VarDumper;
use Webmozart\Assert\Assert;

final class ManagingShippingMethodsContext implements Context
{
    /** @var ApiClientInterface */
    private $client;

    /** @var ResponseCheckerInterface */
    private $responseChecker;

    /** @var IriConverterInterface */
    private $iriConverter;

    public function __construct(
        ApiClientInterface $client,
        ResponseCheckerInterface $responseChecker,
        IriConverterInterface $iriConverter
    ) {
        $this->client = $client;
        $this->responseChecker = $responseChecker;
        $this->iriConverter = $iriConverter;
    }

    /**
     * @When I want to browse shipping methods
     */
    public function iBrowseShippingMethods(): void
    {
        $this->client->index();
    }

    /**
     * @When I delete shipping method :shippingMethod
     */
    public function iDeleteShippingMethod(ShippingMethodInterface $shippingMethod): void
    {
        $this->client->delete($shippingMethod->getCode());
    }

    /**
     * @When I want to create a new shipping method
     */
    public function iWantToCreateANewShippingMethod(): void
    {
        $this->client->buildCreateRequest();
    }

    /**
     * @When I add it
     * @When I try to add it
     */
    public function iAddIt(): void
    {
        $this->client->create();
    }

    /**
     * @When I specify its code as :code
     * @When I do not specify its code
     */
    public function iSpecifyItsCodeAs(?string $code = ''): void
    {
        $this->client->addRequestData('code', $code);
    }

    /**
     * @When I specify its position as :position
     */
    public function iSpecifyItsPositionAs(int $position): void
    {
        $this->client->addRequestData('position', $position);
    }

    /**
     * @When I name it :name in :localeCode
     * @When I rename it to :name in :localeCode
     * @When I do not name it
     * @When I remove its name from :localeCode translation
     */
    public function iNameItIn(?string $name = '', ?string $localeCode = 'en_US'): void
    {
        $this->client->updateRequestData(['translations' => [$localeCode => ['name' => $name, 'locale' => $localeCode]]]);
    }

    /**
     * @When I describe it as :description in :localeCode
     */
    public function iDescribeItAsIn(string $description, string $localeCode): void
    {
        $data = ['translations' => [$localeCode => ['locale' => $localeCode]]];
        $data['translations'][$localeCode]['description'] = $description;

        $this->client->updateRequestData($data);
    }

    /**
     * @When /^I define it for the (zone named "[^"]+")$/
     * @When I do not specify its zone
     */
    public function iDefineItForTheZone(ZoneInterface $zone = null): void
    {
        if (null !== $zone) {
            $this->client->addRequestData('zone', $this->iriConverter->getIriFromItem($zone));
        }
    }

    /**
     * @When I disable it
     */
    public function iDisableIt(): void
    {
        $this->client->addRequestData('enabled', false);
    }

    /**
     * @When I enable it
     */
    public function iEnableIt(): void
    {
        $this->client->addRequestData('enabled', true);
    }

    /**
     * @When I make it available in channel :channel
     */
    public function iMakeItAvailableInChannel(ChannelInterface $channel): void
    {
        $this->client->addRequestData('channels', [$this->iriConverter->getIriFromItem($channel)]);
    }

    /**
     * @When I choose :shippingCalculator calculator
     */
    public function iChooseCalculator(string $shippingCalculator): void
    {
        $this->client->addRequestData('calculator', $shippingCalculator);
    }

    /**
     * @When I specify its amount as :amount for :channel channel
     */
    public function iSpecifyItsAmountAsForChannel(ChannelInterface $channel, int $amount): void
    {
        $this->client->addRequestData('configuration', [$channel->getCode() => ['amount' => $amount]]);
    }

    /**
     * @When I want to modify a shipping method :shippingMethod
     * @When /^I want to modify (this shipping method)$/
     */
    public function iWantToModifyShippingMethod(ShippingMethodInterface $shippingMethod): void
    {
        $this->client->buildUpdateRequest($shippingMethod->getCode());
    }

    /**
     * @When I save my changes
     * @When I try to save my changes
     */
    public function iSaveMyChanges(): void
    {
        $this->client->update();
    }

    /**
     * @Then I should see :count shipping methods in the list
     */
    public function iShouldSeeShippingMethodsInTheList(int $count): void
    {
        Assert::count($this->responseChecker->getCollection($this->client->getLastResponse()), $count);
    }

    /**
     * @Then the shipping method :shippingMethodName should be in the registry
     * @Then the shipping method :shippingMethodName should appear in the registry
     */
    public function theShippingMethodShouldAppearInTheRegistry(string $shippingMethodName): void
    {
        Assert::true(
            $this->responseChecker->hasItemWithTranslation($this->client->index(), 'en_US', 'name', $shippingMethodName),
            sprintf('Shipping method with name %s does not exists', $shippingMethodName)
        );
    }

    /**
     * @Then I should be notified that it has been successfully deleted
     */
    public function iShouldBeNotifiedThatItHasBeenSuccessfullyDeleted(): void
    {
        Assert::true(
            $this->responseChecker->isDeletionSuccessful($this->client->getLastResponse()),
            'Shipping method could not be deleted'
        );
    }

    /**
     * @Then /^(this shipping method) should no longer exist in the registry$/
     */
    public function thisShippingMethodShouldNoLongerExistInTheRegistry(ShippingMethodInterface $shippingMethod): void {
        $shippingMethodName = $shippingMethod->getName();

        Assert::false(
            $this->responseChecker->hasItemWithTranslation($this->client->index(), 'en_US', 'name', $shippingMethodName),
            sprintf('Shipping method with name %s does not exists', $shippingMethodName)
        );
    }

    /**
     * @Then I should be notified that it has been successfully created
     */
    public function iShouldBeNotifiedThatItHasBeenSuccessfullyCreated(): void
    {
        Assert::true(
            $this->responseChecker->isCreationSuccessful($this->client->getLastResponse()),
            'Shipping method could not be created'
        );
    }

    /**
     * @Then the shipping method :shippingMethod should be available in channel :channel
     */
    public function theShippingMethodShouldBeAvailableInChannel(ShippingMethodInterface $shippingMethod, ChannelInterface $channel): void
    {
        Assert::true(
            $this->responseChecker->hasValueInCollection(
                $this->client->show($shippingMethod->getCode()),
                'channels',
                $this->iriConverter->getIriFromItem($channel)
            ),
            sprintf('Shipping method is not assigned to %s channel', $channel->getName())
        );
    }

    /**
     * @Then /^(this shipping method) name should be "([^"]+)"$/
     * @Then /^(this shipping method) should still be named "([^"]+)"$/
     */
    public function thisShippingMethodNameShouldBe(ShippingMethodInterface $shippingMethod, string $name): void
    {
        Assert::true(
            $this->responseChecker->hasTranslation(
                $this->client->show($shippingMethod->getCode()),
                'en_US',
                'name',
                $name
            ),
            'Shipping method name has not been changed'
        );
    }

    /**
     * @Then /^(this shipping method) should be disabled$/
     */
    public function thisShippingMethodShouldBeDisabled(ShippingMethodInterface $shippingMethod): void
    {
        Assert::true(
            $this->responseChecker->hasValue(
                $this->client->show($shippingMethod->getCode()),
                'enabled',
                false
            ),
            'Shipping method name is not disabled'
        );
    }

    /**
     * @Then /^(this shipping method) should be enabled$/
     */
    public function thisShippingMethodShouldBeEnabled(ShippingMethodInterface $shippingMethod): void
    {
        Assert::true(
            $this->responseChecker->hasValue(
                $this->client->show($shippingMethod->getCode()),
                'enabled',
                true
            ),
            'Shipping method name is not disabled'
        );
    }

    /**
     * @Then I should not be able to edit its code
     */
    public function iShouldNotBeAbleToEditItsCode(): void
    {
        $this->client->addRequestData('code', 'NEW_CODE');

        Assert::false(
            $this->responseChecker->hasValue($this->client->update(), 'code', 'NEW_CODE'),
            'The code field with value NEW_CODE exist'
        );
    }

    /**
     * @Then I should be notified that it has been successfully edited
     */
    public function iShouldBeNotifiedThatItHasBeenSuccessfullyEdited(): void
    {
        Assert::true(
            $this->responseChecker->isUpdateSuccessful($this->client->getLastResponse()),
            'Shipping method could not be edited'
        );
    }

    /**
     * @Then I should be notified that shipping method with this code already exists
     */
    public function iShouldBeNotifiedThatShippingMethodWithThisCodeAlreadyExists(): void
    {
        $response = $this->client->getLastResponse();
        Assert::false(
            $this->responseChecker->isCreationSuccessful($response),
            'Shipping method  has been created successfully, but it should not'
        );
        Assert::same(
            $this->responseChecker->getError($response),
            'code: The shipping method with given code already exists.'
        );
    }

    /**
     * @Then there should still be only one shipping method with code :value
     */
    public function thereShouldStillBeOnlyOneShippingMethodWith(string $value): void
    {
        $response = $this->client->index();
        $itemsCount = $this->responseChecker->countCollectionItems($response);

        Assert::same($itemsCount, 1, sprintf('Expected 1 shipping method, but got %d', $itemsCount));
        Assert::true($this->responseChecker->hasItemWithValue($response, 'code', $value));
    }

    /**
     * @Then I should be notified that :element is required
     */
    public function iShouldBeNotifiedThatElementIsRequired(string $element): void
    {
        Assert::contains(
            $this->responseChecker->getError($this->client->getLastResponse()),
            sprintf('%s: Please enter shipping method %s.', $element, $element)
        );
    }

    /**
     * @Then I should be notified that zone has to be selected
     */
    public function iShouldBeNotifiedThatZoneHasToBeSelected(): void
    {
        Assert::contains(
            $this->responseChecker->getError($this->client->getLastResponse()),
            'zone: Please select shipping method zone.'
        );
    }

    /**
     * @Then shipping method with :element :value should not be added
     */
    public function theShippingMethodWithElementValueShouldNotBeAdded(string $element, string $value): void
    {
        Assert::false(
            $this->responseChecker->hasItemWithValue($this->client->index(), $element, $value),
            sprintf('Shipping method should not have %s "%s", but it does,', $element, $value)
        );
    }
}
