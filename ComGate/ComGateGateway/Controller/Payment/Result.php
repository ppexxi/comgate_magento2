<?php
namespace ComGate\ComGateGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Exception\PaymentException;

/**
 * This controller handles the payment result URL (paid, cancelled, authorized, pending)
 */
class Result extends CoreClass {

  /**
   * Constructor
   *
   */
  public function __construct(\ComGate\ComGateGateway\Model\Config $config, \Magento\Framework\Message\ManagerInterface $messageManager, \Magento\Framework\App\Action\Context $context, \Magento\Sales\Model\OrderRepository $orderRepository, \Magento\Checkout\Model\Session $session, \Magento\Framework\Locale\Resolver $locale) {
    parent::__construct($config, $messageManager, $context, $orderRepository, $session, $locale);
  }

  /**
   * Handle the result URL redirect from ComGate gateway
   *
   * @return \Magento\Framework\Controller\ResultInterface
   */
  public function execute() {
    $id = trim((string)$this->getRequest()->getParam('id', NULL));
    $refId = (int)trim((string)$this->getRequest()->getParam('refId', NULL));

    if (!$id || !$refId) {
      $this->messageManager->addErrorMessage(__('Invalid response in the process of payment'));
      $this->_redirect('checkout/onepage/failure', ['_secure' => true]);
      return;
    }

    $service = $this->config->getComGateService();
    try {
      $status = $service->getStatus($id);
    }
    catch(\Exception $e) {
      $status = false;
    }

    if (!$status) {
      $this->messageManager->addErrorMessage(__('Malformed response in the process of payment'));
      $this->_redirect('checkout/onepage/failure', ['_secure' => true]);
      return;
    }

    if (!empty($status['code'])) {
      $this->messageManager->addErrorMessage(__('An error occurred in the process of payment'));
      $this->_redirect('checkout/onepage/failure', ['_secure' => true]);
      return;
    }

    $order_id = (int)@$status['refId'];
    $order = $this->getOrder($order_id);
    if (!$order->getId()) {
      $this->messageManager->addErrorMessage(__('Invalid order in the process of payment'));
      $this->_redirect('checkout/onepage/failure', ['_secure' => true]);
      return;
    }

    if (($status['price'] != round($order->getGrandTotal() * 100)) || ($status['curr'] != $order->getOrderCurrency()->getCurrencyCode())) {
      $this->messageManager->addErrorMessage(__('Payment sum or currency mismatch'));
      $this->_redirect('checkout/onepage/failure', ['_secure' => true]);
      return;
    }

    $invoice = $order->getInvoiceCollection()->getFirstItem();
    $payment = $order->getPayment();

    $result = $status['status'];
    if (($result == 'CANCELLED') && ($order->getStatus() != \Magento\Sales\Model\Order::STATE_HOLDED)) {

      $payment->getMethodInstance()->cancel($payment);

      $order->addStatusHistoryComment('ComGate (redirect): Payment failed');
      $order->save();

      $this->messageManager->addErrorMessage(__('An failure occurred in the process of payment'));
      $this->_redirect('checkout/onepage/failure', ['_secure' => true]);
      return;
    }
    else if (($result == 'PAID') && ($order->getStatus() != \Magento\Sales\Model\Order::STATE_PROCESSING)) {

      \ComGate\ComGateGateway\Api\AgmoPaymentsHelper::updatePaymentTransaction($payment, array(
        'id' => $id,
        'state' => $result
      ));

      $payment->capture();

      $order->addStatusHistoryComment('ComGate (redirect): Payment success');
      $order->save();

      $this->_redirect('checkout/onepage/success', ['_secure' => true]);
      return;
    }
    else if (($result == 'PENDING') && ($order->getStatus() != \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW)) {

      \ComGate\ComGateGateway\Api\AgmoPaymentsHelper::updatePaymentTransaction($payment, array(
        'id' => $id,
        'state' => $result
      ));

      $payment->getMethodInstance()->authorize($payment, $order->getGrandTotal());

      $order->addStatusHistoryComment('ComGate (redirect): Payment pending');
      $order->save();

      $this->_redirect('checkout/onepage/success', ['_secure' => true]);
      return;
    }
    else if (($result == 'AUTHORIZED') && ($order->getStatus() != \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW)) {

      \ComGate\ComGateGateway\Api\AgmoPaymentsHelper::updatePaymentTransaction($payment, array(
        'id' => $id,
        'state' => $result
      ));

      $payment->getMethodInstance()->authorize($payment, $order->getGrandTotal());

      $order->addStatusHistoryComment('ComGate (redirect): Payment authorized');
      $order->save();

      $this->_redirect('checkout/onepage/success', ['_secure' => true]);
      return;
    }
    else {
      if ($result == 'CANCELLED') {
        $this->messageManager->addErrorMessage(__('An failure occurred in the process of payment'));

        $payment->getMethodInstance()->cancel($payment);

        $this->_redirect('checkout/onepage/failure', ['_secure' => true]);
        return;
      }
      else {
        \ComGate\ComGateGateway\Api\AgmoPaymentsHelper::updatePaymentTransaction($payment, array(
            'id' => $id,
            'state' => $result
        ));

        $payment->capture();

        $this->_redirect('checkout/onepage/success', ['_secure' => true]);
        return;
      }
    }
  }
}
