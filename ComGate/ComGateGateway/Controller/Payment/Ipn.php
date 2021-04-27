<?php
namespace ComGate\ComGateGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Exception\PaymentException;

/**
 * This controller handles the server to server notification
 *
 */
class Ipn extends Result implements \Magento\Framework\App\Action\HttpPostActionInterface, \Magento\Framework\App\CsrfAwareActionInterface {

  /**
   * Constructor
   *
   */
  public function __construct(\ComGate\ComGateGateway\Model\Config $config, \Magento\Framework\Message\ManagerInterface $messageManager, \Magento\Framework\App\Action\Context $context) {

    parent::__construct($config, $messageManager, $context);
  }

  public function createCsrfValidationException(\Magento\Framework\App\RequestInterface $request): ? \Magento\Framework\App\Request\InvalidRequestException {
    return null;
  }

  public function validateForCsrf(\Magento\Framework\App\RequestInterface $request): ?bool {
    return true;
  }

  /**
   * Function that processes the IPN (Instant Payment Notification) message of the server.
   *
   * @return \Magento\Framework\Controller\ResultInterface
   */
  public function execute() {

    $id = trim((string)$this->getRequest()->getParam('transId', NULL));
    $refId = trim((string)$this->getRequest()->getParam('refId', NULL));

    if (!$id || !$refId) {
      http_response_code(400);
      die('Invalid response');
    }

    $service = $this->config->getComGateService();
    try {
      $status = $service->getStatus($id);
    }
    catch (\Exception $e) {
      $status = false;
    }

    if (!$status) {
      http_response_code(400);
      die('Malformed response');
    }

    $order_id = $status['refId'];
    $order = $this->getOrder($order_id);
    if (!$order->getId()) {
      http_response_code(400);

      //trigger_error('No Order [' . $order_id . ']');
      die('No Order');
    }

    //var_dump($order_id); die();
    //var_dump($order->getStatus()); die();

    $response = $this->createResponse();

    if (!in_array($order->getStatus(), array(
      \Magento\Sales\Model\Order::STATE_NEW,
      \Magento\Sales\Model\Order::STATE_HOLDED,
      \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT,
      \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW
    ))) {

      //trigger_error('ComGate (notification): No change [' . $order_id . ']');

      $response->setHttpResponseCode(200);
      return $response;
    }

    if ($status['code']) {
      http_response_code(400);
      die('Payment error');
    }

    if (($status['status'] == 'CANCELLED') && ($order->getStatus() != \Magento\Sales\Model\Order::STATE_HOLDED)) {
      $order->setState(\Magento\Sales\Model\Order::STATE_HOLDED);
      $order->setStatus(\Magento\Sales\Model\Order::STATE_HOLDED);
        
      //trigger_error('ComGate (notification): Payment failed [' . $order_id . ']');
      $order->addStatusHistoryComment('ComGate (notification): Payment failed');
      $order->save();
    }
    else if (($status['status'] == 'PAID') && ($order->getStatus() != \Magento\Sales\Model\Order::STATE_PROCESSING)) {
      $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
      $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
        
      //trigger_error('ComGate (notification): Payment success [' . $order_id . ']');
      $order->addStatusHistoryComment('ComGate (notification): Payment success');
      $order->save();
    }
    else if (($status['status'] == 'AUTHORIZED') && ($order->getStatus() != \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW)) {
      $order->setState(\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW);
      $order->setStatus(\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW);

      //trigger_error('ComGate (notification): Payment pending [' . $order_id . ']');
      $order->addStatusHistoryComment('ComGate (notification): Payment pending');
      $order->save();
    }
    else {
      /*$order->addStatusHistoryComment('ComGate (notification): Unknown state [error]');
      $order->save();

      http_response_code(400);
      die('Invalid transaction state');*/
    }

    $response->setHttpResponseCode(200);
    return $response;
  }
}
