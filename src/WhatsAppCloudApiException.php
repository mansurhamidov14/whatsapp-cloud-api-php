<?php

namespace Twelver313\WhatsAppCloudApi;

use \Exception;

class WhatsAppCloudApiException extends Exception {
  const INTERNAL_ERROR_CODE = 100000000;
  const INTERNAL_EXCEPTION_TYPE = 'WhatsAppCloudApiException';

  const MESSAGE_NOT_INITIALIZED_SUB_CODE = 1;
  const TEMPLATE_NOT_BUILT_SUB_CODE = 2;

  const MESSAGE_NOT_INITIALIZED_MESSAGE = 'You have not initialized any type of message yet';
  const TEMPLATE_NOT_BUILT_MESSAGE = 'You have not built any template';

  public $type;
  public $code;
  public $subCode;
  public $message;
  public $data;

  public function __construct($error) {
    $this->code = $error['code'];
    $this->message = $error['message'];

    if (!empty($error['type'])) {
      $this->subCode = $error['type'];
    }

    if (!empty($error['error_subcode'])) {
      $this->subCode = $error['error_subcode'];
    }

    if (!empty($error['error_data'])) {
      $this->data = $error['error_data'];
    }
  }
}
