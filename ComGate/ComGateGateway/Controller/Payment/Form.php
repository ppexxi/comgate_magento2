<?php
namespace ComGate\ComGateGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Exception\PaymentException;

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

  /*public function fixCountryCode($countryCode) {
    return convert_country_code_from_isoa2_to_isoa3($countryCode);
  }*/

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

    if ($order->getState() != \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT) {
      $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
      $order->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
      $order->addStatusHistoryComment('ComGate: Redirected to gateway');
      $order->save();
    }

    $currency = $order->getOrderCurrency()->getCurrencyCode();

    $address = $order->getBillingAddress();

    if ($address && ($address->getCustomerId() != null)) $clientId = $address->getCustomerId();
    else $clientId = 0;

    $productName = 'Ord. ' . $order->getId();

    $locale = $this->getLocale();
    $locale_string = explode('_', $locale->getLocale() , 2);

    $payment_methods = $this->config->getChannels();
    //var_dump($payment_methods); die();

    $allowed_payment_methods = array();
    foreach($payment_methods as $payment_method) {
      $allow = false;
      switch ($selection) {
        case 'card':
        if (strpos($payment_method,'CARD_') === 0) {
          $allow = true;
        }
        break;

        case 'wire': 
        if (strpos($payment_method,'BANK_') === 0) {
          $allow = true;
        }
        break;

        case 'delay':
        if (strpos($payment_method,'LATER_') === 0) {
          $allow = true;
        }
        break;
      }

      if ($allow) {
        $allowed_payment_methods[] = $payment_method;
      }
    }
    $payment_methods_string = implode('+', $allowed_payment_methods);
    //var_dump($payment_methods_string); die();
    //$currency = 'CZK';

    $service = $this->config->getComGateService();
    $result = $service->createTransaction(/*$this->fixCountryCode(*/$address ? $address->getCountryId() : 'SK' /*)*/, round($order->getGrandTotal() * 100) , $currency ? $currency : 'EUR', $productName, $order->getId() , $clientId, '', '', $payment_methods_string, '', $address ? $address->getEmail() : 'nomail@example.com', $address ? $address->getTelephone() : '', $productName, strtoupper($locale_string[0]) , false, false, false, false, false);

    $response = $this->createResponse();
    $response->setContents(json_encode($result->redirectUrl));

    return $response;
  }
}
