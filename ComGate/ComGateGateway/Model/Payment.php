<?php
namespace ComGate\ComGateGateway\Model;

/**
 * Payment model for ComGate payment gateway (core of payment-method processing)
 */
class Payment extends \Magento\Payment\Model\Method\AbstractMethod {
  const METHOD_CODE = 'comgate';

  protected $_code = self::METHOD_CODE;

  protected $_isGateway = true;
  protected $_isOffline = false;
  protected $_isInitializeNeeded = true;

  protected $_canOrder = true;
  protected $_canAuthorize = true;
  protected $_canCapture = true;

  protected $_canRefund = true;
  //protected $_canRefundInvoicePartial = true;
  
  //protected $_canVoid = true;
  
  protected $_canUseCheckout = true;
  protected $_canFetchTransactionInfo = true;

  protected $_supportedCurrencyCodes = array(
    'EUR',
    'CZK',
    'PLN',
    'HUF',
    'RON',
    'HRK',
    'USD',
    'GPB',
    'NOK',
    'SEC'
  );

  protected $config;

  public function __construct(\ComGate\ComGateGateway\Model\Config $config, \Magento\Framework\Model\Context $context, \Magento\Framework\Registry $registry, \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory, \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory, \Magento\Payment\Helper\Data $paymentData, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Payment\Model\Method\Logger $logger, \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null, \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null, array $data = [], $directory = null) {

    parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $resource, $resourceCollection, $data, $directory);

    $this->config = $config;
  }

  public function initialize($paymentAction, $stateObject) {
    if ($paymentAction == 'order') {
      $stateObject->setIsNotified(false);
      $stateObject->setStatus(Order::STATE_PENDING_PAYMENT);
      $stateObject->setState(Order::STATE_PENDING_PAYMENT);
    }
  }

  public function canUseForCurrency($currencyCode) {
    return in_array($currencyCode, $this->_supportedCurrencyCodes);
  }

  public function getConfigPaymentAction() {
    return self::ACTION_ORDER;
  }

  public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount) {
    if (!$this->canAuthorize()) {
      throw new \Magento\Framework\Exception\LocalizedException(__('The authorize action is not available.'));
    }

    $order = $payment->getOrder();
    $payment->setIsTransactionClosed(0);

    if ($order->getState() != \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW) {
      $order->setState(\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW);
      $order->setStatus(\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW);
      $order->save();
    }

    return $this;
  }

  public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount) {
    if (!$this->canCapture()) {
      throw new \Magento\Framework\Exception\LocalizedException(__('The capture action is not available.'));
    }

    $order = $payment->getOrder();
    $payment->setIsTransactionClosed(1);

    if ($order->getState() != \Magento\Sales\Model\Order::STATE_PROCESSING) {
      $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
      $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
      $order->save();
    }

    return $this;
  }

  /*public function void(\Magento\Payment\Model\InfoInterface $payment) {
    return $this;
  }*/
  
  public function cancel(\Magento\Payment\Model\InfoInterface $payment) {

    $order = $payment->getOrder();
    $payment->setIsTransactionClosed(1);

    if ($order->getState() != \Magento\Sales\Model\Order::STATE_HOLDED) { 
      $order->setState(\Magento\Sales\Model\Order::STATE_HOLDED);
      $order->setStatus(\Magento\Sales\Model\Order::STATE_HOLDED);
      $order->save();
    }

    return $this;
  }

  public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount) {
    if (!$this->canRefund()) {
      throw new \Magento\Framework\Exception\LocalizedException(__('The refund action is not available.'));
    }
    
    $order = $payment->getOrder();

    $service = $this->config->getComGateService();
    $service->refund($payment->getLastTransId(), $amount, $order->getOrderCurrency()->getCurrencyCode(), $order->getId());

    return $this;
  }

  public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null) {
    return true;
  }
}
