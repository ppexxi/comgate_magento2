<?php
namespace ComGate\ComGateGateway\Model\Source;

/**
 * Payment channels data-source model for ComGate payment gateway
 */
class Channels implements \Magento\Framework\Option\ArrayInterface {

  /**
   * @var Config
   */
  private $config;

  private $options = NULL;

  public function __construct(\ComGate\ComGateGateway\Model\Config $config) {
    $this->config = $config;
  }

  public function toOptionArray() {
    if (!isset($this->options)) {
      $service = $this->config->getComGateService();

      try {
        $payment_method_list = $service->getPaymentMethods();
      }
      catch(\Exception $e) {
        //throw $e;
        return [];
      }

      if (!$payment_method_list) {
        return [];
      }

      $this->options = array();

      foreach ($payment_method_list as & $payment_method) {
        $option = array();
        $option['value'] = $payment_method->id;
        $option['label'] = $payment_method->name; //$payment_method->description; $payment_method->logo;
        $this->options[] = $option;
      }
    }

    return $this->options;
  }

  public function toArray() {
    $options = $this->toOptionArray();

    $array = array();
    foreach ($options as $option) {
      $array[$option['value']] = $option['label'];
    }

    return $array;
  }
}

