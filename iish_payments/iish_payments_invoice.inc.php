<?php
/**
 * @file
 * Defines the main form for users to pay an invoice online.
 */

/**
 * Implements hook_form()
 */
function iish_payments_invoice_form($form, &$form_state) {
  $invoiceInfo = variable_get('iish_payments_invoice_information');

  $form['body'] = array(
    '#type' => 'fieldset',
  );

  $form['body']['text'] = array(
    '#markup' => nl2br(filter_xss_admin($invoiceInfo['value'])),
  );

  $form['main'] = array(
    '#type' => 'fieldset',
  );

  $form['main']['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#maxlength' => 255,
    '#required' => TRUE,
  );

  $form['main']['email'] = array(
    '#type' => 'textfield',
    '#title' => t('E-mail'),
    '#maxlength' => 255,
    '#required' => TRUE,
  );

  $form['main']['invoice_number'] = array(
    '#type' => 'textfield',
    '#title' => t('Invoice number'),
    '#required' => TRUE,
  );

  $form['main']['amount'] = array(
    '#type' => 'textfield',
    '#title' => t('Amount in EUR'),
    '#required' => TRUE,
    '#default_value' => 0,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#name' => 'submit',
    '#value' => t('Pay online with iDeal or credit card'),
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

  if (!ctype_digit($form_state['values']['amount']) || (intval($form_state['values']['amount']) <= 0)) {
    form_set_error('amount', t('Please specify a valid amount.'));
  }
}

/**
 * Implements hook_form_submit()
 */
function iish_payments_invoice_form_submit($form, &$form_state) {
  global $language;

  $createOrderMessage = new PayWayMessage(array(
    'amount' => intval($form_state['values']['amount']) * 100,
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
      'We are sorry for the inconvenience. Please try again later.'));
  }
}
