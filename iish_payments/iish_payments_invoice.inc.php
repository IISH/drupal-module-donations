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

  if (!ctype_digit($form_state['values']['amount']) || ((int) $form_state['values']['amount'] <= 0)) {
    form_set_error('amount', t('Please specify a valid amount.'));
  }
}

/**
 * Implements hook_form_submit()
 */
function iish_payments_invoice_form_submit($form, &$form_state) {
  global $language;

  $createOrderMessage = new PayWayMessage(array(
    'amount' => ((int) $form_state['values']['amount']) * 100,
    'currency' => 'EUR',
    'language' => ($language->language === 'nl') ? 'nl_NL' : 'en_US',
    'cn' => $form_state['values']['name'],
    'email' => $form_state['values']['email'],
    'com' => 'Invoice: ' . trim($form_state['values']['invoice_number']),
    'paymentmethod' => PayWayMessage::ORDER_OGONE_PAYMENT,
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
