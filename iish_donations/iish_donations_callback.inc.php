<?php
/**
 * @file
 * Handles the callback states from PayWay after a payment attempt has been made.
 */

/**
 * Called when a payment was accepted.
 * @return string The message for the user.
 */
function iish_donations_callback_accept() {
  $paywayService = new PayWayService();
  $paymentResponseMessage = new PayWayMessage(drupal_get_query_parameters());

  // 'POST' indicates that it is a one time response after the payment has been made, in our case, to send an email
  if ($paywayService->isSignValid($paymentResponseMessage) && $paymentResponseMessage->get('post')) {
    $orderId = $paymentResponseMessage->get('orderid');

    $orderDetailsMessage = $paywayService->send('orderDetails', new PayWayMessage(array('orderid' => $orderId)));
    if ($orderDetailsMessage !== FALSE) {
      db_update('iish_donations_orders')
        ->fields(iish_donations_map_payway_message($orderDetailsMessage))
        ->condition('order_id', $orderId, '=')
        ->execute();
    }
  }

  return t('Thank you. The procedure has been completed successfully!') . '<br />' . t('Within a few minutes ' .
    'you will receive an email from our payment provider confirming your payment.');
}

/**
 * Called when a payment was declined.
 * @return string The message for the user.
 */
function iish_donations_callback_decline() {
  return t('Unfortunately, your payment has been declined. Please try to finish your payment at a later moment ' .
    'or try a different payment method.');
}

/**
 * Called when a payment result is uncertain.
 * @return string The message for the user.
 */
function iish_donations_callback_exception() {
  return t('Unfortunately, your payment result is uncertain at the moment.') . '<br />' . t('Please contact ' .
    '!email to request information on your payment transaction.',
    array('!email' => variable_get('iish_donations_friends_email')));
}