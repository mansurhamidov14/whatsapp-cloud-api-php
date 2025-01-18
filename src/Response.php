<?php

namespace Twelver313\WhatsAppCloudApi;

class Response {
  public $id;
  public $receiver;

  public function __construct($options) {
    $this->id = $options['id'];
    $this->receiver = $options['contact'];
  }
}
