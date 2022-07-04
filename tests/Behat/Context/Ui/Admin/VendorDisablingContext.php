<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace Tests\BitBag\SyliusMultiVendorMarketplacePlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Behat\Mink\Element\DocumentElement;
use Behat\MinkExtension\Context\RawMinkContext;
use BitBag\SyliusMultiVendorMarketplacePlugin\Entity\Vendor;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Webmozart\Assert\Assert;

final class VendorDisablingContext extends RawMinkContext implements Context
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * @Given There is a :ifEnabled vendor
     */
    public function thereIsAVendor($ifEnabled)
    {
        $flag = 'enabled' == $ifEnabled ? true : false;
        $vendor = new Vendor();
        $vendor->setCompanyName('vendor');
        $vendor->setTaxIdentifier('vendorTax');
        $vendor->setPhoneNumber('vendorPhone');
        $vendor->setEnabled($flag);
        $this->entityManager->persist($vendor);
        $this->entityManager->flush();
    }

    /**
     * @When I click :buttonText
     */
    public function iClick($buttonText)
    {
        $this->getPage()->pressButton($buttonText);
    }

    /**
     * @When I choose :element
     */
    public function iChoose($element)
    {
        $page = $this->getSession()->getPage();
        $findName = $page->find('css', $element);
        if (!$findName) {
            throw new Exception($element . ' could not be found');
        }
        $findName->click();
    }

    /**
     * @Then I should not see :ifEnabled button
     */
    public function iShouldNotSeeButton($ifEnabled)
    {
        $element = '#' . strtolower($ifEnabled);
        $page = $this->getSession()->getPage();
        $findName = $page->find('css', $element);
        Assert::null($findName);
    }

    /**
     * @return DocumentElement
     */
    private function getPage()
    {
        return $this->getSession()->getPage();
    }
}
