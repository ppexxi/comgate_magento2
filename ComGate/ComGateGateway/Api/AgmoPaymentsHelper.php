<?php

namespace ComGate\ComGateGateway\Api;

class AgmoPaymentsHelper {

 public static function updatePaymentTransaction($payment, $paymentData = array()) {

    $payment->setLastTransId($paymentData['id']);
    $payment->setTransactionId($paymentData['id']);
    
    $payment->setAdditionalInformation([\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array)$paymentData]);
      
    $payment->setParentTransactionId(null);
    $payment->save();

    return $payment;
  }

  public static function createPaymentUrl($config, $locale, $order, $selection = 'card') {

    $currency = $order->getOrderCurrency()->getCurrencyCode();

    $address = $order->getBillingAddress();

    if ($address && ($address->getCustomerId() != null)) $clientId = $address->getCustomerId();
    else $clientId = 0;

    $productName = 'Ord. ' . $order->getId();

    $locale_string = explode('_', $locale->getLocale() , 2);

    $payment_methods = $config->getChannels();

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

    $service = $config->getComGateService();
    $result = $service->createTransaction($address ? $address->getCountryId() : 'SK', round($order->getGrandTotal() * 100) , $currency ? $currency : 'EUR', $productName, $order->getId() , $clientId, '', '', $payment_methods_string, '', $address ? $address->getEmail() : 'nomail@example.com', $address ? $address->getTelephone() : '', $productName, strtoupper($locale_string[0]) , false, false, false, false, false);

    return $result->redirectUrl;
  }
}
