<?php

/**
 * A service for PayWay messages.
 */
class PayWayService {
  private $address;
  private $passphraseIn;
  private $passphraseOut;
  private $project;

  /**
   * Constructs the PayWay service.
   */
  public function __construct() {
    $this->address = variable_get('iish_payments_payway_address');
    $this->passphraseIn = variable_get('iish_payments_payway_passphrase_in');
    $this->passphraseOut = variable_get('iish_payments_payway_passphrase_out');
    $this->project = variable_get('iish_payments_payway_project');
  }

  /**
   * Sends this message to PayWay.
   * @param string $apiName The name of the PayWay API to send the message to.
   * @param PayWayMessage $message The PayWay message.
   * @return bool|PayWayMessage Returns a new PayWayMessage with the response, unless there is an error.
   * In that case, FALSE is returned. For payments, the user is redirected to the payment page.
   */
  public function send($apiName, PayWayMessage $message) {
    $this->addProject($message);
    $this->signIn($message);

    // If a payment has to be made, redirect the user to payment page
    if ($apiName === 'payment') {
      $this->redirectToPayWay($message);
    }
    else {
      $result = drupal_http_request(
        $this->address . $apiName,
        array(
          'headers' => array('Content-Type' => 'text/json'),
          'method' => 'POST',
          'data' => drupal_json_encode($message->getAsArray()),
        )
      );

      if ($result->code == 200) {
        $message = new PayWayMessage(drupal_json_decode($result->data));
        if ($this->isSignValid($message)) {
          return $message;
        }
      }
    }

    return FALSE;
  }

  /**
   * Checks to see if the message signed by PayWay is correct.
   * @param PayWayMessage $message The PayWay message.
   * @return bool Returns TRUE if the signature is valid
   */
  public function isSignValid(PayWayMessage $message) {
    $successExists = ($message->get('success') !== NULL);

    if (!$successExists || ($successExists && $message->get('success'))) {
      $curSign = $message->get('shasign');
      $this->signOut($message);

      return ($curSign == $message->get('shasign'));
    }

    return FALSE;
  }

  /**
   * Cleans up the message and signs the message for PayWay to receive.
   * @param PayWayMessage $message The PayWay message.
   */
  private function signIn(PayWayMessage $message) {
    $this->sign($message, TRUE);
  }

  /**
   * Cleans up the message and signs the message received from PayWay.
   * @param PayWayMessage $message The PayWay message.
   */
  private function signOut(PayWayMessage $message) {
    $this->sign($message, FALSE);
  }

  /**
   * Cleans up the message and signs the message.
   * @param PayWayMessage $message The PayWay message.
   * @param bool $in If this message is intended for PayWay to read.
   */
  private function sign(PayWayMessage $message, $in) {
    $passPhrase = $this->passphraseIn;
    if (!$in) {
      $passPhrase = $this->passphraseOut;
    }

    // Sort and cleanup the message
    $messageArray = $message->getAsArray();
    ksort($messageArray);
    unset($messageArray['SHASIGN']);

    // Create the signature and add it to the message
    $messageConcatenated = array();
    foreach ($messageArray as $parameter => $value) {
      // Boolean values are printed as '1' and '0', but should be printed as 'true' and 'false'
      if (is_bool($value)) {
        $value = $value ? 'true' : 'false';
      }
      $messageConcatenated[] = $parameter . '=' . $value;
    }

    $toBeHashed = implode($passPhrase, $messageConcatenated) . $passPhrase;
    $message->add('shasign', sha1($toBeHashed));
  }

  /**
   * Adds the project name to the message.
   * @param PayWayMessage $message The PayWay message.
   */
  private function addProject(PayWayMessage $message) {
    $message->add('project', $this->project);
  }

  /**
   * Redirects the user to PayWay payment page with this message.
   * @param PayWayMessage $message The PayWay message.
   */
  private function redirectToPayWay(PayWayMessage $message) {
    header('Location: ' . $this->address . 'payment?' . http_build_query($message->getAsArray(), NULL, '&'));
    die();
  }
} 