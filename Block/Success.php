<?php


namespace Dragonfly\GoogleReview\Block;

use Magento\Checkout\Block\Onepage\Success as CoreSuccess;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order\Config;
use Magento\Store\Model\ScopeInterface;

class Success extends CoreSuccess
{
    const XML_PATH_ACTIVE = 'google/google_review/active';
    const XML_PATH_MERCHANT_ID = 'google/google_review/merchant_id';
    const XML_PATH_ESTIMATED_DELIVERY_DATE = 'google/google_review/estimated_delivery_date';
    const ESTIMATED_DELIVERY_DATE = 3;

    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        Session     $checkoutSession,
        Config      $orderConfig,
        Context     $context,
        HttpContext $httpContext,
        array       $data = []
    )
    {
        parent::__construct($context, $checkoutSession, $orderConfig, $httpContext, $data);
        $this->scopeConfig = $context->getScopeConfig();
    }

    public function getOrderData(): ?array
    {
        if (!$this->isGoogleReviewActive()) {
            return null;
        }

        $order = $this->_checkoutSession->getLastRealOrder();
        $orderData = $order->getData();
        $orderId = $order->getIncrementId();
        $customerEmail = $orderData['customer_email'];
        $address = $order->getShippingAddress();
        $countryId = $address->getCountryId() ?? '';
        $estimatedDeliveryDate = date("Y-m-d", strtotime("+" . $this->getEstimatedDeliveryDate() . " days"));

        $array = [];
        $array['order_id'] = $orderId;
        $array['email'] = $customerEmail;
        $array['delivery_country'] = $countryId;
        $array['estimated_delivery_date'] = $estimatedDeliveryDate;

        return $array;
    }

    public function isGoogleReviewActive(): bool
    {
        return $this->scopeConfig->isSetFlag(
                self::XML_PATH_ACTIVE,
                ScopeInterface::SCOPE_STORE
            ) && $this->getMerchantId();
    }

    public function getMerchantId(): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_MERCHANT_ID,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getEstimatedDeliveryDate(): int
    {
        $settingsValue = (int)$this->scopeConfig->getValue(
            self::XML_PATH_ESTIMATED_DELIVERY_DATE,
            ScopeInterface::SCOPE_STORE
        );
        if (empty($settingsValue)) {
            return self::ESTIMATED_DELIVERY_DATE;
        }

        return $settingsValue;
    }
}
