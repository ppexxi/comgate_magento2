<?php
namespace ComGate\ComGateGateway\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Configuration provider model for ComGate payment gateway (data sent to frontend)
 */
class ConfigProvider implements ConfigProviderInterface {
  /**
   * @var Config
   */
  private $config;

  public function __construct(Config $config) {
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig() {
    $outConfig = ['payment' => [\ComGate\ComGateGateway\Model\Payment::METHOD_CODE => [
    //'gateway_url' => $this->config->getGatewayUrl(),
    'result_url' => $this->config->getResultUrl() , 'form_url' => $this->config->getFormUrl() ,
    //'ipn_url' => $this->config->getIpnUrl(),
    'enabled' => $this->config->isEnabled() , 'title' => $this->config->getTitle() , 'production' => $this->config->isProduction() ,
    //'preselected' => $this->config->getPreselected(),
    'channels' => $this->config->getChannels() , 'debug' => $this->config->isDebug() ]]];

    return $outConfig;
  }
}

