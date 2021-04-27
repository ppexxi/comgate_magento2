<?php
namespace ComGate\ComGateGateway\Model;

require_once __DIR__ . "/../libs/comgate/AgmoPaymentsSimpleProtocol.php";

/**
 * Configuration model for ComGate payment gateway
 */
class Config {
  /**
   * @var \Magento\Framework\App\Config\ScopeConfigInterface
   */
  private $scopeConfigInterface;

  private $service = null;

  const GATEWAY_URL = 'https://payments.comgate.cz/v1.0';

  public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $configInterface) {
    $this->scopeConfigInterface = $configInterface;
  }

  /**
   * Function used for reading a config value.
   */
  private function getConfigValue($value) {
    return $this->scopeConfigInterface->getValue('payment/comgate/' . $value);
  }

  public function isEnabled() {
    return (bool)$this->getConfigValue('active');
  }

  public function getTitle() {
    return trim((string)$this->getConfigValue('title'));
  }

  public function isProduction() {
    return (bool)$this->getConfigValue('production');
  }

  public function getComid() {
    return trim((string)$this->getConfigValue('comid'));
  }

  public function getSecret() {
    return trim((string)$this->getConfigValue('secret'));
  }

  public function getChannels() {
    $channels = (array)$this->getConfigValue('channels');
    $first = reset($channels);
    if ($first) {
      return explode(',', $first);
    }
    else {
      return array(
        'ALL'
      );
    }
  }

  /*public function getPreselected() {
    return trim((string)$this->getConfigValue('preselected'));
  }*/

  public function isDebug() {
    return (bool)$this->getConfigValue('debug');
  }

  protected function getObjectManager() {
    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    return $objectManager;
  }

  protected function getStore() {
    $objectManager = $this->getObjectManager();
    $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
    $store = $storeManager->getStore();
    return $store;
  }

  public function getGatewayUrl() {
    return self::GATEWAY_URL;
  }

  public function getFormUrl() {
    return $this->getStore()->getBaseUrl() . 'comgate/payment/form';
  }

  public function getResultUrl() {
    return $this->getStore()->getBaseUrl() . 'comgate/payment/result';
  }

  public function getIpnUrl() {
    return $this->getStore()->getBaseUrl() . 'comgate/payment/ipn';
  }

  public function getComGateService() {
    if (!$this->service) {
      $this->service = new \AgmoPaymentsSimpleProtocol(self::GATEWAY_URL, $this->getComid() , !$this->isProduction() , $this->getSecret());
    }

    return $this->service;
  }
}

