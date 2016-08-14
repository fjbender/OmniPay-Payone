<?php

namespace Omnipay\Payone\Message;

/**
 * Authorize, shop mode, client payment gateway (AJAX card tokens or redirect).
 */

use Omnipay\Payone\Extend\ItemInterface as ExtendItemInterface;
use Omnipay\Payone\Extend\Item as ExtendItem;
use Omnipay\Payone\AbstractShopGateway;
use Omnipay\Payone\ShopClientGateway;
use Omnipay\Common\Currency;
use Omnipay\Common\ItemBag;

class ShopClientAuthorizeRequest extends ShopServerAuthorizeRequest
{
    /**
     * The Response Type is always needed.
     */
    public function setResponseType($value)
    {
        return $this->setParameter('responseType', $value);
    }

    public function getResponseType()
    {
        return $this->getParameter('responseType');
    }

    /**
     * The data is used to generate the POST form to send the user
     * off to the PAYONE credit card form.
     */
    public function getData()
    {
        // The base data.
        $data = [
            'mid' => $this->getMerchantId(),
            'portalid' => $this->getPortalId(),
            'api_version' => AbstractShopGateway::API_VERSION,
            'mode' => $this->getTestMode()
                ? AbstractShopGateway::MODE_TEST
                : AbstractShopGateway::MODE_LIVE,
            'request' => $this->getRequestCode(),
            'responsetype' => $this->getResponseType(),
            'encoding' => $this->getEncoding(),
        ];

        // The errorurl does NOT appear in the Frontend documentation, but does
        // work and is implemented in other platform gateways.

        $data += $this->getDataUrl();

        if ($this->getSubAccountId()) {
            $data['aid'] = $this->getSubAccountId();
        }

        if ($this->getClearingType()) {
            $data['clearingtype'] = $this->getClearingType();
        }

        if ($this->getTransactionId()) {
            $data['reference'] = $this->getTransactionId();
        }

        if ($this->getAmountInteger()) {
            $data['amount'] = $this->getAmountInteger();
        }

        if ($this->getCurrency()) {
            $data['currency'] = $this->getCurrency();
        }

        if ($this->getEcommerceMode()) {
            $data['ecommercemode'] = $this->getEcommerceMode();
        }

        // Add in any cart items.
        $data += $this->getDataItems();

        if ($card = $this->getCard()) {
            $data['firstname'] = $card->getFirstName();
            $data['lastname'] = $card->getLastName(); // Mandatory
            $data['company'] = $card->getCompany();
            $data['street'] = $card->getBillingAddress1();
            $data['zip'] = $card->getBillingPostcode();
            $data['city'] = $card->getBillingCity();
            $data['country'] = $card->getBillingCountry(); // Mandatory
            $data['email'] = $card->getEmail();

            // Stuff the card data away for later use, but don't merge its
            // data into the main hidden-fields data.
            $card_data = $this->getDataCard();
            $data['card'] = $card_data;
        }

        // Create the hash for the hashable fields.
        $data['hash'] = $this->hashArray($data);

        return $data;
    }

    /**
     * Sending the data is a simple pass-through.
     */
    public function sendData($data)
    {
        return $this->createResponse($data);
    }


    /**
     * Whether using the AJAX or REDIRECT response type, the client needs the
     * POST data for adding to the form. Some of that data will be supplied in
     * hidden fields and be hash-protected. Some of that data will be user-
     * enterable, and so not included in the hash. Some non-hashed fields are
     * still mandatory, depending on the request type.
     */
    protected function createResponse($data)
    {
        // Filter out all fields but the hashable hidden fields.
        // But do add in the hash that has already been calculated.
        $data = $this->filterHashFields($data) + array('hash' => $data['hash']);

        return $this->response = new ShopClientAuthorizeResponse($this, $data);
    }
}