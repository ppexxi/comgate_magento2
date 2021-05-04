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
  protected $orderRepository;
  protected $session;
  protected $locale;

  /**
   * Constructor
   *
   */
  public function __construct(\ComGate\ComGateGateway\Model\Config $config, \Magento\Framework\Message\ManagerInterface $messageManager, \Magento\Framework\App\Action\Context $context, \Magento\Sales\Model\OrderRepository $orderRepository, \Magento\Checkout\Model\Session $session, \Magento\Framework\Locale\Resolver $locale) {
    parent::__construct($context);

    $this->config = $config;
    $this->messageManager = $messageManager;
    $this->orderRepository = $orderRepository;
    $this->session = $session;
    $this->locale = $locale;
  }

  protected function getOrder($order_id) {
    $order = $this->orderRepository->get($order_id);
    return $order;
  }

  protected function getSession() {
    return $this->session;
  }

  protected function getLocale() {
    return $this->locale;
  }

  protected function createResponse() {
    return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
  }
}

