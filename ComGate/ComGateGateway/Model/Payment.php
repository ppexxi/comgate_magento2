<?php
namespace ComGate\ComGateGateway\Model;

/**
 * Payment model for ComGate payment gateway (core of payment-method processing)
 */
class Payment extends \Magento\Payment\Model\Method\AbstractMethod {
  const METHOD_CODE = 'comgate';

  protected $_code = self::METHOD_CODE;

  protected $_isGateway = true;
  protected $_canAuthorize = true;
  protected $_canCapture = true;
  //protected $_canCapturePartial = true;
  //protected $_canRefund = true;
  //protected $_canRefundInvoicePartial = true;
  //protected $_canVoid = false;
  //protected $_canUseInternal = true;
  protected $_canUseCheckout = true;
  protected $_canFetchTransactionInfo = true;
  //protected $_isInitializeNeeded = false;
  //protected $_isOffline = false;
  protected $_supportedCurrencyCodes = array(
    'EUR'
  );

  public function __construct(\Magento\Framework\Model\Context $context, \Magento\Framework\Registry $registry, \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory, \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory, \Magento\Payment\Helper\Data $paymentData, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Payment\Model\Method\Logger $logger, \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null, \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null, array $data = [], $directory = null) {

    parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $resource, $resourceCollection, $data, $directory);
  }

  public function initialize($paymentAction, $stateObject) {
    //$payment = $this->getInfoInstance();
    //$stateObject->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
    //$stateObject->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
    $stateObject->setIsNotified(false);
  }

  public function canUseForCurrency($currencyCode) {
    if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
      return false;
    }
    return true;
  }

  public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount) {
    //$order = $payment->getOrder();
    //$billing = $order->getBillingAddress();
    return $this;
  }

  public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount) {
    //$order = $payment->getOrder();
    //$billing = $order->getBillingAddress();
    return $this;
  }

  public function cancel(\Magento\Payment\Model\InfoInterface $payment) {
    //$order = $payment->getOrder();
    return $this;
  }

  public function void(\Magento\Payment\Model\InfoInterface $payment) {
    //$order = $payment->getOrder();
    return $this;
  }

  /*public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount) {
    return $this;
    }*/

  public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null) {
    return true;
  }
}

