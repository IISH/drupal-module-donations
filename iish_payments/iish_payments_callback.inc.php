<?php
/**
 * @file
 * Handles the callback states from PayWay after a payment attempt has been made.
 */

/**
 * Called when a payment was accepted.
 * @return string The message for the user.
 */
function iish_payments_callback_accept() {
  return t('Thank you. The procedure has been completed successfully!') . '<br />' . t('Within a few minutes ' .
    'you will receive an email from our payment provider confirming your payment.');
}

/**
 * Called when a payment was declined.
 * @return string The message for the user.
 */
function iish_payments_callback_decline() {
  return t('Unfortunately, your payment has been declined. Please try to finish your payment at a later moment ' .
    'or try a different payment method.');
}

/**
 * Called when a payment result is uncertain.
 * @return string The message for the user.
 */
function iish_payments_callback_exception() {
  return t('Unfortunately, your payment result is uncertain at the moment.') . '<br />' . t('Please contact ' .
    '!email to request information on your payment transaction.',
    array('!email' => variable_get('iish_payments_friends_email')));
}