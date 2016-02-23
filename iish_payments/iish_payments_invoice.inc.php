<?php
/**
 * @file
 * Defines the main form for users to pay an invoice online.
 */

/**
 * Implements hook_form()
 */
function iish_payments_invoice_form($form, &$form_state) {
  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#maxlength' => 255,
    '#required' => TRUE,
  );

  $form['email'] = array(
    '#type' => 'textfield',
    '#title' => t('E-mail'),
    '#maxlength' => 255,
    '#required' => TRUE,
  );

  $form['invoice_number'] = array(
    '#type' => 'textfield',
    '#title' => t('Invoice number'),
    '#required' => TRUE,
  );

  $form['amount'] = array(
    '#type' => 'textfield',
    '#title' => t('Amount in EUR'),
    '#required' => TRUE,
    '#default_value' => 0,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#name' => 'submit',
    '#value' => t('Pay online with iDEAL or credit card'),
  );

  return $form;
}

/**
 * Implements hook_form_validate()
 */
function iish_payments_invoice_form_validate($form, &$form_state) {
  if (!valid_email_address($form_state['values']['email'])) {
    form_set_error('email', t('Please specify a valid e-mail address.'));
  }

  $validAmount = false;
  $amount = $form_state['values']['amount'];
  $amount = str_replace('.', ',', $amount);
  $amountSplit = explode(',', $amount);
  if ((count($amountSplit) === 2) && (strlen($amountSplit[1]) === 2)) {
    if (ctype_digit($amountSplit[0]) && ctype_digit($amountSplit[1])) {
      if (((int) $amountSplit[0] >= 0) && ((int) $amountSplit[1] >= 0)) {
        $validAmount = true;
      }
    }
  }

  if (!$validAmount) {
    form_set_error('amount', t('Please specify a valid amount.'));
  }
}

/**
 * Implements hook_form_submit()
 */
function iish_payments_invoice_form_submit($form, &$form_state) {
  global $language;

  $amount = $form_state['values']['amount'];
  $amount = str_replace('.', ',', $amount);
  $amountSplit = explode(',', $amount);
  $amountInCents = (((int) $amountSplit[0]) * 100) + ((int) $amountSplit[1]);

  $createOrderMessage = new PayWayMessage(array(
    'amount' => $amountInCents,
    'currency' => 'EUR',
    'language' => ($language->language === 'nl') ? 'nl_NL' : 'en_US',
    'cn' => $form_state['values']['name'],
    'email' => $form_state['values']['email'],
    'com' => 'Invoice: ' . trim($form_state['values']['invoice_number']),
    'paymentmethod' => PayWayMessage::ORDER_OGONE_PAYMENT,
    'owneraddress' => (isset($form_state['values']['address'])) ? trim($form_state['values']['address']) : '',
    'ownerzip' => (isset($form_state['values']['zipcode'])) ? trim($form_state['values']['zipcode']) : '',
    'ownertown' => (isset($form_state['values']['city'])) ? trim($form_state['values']['city']) : '',
    'ownercty' => (isset($form_state['values']['country'])) ? trim($form_state['values']['country']) : '',
  ));

  $paywayService = new PayWayService();
  $orderMessage = $paywayService->send('createOrder', $createOrderMessage);

  // If creating a new order is successful, redirect to PayWay
  if ($orderMessage !== FALSE) {
    $paymentMessage = new PayWayMessage(array('orderid' => $orderMessage->get('orderid')));
    $paywayService->send('payment', $paymentMessage);
  }
  else {
    drupal_set_message(t('Currently it is not possible to proceed to create a new order. ' .
      'We are sorry for the inconvenience. Please try again later.'), 'error');
  }
}
