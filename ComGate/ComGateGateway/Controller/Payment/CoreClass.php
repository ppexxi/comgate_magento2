<?php
namespace ComGate\ComGateGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Exception\PaymentException;

/**
 * Abstract class for ComGate payment-controllers with commonly used methods
 */
abstract class CoreClass extends Action {

  protected $config;
  protected $messageManager;

  /**
   * Constructor
   *
   */
  public function __construct(\ComGate\ComGateGateway\Model\Config $config, \Magento\Framework\Message\ManagerInterface $messageManager, \Magento\Framework\App\Action\Context $context) {
    parent::__construct($context);

    $this->config = $config;
    $this->messageManager = $messageManager;
  }

  protected function getObjectManager() {
    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    return $objectManager;
  }

  protected function getOrder($order_id) {
    $objectManager = $this->getObjectManager();
    $order = $objectManager->create('Magento\Sales\Api\Data\OrderInterface');
    return $order->load($order_id);
  }

  protected function getStore() {
    $objectManager = $this->getObjectManager();
    $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
    $store = $storeManager->getStore();
    return $store;
  }

  protected function getSession() {
    $objectManager = $this->getObjectManager();
    $session = $objectManager->get('\Magento\Checkout\Model\Session');
    return $session;
  }

  protected function getLocale() {
    $objectManager = $this->getObjectManager();
    $locale = $objectManager->get('\Magento\Framework\Locale\Resolver');
    return $locale;
  }

  protected function createResponse() {
    return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
  }
}

