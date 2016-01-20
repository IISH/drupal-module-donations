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
  return 'Thank you. Your payment has been completed successfully!<br />' .
    'Within a few minutes you will receive an email confirming your payment.<br />' .
	'<br /><hr /><br />' .
	'Hartelijk dank. Uw betaling is geslaagd!<br />' .
	'Binnen enkele minuten ontvangt u een bevestiging per e-mail.<br />';
}

/**
 * Called when a payment was declined.
 * @return string The message for the user.
 */
function iish_payments_callback_decline() {
  return 'Unfortunately, your payment has been declined.<br />' .
    'Please try to finish your payment at a later moment or try a different payment method.<br />' .
    '<br /><hr /><br />' .
    'Helaas, uw betaling is niet geslaagd.<br />' .
    'U kunt op een later tijdstip opnieuw proberen, of probeer een andere betaalmethode.<br />';
}

/**
 * Called when a payment result is uncertain.
 * @return string The message for the user.
 */
function iish_payments_callback_exception() {
  return 'Unfortunately, your payment result is uncertain at the moment.<br />Please contact ' .
    variable_get('iish_payments_friends_email') . ' to request information on your payment transaction.<br />' .
    '<br /><hr /><br />' .
    'Helaas, door een technische storing kunnen we de status van uw betaling niet verifiëren.<br />' .
    'Neem a.u.b. contact op met ' . variable_get('iish_payments_friends_email') .
    ' om informatie betreffende uw betaling op te vragen.<br />';
}
