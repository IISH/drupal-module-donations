<?php

/**
 * Represents messages from and to PayWay.
 */
class PayWayMessage {
  const ORDER_NOT_PAYED = 0;
  const ORDER_PAYED = 1;
  const ORDER_REFUND_OGONE = 2;
  const ORDER_REFUND_BANK = 3;

  const ORDER_OGONE_PAYMENT = 0;
  const ORDER_BANK_PAYMENT = 1;
  const ORDER_CASH_PAYMENT = 2;

  const PAYMENT_ACCEPTED = 1;
  const PAYMENT_DECLINED = 2;
  const PAYMENT_EXCEPTION = 3;
  const PAYMENT_CANCELLED = 4;
  const PAYMENT_OTHER_STATUS = 5;

  private $message;

  /**
   * Creates a new message from or to PayWay.
   * @param array $message The message parameters and their keys.
   * @return \PayWayMessage The PayWay message.
   */
  public function __construct(array $message = array()) {
    $this->message = array();

    foreach ($message as $parameter => $value) {
      $this->add($parameter, $value);
    }
  }

  /**
   * Adds a new parameter/value pair to the message.
   * @param string $parameter The parameter, as defined by PayWay.
   * @param mixed $value The value for this parameter.
   * @return PayWayMessage The PayWay message.
   */
  public function add($parameter, $value) {
    $parameter = trim(strtoupper($parameter));

    $paramIsOk = !empty($parameter);
    $valueIsOk = is_string($value) ? strlen(trim($value)) > 0 : !is_null($value);

    if ($paramIsOk && $valueIsOk) {
      $this->message[$parameter] = $value;
    }

    return $this;
  }

  /**
   * Returns the date/time value for the given parameter.
   * @param string $parameter The parameter in question.
   * @return int|null The time if it can be parsed.
   */
  public function getDateTime($parameter) {
    $parameter = trim(strtoupper($parameter));

    if (array_key_exists($parameter, $this->message)) {
      return strtotime($this->message[$parameter]);
    }
    else {
      return NULL;
    }
  }

  /**
   * Returns the value for the given parameter.
   * @param string $parameter The parameter in question.
   * @return mixed The value of the given parameter in this message.
   */
  public function get($parameter) {
    $parameter = trim(strtoupper($parameter));

    if (array_key_exists($parameter, $this->message)) {
      return $this->message[$parameter];
    }
    else {
      return NULL;
    }
  }

  /**
   * Returns this PayWay message as an array.
   * @return array The message.
   */
  public function getAsArray() {
    return $this->message;
  }
} 