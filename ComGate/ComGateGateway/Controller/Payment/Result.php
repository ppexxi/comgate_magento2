<?php
namespace ComGate\ComGateGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Exception\PaymentException;

/**
 * This controller handles the payment result URL
 */
class Result extends CoreClass {

  /**
   * Constructor
   *
   */
  public function __construct(\ComGate\ComGateGateway\Model\Config $config, \Magento\Framework\Message\ManagerInterface $messageManager, \Magento\Framework\App\Action\Context $context) {
    parent::__construct($config, $messageManager, $context);
  }

  /**
   * Handle the result URL redirect from ComGate gateway
   *
   * @return \Magento\Framework\Controller\ResultInterface
   */
  public function execute() {
    $id = trim((string)$this->getRequest()->getParam('id', NULL));
    $refId = trim((string)$this->getRequest()->getParam('refId', NULL));

    if (!$id || !$refId) {
      $this->messageManager->addErrorMessage(__('Invalid response in the process of payment'));
      $this->_redirect('checkout/onepage/failure', ['_secure' => TRUE]);
      return;
    }

    $service = $this->config->getComGateService();
    try {
      $status = $service->getStatus($id);
    }
    catch (\Exception $e) {
      $status = false;
    }

    if (!$status) {
      $this->messageManager->addErrorMessage(__('Malformed response in the process of payment'));
      $this->_redirect('checkout/onepage/failure', ['_secure' => TRUE]);
      return;
    }

    if ($status['code']) {
      $this->messageManager->addErrorMessage(__('An error occurred in the process of payment'));
      $this->_redirect('checkout/onepage/failure', ['_secure' => TRUE]);
      return;
    }

    $order_id = $status['refId'];
    $order = $this->getOrder($order_id);
    if (!$order->getId()) {
      $this->messageManager->addErrorMessage(__('Invalid order in the process of payment'));
      $this->_redirect('checkout/onepage/failure', ['_secure' => TRUE]);
      return;
    }

    $result = $status['status'];
    if (($result == 'CANCELLED') && ($order->getStatus() != \Magento\Sales\Model\Order::STATE_HOLDED)) {
      $order->setState(\Magento\Sales\Model\Order::STATE_HOLDED);
      $order->setStatus(\Magento\Sales\Model\Order::STATE_HOLDED);
      $order->addStatusHistoryComment('ComGate (redirect): Payment failed');
      $order->save();

      $this->messageManager->addErrorMessage(__('An failure occurred in the process of payment'));
      $this->_redirect('checkout/onepage/failure', ['_secure' => TRUE]);
      return;
    }
    else if (($result == 'PAID') && ($order->getStatus() != \Magento\Sales\Model\Order::STATE_PROCESSING)) {
      $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
      $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
      $order->addStatusHistoryComment('ComGate (redirect): Payment success');
      $order->save();

      $this->_redirect('checkout/onepage/success', ['_secure' => TRUE]);
      return;
    }
    else  if (($result == 'PENDING') && ($order->getStatus() != \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW)) {
      $order->setState(\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW);
      $order->setStatus(\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW);
      $order->addStatusHistoryComment('ComGate (redirect): Payment pending');
      $order->save();

      $this->_redirect('checkout/onepage/success', ['_secure' => TRUE]);
      return;
    }
    else  if (($result == 'AUTHORIZED') && ($order->getStatus() != \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW)) {
      $order->setState(\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW);
      $order->setStatus(\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW);
      $order->addStatusHistoryComment('ComGate (redirect): Payment authorized');
      $order->save();

      $this->_redirect('checkout/onepage/success', ['_secure' => TRUE]);
      return;
    }
    else {
      if ($result == 'CANCELLED') {
        $this->messageManager->addErrorMessage(__('An failure occurred in the process of payment'));
        $this->_redirect('checkout/onepage/failure', ['_secure' => TRUE]);
        return;
      }
      else {
        $this->_redirect('checkout/onepage/success', ['_secure' => TRUE]);
        return;
      }
      
      /*$order->addStatusHistoryComment('ComGate (redirect): Unknown state [error]');
      $order->save();

      $this->messageManager->addErrorMessage(__('An unknown result occurred in the process of payment'));
      $this->_redirect('checkout/onepage/failure', ['_secure' => TRUE]);*/
    }
  }
}
