<?php
class AgmoPaymentsSimpleProtocol {

  private $_paymentsUrl;
  private $_paymentsUrl2; // pro opakovane platby
  private $_merchant;
  private $_test;
  private $_secret;
  private $_transactionId = NULL;
  private $_redirectUrl = NULL;
  private $_statusParams = array();

  /**
   * @param string $paymentsUrl
   *      AGMO payments system URL
   * @param string $merchant
   *      merchants identifier
   * @param boolean $test
   *      TRUE = testing system variant
   *      FALSE = release (production) system variant
   * @param string $secret
   *      merchants password (used for background HTTP communication)
   * @param $paymentsUrl2
   */
  public function __construct($paymentsUrl, $merchant, $test, $secret = NULL, $paymentsUrl2 = NULL) {
    $this->_paymentsUrl = $paymentsUrl;
    $this->_paymentsUrl2 = $paymentsUrl2;
    $this->_merchant = $merchant;
    $this->_test = $test;
    $this->_secret = $secret;

    /*var_dump($this->_paymentsUrl);
    var_dump($this->_merchant);
    var_dump($this->_test);
    var_dump($this->_secret); die();*/

    $this->initialize();
  }

  /**
   * encodes parameters array to string (url encoded parameters glued with ampersand character)
   *
   * @param array $params
   *      parameters array (key - value pairs)
   *
   * @return string
   */
  private function _encodeParams($params) {
    $data = '';
    foreach ($params as $key => $val) {
      $data .= ($data === '' ? '' : '&') . urlencode($key) . '=' . urlencode($val);
    }
    return $data;
  }

  /**
   * decodes url encoded parameters glued with ampersand character to key - value pairs
   *
   * @param $data
   *      url encoded parameters glued with ampersand character
   *
   * @return array
   */
  private function _decodeParams($data) {
    $encodedParams = explode('&', $data);
    $params = array();
    foreach ($encodedParams as $encodedParam) {
      $encodedPair = explode('=', $encodedParam);
      $paramName = urlencode($encodedPair[0]);
      $paramValue = (count($encodedPair) == 2 ? urldecode($encodedPair[1]) : '');
      $params[$paramName] = $paramValue;
    }
    return $params;
  }

  /**
   * check if parameter with a specified name exists in the parameters array
   * - throws an exception if the parametr doesn't exist
   * - returns the parameter value
   *
   * @param array $params
   *      parameters array (key - value pairs)
   * @param string $paramName
   *      parameter name
   *
   * @return string
   * @throws Exception
   */
  private function _checkParam($params, $paramName) {
    if (!isset($params[$paramName])) {
      throw new Exception('Missing response parameter: ' . $paramName);
    }
    return $params[$paramName];
  }

  /**
   * creates a HTTP POST request and returns the response body
   *
   * @param string $url
   *      URL address
   * @param string $data
   *      request body
   *
   * @return string
   */
  private function _doHttpPost($url, $data) {
    $c = curl_init();
    curl_setopt($c, CURLOPT_URL, $url);
    curl_setopt($c, CURLOPT_POST, 1);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_HEADER, 0);
    curl_setopt($c, CURLOPT_POSTFIELDS, $data);
    $responseBody = curl_exec($c);
    curl_close($c);
    return $responseBody;
  }

  /**
   * creates new payment transaction
   *
   * @param string $country
   *      identifier of country (CZ)
   * @param float $price
   *      price in the specified currency
   * @param string $currency
   *      3-code identifier of currency (CZK)
   * @param string $label
   *      short product label (max 16 characters)
   * @param string $refId
   *      merchants payment identifier
   * @param string $payerId
   *      payer identifier
   * @param string $vatPL
   *        one of VATs PL from Agmo Payments system
   *      parameter is required only for MPAY_PL method
   * @param string $category
   *      product category identifier
   *      parameter is required only for MPAY_CZ and SMS_CZ methods
   * @param string $method
   *      method identifier or 'ALL' value
   * @param string $account
   *      Identifier of Merchant’s bank account to which AGMO transfers the money.
   *      If the parameter is empty, the default Merchant’s account will be used.
   *      List of accounts is on https://data.agmo.eu/
   * @param string $email
   *      cliens email address (optional)
   * @param string $phone
   *      clients phone number (optional)
   * @param string $productName
   *      product identifier (optional)
   * @param string $language
   *      language identifier (optional)
   * @param bool $preauth
   * @param bool $reccurring
   * @param null $reccurringId
   *
   * @param bool $eetReport
   *      overrides eet reporting settings in shop configuration
   * @param null $eetData
   *      data for eet reporting
   *
   * @throws Exception
   */
  public function createTransaction($country, $price, $currency, $label, $refId, $payerId, $vatPL = '', $category = '', $method = 'ALL', $account = '', $email = '', $phone = '', $productName = '', $language = '', $preauth = false, $reccurring = false, $reccurringId = null, $eetReport = false, $eetData = null) {

    //var_dump(func_get_args()); die();

    // initialize response variables
    $this->_transactionId = NULL;
    $this->_redirectUrl = NULL;

    $country = str_replace(array(
      'SVK'
    ) , array(
      'SK'
    ) , $country);

    // prepare request body
    $requestParams = array(
      'merchant' => $this->_merchant,
      'test' => ($this->_test ? 'true' : 'false') ,
      'country' => $country,
      'price' => $price,
      'curr' => $currency,
      'label' => $label,
      'refId' => $refId,
      'payerId' => $payerId,
      'vatPL' => $vatPL,
      'cat' => $category,
      'method' => $method,
      'account' => $account,
      'email' => $email,
      'phone' => $phone,
      'name' => $productName,
      'lang' => $language,
      'prepareOnly' => 'true',
      'secret' => $this->_secret,
      'preauth' => $preauth ? 'true' : 'false',
      'initRecurring' => $reccurring ? 'true' : 'false',
      'eetReport' => $eetReport,
      'eetData' => (!empty($eetData) && is_array($eetData)) ? json_encode($eetData) : $eetData
    );

    if ($reccurringId != null) {
      $requestParams['initRecurringId'] = $reccurringId;
    }

    $requestBody = $this->_encodeParams($requestParams);

    if ($reccurringId != null) {
      $url = $this->_paymentsUrl2 . '/recurring';
    }
    else {
      $url = $this->_paymentsUrl . '/create';
    }

    // do HTTP request
    $responseBody = $this->_doHttpPost($url, $requestBody);

    //print_r($requestParams);
    //print_r($responseBody); die();

    // process HTTP response
    $responseParams = $this->_decodeParams($responseBody);
    $responseCode = $this->_checkParam($responseParams, 'code');
    $responseMessage = $this->_checkParam($responseParams, 'message');

    try {
      $responseExtraMessage = ' - ' . $this->_checkParam($responseParams, 'extraMessage');
    }
    catch(\Exception $e) {
      $responseExtraMessage = '';
    }

    if ($responseCode !== '0' || $responseMessage !== 'OK') {
      throw new Exception('Transaction creation error ' . $responseCode . ': ' . $responseMessage . $responseExtraMessage);
    }

    $result = new stdClass();
    $this->_transactionId = $result->transactionId = $this->_checkParam($responseParams, 'transId');
    $this->_redirectUrl = $result->redirectUrl = $this->_checkParam($responseParams, 'redirect');

    return $result;
  }

  public function getPaymentMethods() {

    // prepare request body
    $requestParams = array(
      'merchant' => $this->_merchant,
      'test' => ($this->_test ? 'true' : 'false') ,
      'secret' => $this->_secret,
      'type' => 'json'
    );

    $requestBody = $this->_encodeParams($requestParams);
    $url = $this->_paymentsUrl;

    // do HTTP request
    $responseBody = $this->_doHttpPost($url . '/methods', $requestBody);

    // process HTTP response
    $out = @json_decode($responseBody);

    if (!$out || !isset($out->methods)) {
      return false;
    }

    //var_dump($out); die();
    return $out->methods;
  }

  public function getStatus($id) {

    // prepare request body
    $requestParams = array(
      'merchant' => $this->_merchant,
      'test' => ($this->_test ? 'true' : 'false') ,
      'secret' => $this->_secret,
      'transId' => $id,
      'type' => 'json'
    );

    $requestBody = $this->_encodeParams($requestParams);
    $url = $this->_paymentsUrl;

    // do HTTP request
    $responseBody = $this->_doHttpPost($url . '/status', $requestBody);
    $responseParams = $this->_decodeParams($responseBody);

    if (!$responseParams) {
      return false;
    }

    return $responseParams;
  }

  /**
   * returns an identifier of the transaction created via createTransaction method
   *
   * @return string
   */
  public function getTransactionId() {
    return $this->_transactionId;
  }

  /**
   * returns an URL address for the transaction created via createTransaction method
   *
   * @return string
   */
  public function getRedirectUrl() {
    return $this->_redirectUrl;
  }

  /**
   * gets transaction status parameters and check them in the configuration
   *
   * @param array $params
   *
   * @throws Exception
   */
  public function checkTransactionStatus($params) {

    $this->_statusParams = array();

    if (!isset($params) || !is_array($params) || !isset($params['merchant']) || $params['merchant'] === '' || !isset($params['test']) || $params['test'] === '' || !isset($params['price']) || $params['price'] === '' || !isset($params['curr']) || $params['curr'] === '' || !isset($params['refId']) || $params['refId'] === '' || !isset($params['transId']) || $params['transId'] === '' || !isset($params['secret']) || $params['secret'] === '' || !isset($params['status']) || $params['status'] === '') {
      throw new Exception('Missing parameters');
    }

    if ($params['merchant'] !== $this->_merchant || $params['test'] !== ($this->_test ? 'true' : 'false') || $params['secret'] !== $this->_secret) {
      throw new Exception('Invalid merchant identification');
    }

    $this->_statusParams = $params;

  }

  private function initialize() {
    // no code
  }

  /**
   * returns transId from the transaction status check
   *
   * @return string
   */
  public function getTransactionStatusTransId() {
    return $this->_statusParams['transId'];
  }

  /**
   * returns refId from the transaction status check
   *
   * @return string
   */
  public function getTransactionStatusRefId() {
    return $this->_statusParams['refId'];
  }

  /**
   * returns price from the transaction status check
   *
   * @return float
   */
  public function getTransactionStatusPrice() {
    return $this->_statusParams['price'];
  }

  /**
   * returns currency from the transaction status check
   *
   * @return string
   */
  public function getTransactionStatusCurrency() {
    return $this->_statusParams['curr'];
  }

  /**
   * returns status from the transaction status check
   *
   * @return string
   */
  public function getTransactionStatus() {
    return $this->_statusParams['status'];
  }

  /**
   * returns fee from the transaction status check
   *
   * @return string
   */
  public function getTransactionFee() {
    return $this->_statusParams['fee'];
  }

}

