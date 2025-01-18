<?php

namespace Twelver313\WhatsAppCloudApi;

class Client {
  const HEADER_COMPONENT = 'header';
  const BODY_COMPONENT = 'body';
  const FOOTER_COMPONENT = 'footer';

  const DATA_TYPE_TEXT = 'text';
  const DATA_TYPE_IMAGE = 'image';
  const DATA_TYPE_LOCATION = 'location';
  const DATA_TYPE_DATETIME = 'date_time';
  const DATA_TYPE_CURRENCY = 'currency';

  const MESSAGE_TYPE_TEMPLATE = 'template';
  const MESSAGE_TYPE_TEXT = 'text';
  const MESSAGE_TYPE_REACTION = 'reaction';
  const MESSAGE_TYPE_IMAGE = 'image';
  const MESSAGE_TYPE_LOCATION = 'location';
  const MESSAGE_TYPE_CONTACTS = 'contacts';
  const MESSAGE_TYPE_INTERACTIVE = 'interactive';

  private $accessToken;
  private $id;
  private $simulateSending;
  private $endpoint;
  private $requestOptions;
  private $data;

  private $templateHeaderParams;
  private $templateBodyParams;
  private $templateFooterParams;

  public function __construct($options) {
    $this->accessToken = $options['accessToken'];
    $this->id = $options['phoneNumberId'];
    $this->endpoint = "https://graph.facebook.com/v17.0/{$this->id}/messages";
    $this->simulateSending = $options['simulateSending'];

    $this->requestOptions = [
      CURLOPT_POST => true,
      CURLOPT_URL => $this->endpoint,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$this->accessToken}",
        'Content-Type: application/json'
      ],
    ];
  }

  private function verifyNotEmptyData() {
    if (empty($this->data)) {
      throw new WhatsAppCloudApiException([
        'code' => WhatsAppCloudApiException::INTERNAL_ERROR_CODE,
        'error_subcode' => WhatsAppCloudApiException::MESSAGE_NOT_INITIALIZED_SUB_CODE,
        'message' => WhatsAppCloudApiException::MESSAGE_NOT_INITIALIZED_MESSAGE,
        'type' => WhatsAppCloudApiException::INTERNAL_EXCEPTION_TYPE
      ]);
    }
  }

  private function verifyTemplateComposed() {
    $this->verifyNotEmptyData();
    if (!$this->isTemplate()) {
      throw new WhatsAppCloudApiException([
        'code' => WhatsAppCloudApiException::INTERNAL_ERROR_CODE,
        'error_subcode' => WhatsAppCloudApiException::TEMPLATE_NOT_BUILT_SUB_CODE,
        'message' => WhatsAppCloudApiException::TEMPLATE_NOT_BUILT_MESSAGE,
        'type' => WhatsAppCloudApiException::INTERNAL_EXCEPTION_TYPE
      ]);
    }
  }

  private function getInitialData($type) {
    return [
      'messaging_product' => 'whatsapp',
      'recipient_type' => 'individual',
      'type' => $type
    ];
  }

  public function buildTemplate($name, $lang = null) {
    $this->data = $this->getInitialData(self::MESSAGE_TYPE_TEMPLATE);
    $this->data[self::MESSAGE_TYPE_TEMPLATE] = [
      'name' => $name,
      'language' => [
        'code' => $lang
      ],
      'components' => []
    ];

    return $this;
  }

  public function buildReaction($message_id, $emoji) {
    $this->data = $this->getInitialData(self::MESSAGE_TYPE_REACTION);
    $this->data[self::MESSAGE_TYPE_REACTION] = [
      'message_id' => $message_id,
      'emoji' => $emoji
    ];
    return $this;
  }

  private function isTemplate() {
    return $this->data['type'] == self::MESSAGE_TYPE_TEMPLATE;
  }

  public function setHeaderParams($parameters) {
    $this->verifyTemplateComposed();
    $this->templateHeaderParams = $this->getDerivedComponentParams($parameters);
    return $this;
  }

  public function setBodyParams($parameters) {
    $this->verifyTemplateComposed();
    $this->templateBodyParams = $this->getDerivedComponentParams($parameters);
    return $this;
  }

  public function setFooterParams($parameters) {
    $this->verifyTemplateComposed();
    $this->templateFooterParams = $this->getDerivedComponentParams($parameters);
    return $this;
  }

  private function getDerivedComponentParams($parameters) {
    $result = [];

    foreach ($parameters as $parameter) {
      $result[] = [
        'type' => $parameter['type'],
        $parameter['type'] => $parameter['value']
      ];
    }

    return $result;
  }

  private function bindTemplateParams() {
    $this->data['template']['components'] = [];
    if (!empty($this->templateHeaderParams)) {
      $this->data['template']['components'][] = [
        'type' => 'header',
        'parameters' => $this->templateHeaderParams
      ];
    }

    if (!empty($this->templateBodyParams)) {
      $this->data['template']['components'][] = [
        'type' => 'body',
        'parameters' => $this->templateBodyParams
      ];
    }

    if (!empty($this->templateFooterParams)) {
      $this->data['template']['components'][] = [
        'type' => 'footer',
        'parameters' => $this->templateFooterParams
      ];
    }
  }

  public function setTo($phone_number) {
    $this->verifyNotEmptyData();

    $this->data['to'] = $phone_number;
    return $this;
  }

  public function setLang($lang) {
    $this->verifyTemplateComposed();
    $this->data['template']['language']['code'] = $lang;
    return $this;
  }

  public function send() {
    $this->verifyNotEmptyData();

    if ($this->simulateSending) {
      return new Response([
        'id' => 'wamid.testMessageId',
        'contact' => $this->data['to']
      ]);
    }

    if ($this->isTemplate()) {
      $this->bindTemplateParams();
    }

    $curl = curl_init();
    curl_setopt_array($curl, $this->requestOptions);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($this->data));
    $result = json_decode(curl_exec($curl), true);

    if (!empty($result['error'])) {
      throw new WhatsAppCloudApiException($result['error']);
    }

    return new Response([
      'id' => $result['messages'][0]['id'],
      'contact' => $result['contacts'][0]['input']
    ]);
  }
}
