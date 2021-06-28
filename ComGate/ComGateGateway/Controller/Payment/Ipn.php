<?php
namespace ComGate\ComGateGateway\Controller\Payment;

/**
 * This controller handles the server to server notification (IPN)
 *
 */
class Ipn extends Result implements \Magento\Framework\App\Action\HttpPostActionInterface, \Magento\Framework\App\CsrfAwareActionInterface {

  /**
   * Constructor
   *
   */
  public function __construct(\ComGate\ComGateGateway\Model\Config $config, \Magento\Framework\Message\ManagerInterface $messageManager, \Magento\Framework\App\Action\Context $context, \Magento\Sales\Model\OrderRepository $orderRepository, \Magento\Checkout\Model\Session $session, \Magento\Framework\Locale\Resolver $locale) {

    parent::__construct($config, $messageManager, $context, $orderRepository, $session, $locale);
  }

  public function createCsrfValidationException(\Magento\Framework\App\RequestInterface $request): ? \Magento\Framework\App\Request\InvalidRequestException {
    return null;
  }

  public function validateForCsrf(\Magento\Framework\App\RequestInterface $request): ? bool {
    return true;
  }

  /**
   * Function that processes the IPN (Instant Payment Notification) message of the server.
   *
   * @return \Magento\Framework\Controller\ResultInterface
   */
  public function execute() {

    $id = trim((string)$this->getRequest()->getParam('transId', NULL));
    $refId = (int)trim((string)$this->getRequest()->getParam('refId', NULL));

    if (!$id || !$refId) {
      http_response_code(400);
      die('Invalid response');
    }

    $service = $this->config->getComGateService();
    try {
      $status = $service->getStatus($id);
    }
    catch(\Exception $e) {
      $status = false;
    }

    if (!$status) {
      http_response_code(400);
      die('Malformed response');
    }

    $order_id = (int)@$status['refId'];
    $order = $this->getOrder($order_id);
    if (!$order->getId()) {
      http_response_code(400);

      //trigger_error('No Order [' . $order_id . ']');
      die('No Order');
    }

    $response = $this->createResponse();

    if (!in_array($order->getStatus() , array(
      \Magento\Sales\Model\Order::STATE_NEW,
      \Magento\Sales\Model\Order::STATE_HOLDED,
      \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT,
      \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW
    ))) {

      $response->setHttpResponseCode(200);
      return $response;
    }

    if (!empty($status['code'])) {
      http_response_code(400);
      die('Payment error');
    }

    if (($status['price'] != round($order->getGrandTotal() * 100)) || ($status['curr'] != $order->getOrderCurrency()->getCurrencyCode())) {
      http_response_code(400);
      die('Payment sum or currency mismatch');
    }

    $invoice = $order->getInvoiceCollection()->getFirstItem();
    $payment = $order->getPayment();

    if (($status['status'] == 'CANCELLED') && ($order->getStatus() != \Magento\Sales\Model\Order::STATE_HOLDED)) {

      $payment->getMethodInstance()->cancel($payment);

      $order->addStatusHistoryComment('ComGate (notification): Payment failed');
      $order->save();
    }
    else if (($status['status'] == 'PAID') && ($order->getStatus() != \Magento\Sales\Model\Order::STATE_PROCESSING)) {
      
       \ComGate\ComGateGateway\Api\AgmoPaymentsHelper::updatePaymentTransaction($payment, array(
        'id' => $id,
        'state' => $status['status']
      ));

      $payment->capture();

      $order->addStatusHistoryComment('ComGate (notification): Payment success');
      $order->save();
    }
    else if (($status['status'] == 'AUTHORIZED') && ($order->getStatus() != \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW)) {

       \ComGate\ComGateGateway\Api\AgmoPaymentsHelper::updatePaymentTransaction($payment, array(
        'id' => $id,
        'state' => $status['status']
      ));

      $payment->getMethodInstance()->authorize($payment, $order->getGrandTotal());

      $order->addStatusHistoryComment('ComGate (notification): Payment authorized');
      $order->save();
    }

    $response->setHttpResponseCode(200);
    return $response;
  }
}
