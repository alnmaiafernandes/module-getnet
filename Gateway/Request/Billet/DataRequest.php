<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to https://www.fcamara.com.br/ for more information.
 *
 * @category  FCamara
 * @package   FCamara_Getnet
 * @copyright Copyright (c) 2020 Getnet
 * @Agency    FCamara Formação e Consultoria, Inc. (http://www.fcamara.com.br)
 * @author    Jonatan Santos <jonatan.santos@fcamara.com.br>
 */

namespace FCamara\Getnet\Gateway\Request\Billet;

use FCamara\Getnet\Model\ConfigInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class DataRequest implements BuilderInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @param ConfigInterface $config
     * @param TimezoneInterface $timezone
     * @param Session $checkoutSession
     */
    public function __construct(
        ConfigInterface $config,
        TimezoneInterface $timezone,
        Session $checkoutSession
    ) {
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->timezone = $timezone;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Zend_Date_Exception
     */
    public function build(array $buildSubject)
    {
        if (
            !isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $buildSubject['payment'];
        $order = $paymentDO->getOrder();

        $address = $this->checkoutSession->getQuote()->getBillingAddress();
        $customer = $this->checkoutSession->getQuote()->getCustomer();
        $streetData = $address->getStreet();
        $district = $complement = $number = $street = 'NAO INFORMADO';

        if (isset($streetData[0])) {
            $street = $streetData[0];
        }

        if (isset($streetData[1])) {
            $number = $streetData[1];
        }

        if (isset($streetData[2])) {
            $complement = $streetData[2];
        }

        if (isset($streetData[3])) {
            $district = $streetData[3];
        }

        $time = $this->timezone->date()->getTimestamp();
        $date = new \Zend_Date($time, \Zend_Date::TIMESTAMP);

        if (null != $this->config->expirationDays()) {
            $date->addDay($this->config->expirationDays());
        } else {
            $date->addDay(1);
        }

        $expirationDate = $date->get('dd/MM/YYYY');

        $postcode = str_replace('-', '', $address->getPostcode());

        $response = [
            'seller_id' => $this->config->sellerId(),
            'amount' => (int) $order->getGrandTotalAmount() * 100,
            'currency' => 'BRL',
            'order' => [
                'order_id' => $order->getOrderIncrementId(),
                'sales_tax' => 0,
                'product_type' => 'service',
            ],
            'boleto' => [
                'our_number' => $this->config->ourNumber(),
                'expiration_date' => $expirationDate,
                'instructions' => $this->config->instructions(),
                'provider' => $this->config->billetProvider(),
            ],
            'customer' => [
                'first_name' => $customer->getFirstname(),
                'name' => $customer->getFirstname() . ' ' . $customer->getLastname(),
                'document_type' => 'CPF',
                'document_number' => $customer->getTaxvat(),
                'billing_address' => [
                    'street' => $street,
                    'number' => $number,
                    'complement' => $complement,
                    'district' => $district,
                    'city' => $address->getCity(),
                    'state' => $address->getRegionCode(),
                    'postal_code' => $postcode,
                ],
            ],
        ];

        return $response;
    }
}
