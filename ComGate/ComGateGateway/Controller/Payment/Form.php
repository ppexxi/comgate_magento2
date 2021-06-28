<?php
namespace ComGate\ComGateGateway\Controller\Payment;

/**
 * This controller handles payment-transaction creation & redirection URL
 */
class Form extends CoreClass {

  /**
   * Constructor
   *
   */
  public function __construct(\ComGate\ComGateGateway\Model\Config $config, \Magento\Framework\Message\ManagerInterface $messageManager, \Magento\Framework\App\Action\Context $context, \Magento\Sales\Model\OrderRepository $orderRepository, \Magento\Checkout\Model\Session $session, \Magento\Framework\Locale\Resolver $locale) {
    parent::__construct($config, $messageManager, $context, $orderRepository, $session, $locale);
  }

  /**
   * @return \Magento\Framework\Controller\ResultInterface
   */
  public function execute() {

    $order = $this->getSession()->getLastRealOrder();
    /*if (!$order || !$order->getId()) {
      // NOTE: for debug purposes
      $order_id = (int)trim((string)$this->getRequest()->getParam('order_id', NULL));
      $order = $this->getOrder($order_id);
    }*/

    if (!$order) {
      http_response_code(400);
      die('No order');
    }

    $order_id = $order->getId();
    if (!$order_id) {
      http_response_code(400);
      die('No order ID');
    }

    $selection = trim((string)$this->getRequest()->getParam('selection', NULL));
    if (!$selection) {
      $selection = 'card';
    }
    
    $order->addStatusHistoryComment('ComGate: Passing to gateway');
    $order->save();

    $url = \ComGate\ComGateGateway\Api\AgmoPaymentsHelper::createPaymentUrl($this->config, $this->getLocale(), $order, $selection);

    $response = $this->createResponse();
    $response->setContents(json_encode($url));

    return $response;
  }
}
