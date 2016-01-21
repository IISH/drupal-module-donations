<?php
/**
 * @file
 * Defines the main form for users to register (and pay) for a membership or to donate money.
 */

/**
 * Implements hook_form()
 */
function iish_payments_friends_form($form, &$form_state) {
  // Make sure we always start at the choice page
  if (!isset($form_state['page'])) {
    $form_state['page'] = 'choice';
  }

  switch ($form_state['page']) {
    case 'membership':
      return iish_payments_friends_membership_form($form, $form_state);
      break;
    case 'donation':
      return iish_payments_friends_donation_form($form, $form_state);
      break;
    case 'choice':
    default:
      return iish_payments_friends_choice_form($form, $form_state);
  }
}

/**
 * Implements hook_form_validate()
 */
function iish_payments_friends_form_validate($form, &$form_state) {
  switch ($form_state['page']) {
    case 'membership':
      iish_payments_friends_membership_form_validate($form, $form_state);
      break;
    case 'donation':
      iish_payments_friends_donation_form_validate($form, $form_state);
      break;
  }
}

/**
 * Implements hook_form_submit()
 */
function iish_payments_friends_form_submit($form, &$form_state) {
  switch ($form_state['page']) {
    case 'membership':
      iish_payments_friends_membership_form_submit($form, $form_state);
      break;
    case 'donation':
      iish_payments_friends_donation_form_submit($form, $form_state);
      break;
    case 'choice':
    default:
      iish_payments_friends_choice_form_submit($form, $form_state);
      $form_state['page'] = ($form_state['choice'] === 'donation') ? 'donation' : 'membership';
      $form_state['rebuild'] = TRUE;
      break;
  }
}

/**
 * Implements hook_form()
 */
function iish_payments_friends_choice_form($form, &$form_state) {
  $friendsInfo = variable_get('iish_payments_friends_information');

  $form['info'] = array(
    '#markup' => nl2br(filter_xss_admin($friendsInfo['value'])),
  );

  $form['choice'] = array(
    '#type' => 'radios',
    '#title' => t('Please make a choice'),
    '#required' => TRUE,
    '#options' => array(
      'new' => t('I want to become a new member'),
      'renew' => t('I want to renew my membership'),
      'donation' => t('I want to make a donation to the IISH'),
    ),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#name' => 'submit',
    '#value' => t('Next'),
  );

  return $form;
}

/**
 * Implements hook_form_submit()
 */
function iish_payments_friends_choice_form_submit($form, &$form_state) {
  $form_state['choice'] = $form_state['values']['choice'];
}

/**
 * Implements hook_form()
 */
function iish_payments_friends_membership_form($form, &$form_state) {
  $choice = $form_state['choice'];

  if ($choice === 'new') {
    drupal_set_title(t('New Membership'));
  }
  else {
    drupal_set_title(t('Friends Membership Renewal'));
  }

  if ($choice === 'new') {
    $form['gender'] = array(
      '#type' => 'radios',
      '#title' => t('Gender'),
      '#required' => TRUE,
      '#options' => array(
        'male' => t('Male'),
        'female' => t('Female'),
      ),
    );
  }

  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#maxlength' => 255,
    '#required' => TRUE,
  );

  if ($choice === 'new') {
    $form['address'] = array(
      '#type' => 'textfield',
      '#title' => t('Address'),
      '#maxlength' => 255,
      '#required' => TRUE,
    );

    $form['zipcode'] = array(
      '#type' => 'textfield',
      '#title' => t('Zipcode'),
      '#maxlength' => 255,
      '#required' => TRUE,
    );

    $form['city'] = array(
      '#type' => 'textfield',
      '#title' => t('City'),
      '#maxlength' => 255,
      '#required' => TRUE,
    );

    $form['country'] = array(
      '#type' => 'textfield',
      '#title' => t('Country'),
      '#maxlength' => 255,
      '#required' => TRUE,
    );
  }

  $form['email'] = array(
    '#type' => 'textfield',
    '#title' => t('E-mail'),
    '#maxlength' => 255,
    '#required' => TRUE,
  );

  if ($choice === 'new') {
    $form['date_of_birth'] = array(
      '#type' => 'date_popup',
      '#title' => t('Date of birth'),
      '#required' => FALSE,
      '#date_format' => 'Y-m-d',
      '#default_value' => date('Y-m-d'),
      '#datepicker_options' => array(
        'yearRange' => '-150:',
        'minDate' => '-150y',
        'maxDate' => '-1',
      ),
    );
  }

  $form['amount'] = array(
    '#type' => 'radios',
    '#title' => t('Type of membership'),
    '#required' => TRUE,
    '#options' => array(
      100 => '&euro; 100,-',
      500 => '&euro; 500,-',
      1500 => '&euro; 1500,- (' . t('Lifetime member') . ')',
    ),
  );

  if ($choice === 'renew') {
    $form['year'] = array(
      '#type' => 'select',
      '#title' => t('Year'),
      '#required' => TRUE,
      '#options' => array_combine(range(2000, date('Y')), range(2000, date('Y'))),
      '#default_value' => date('Y'),
    );

    $form['invoice_number'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => t('Please fill in the invoice number'),
    );
  }

  $form['submit_online'] = array(
    '#type' => 'submit',
    '#name' => 'submit_online',
    '#value' => t('Pay online with iDEAL or credit card'),
  );

  if ($choice === 'new') {
    $form['submit_invoice'] = array(
      '#type' => 'submit',
      '#name' => 'submit_invoice',
      '#value' => t('Send me an invoice'),
    );
  }

  return $form;
}

/**
 * Implements hook_form_validate()
 */
function iish_payments_friends_membership_form_validate($form, &$form_state) {
  if (!valid_email_address($form_state['values']['email'])) {
    form_set_error('email', t('Please specify a valid e-mail address.'));
  }
}

/**
 * Implements hook_form_submit()
 */
function iish_payments_friends_membership_form_submit($form, &$form_state) {
  global $language;

  $choice = $form_state['choice'];
  $amount = (int) $form_state['values']['amount'];
  $donation = ($choice === 'new') ? (int) $form_state['values']['donation'] : 0;
  $isOnlinePayment = (($choice === 'renew') || ($form_state['triggering_element']['#name'] === 'submit_online'));

  if ($choice === 'new') {
    $description = 'New Friends membership';
  }
  else {
    $description = 'Friends membership renewal ' . (int) $form_state['values']['year'] .
      ' - Invoice number ' . $form_state['values']['invoice_number'];
  }

  $createOrderMessage = new PayWayMessage(array(
    'amount' => ($amount * 100) + ($donation * 100),
    'currency' => 'EUR',
    'language' => ($language->language === 'nl') ? 'nl_NL' : 'en_US',
    'cn' => $form_state['values']['name'],
    'email' => $form_state['values']['email'],
    'com' => $description,
    'paymentmethod' => $isOnlinePayment ? PayWayMessage::ORDER_OGONE_PAYMENT : PayWayMessage::ORDER_BANK_PAYMENT,
  ));

  $paywayService = new PayWayService();
  $orderMessage = $paywayService->send('createOrder', $createOrderMessage);

  // If creating a new order is successful, redirect to PayWay or to bank transfer information?
  if ($orderMessage !== FALSE) {
    $orderId = $orderMessage->get('orderid');

    $mailParams = $form_state['values'];
    $mailParams['choice'] = $choice;
    $mailParams['order_id'] = $orderId;
    $mailParams['is_online_payment'] = $isOnlinePayment;

    $emailFriends = variable_get('iish_payments_friends_email');
    drupal_mail('iish_payments_membership', NULL, $form_state['values']['email'], $language, $mailParams);
    drupal_mail('iish_payments_membership', NULL, $emailFriends, $language, $mailParams);

    // if set, send also bcc
    $emailBcc = trim(variable_get('iish_payments_bcc_email'));
    if ( $emailBcc != '' ) {
      drupal_mail('iish_payments_membership', NULL, $emailBcc, $language, $mailParams);
    }

    if ($isOnlinePayment) {
      $paymentMessage = new PayWayMessage(array('orderid' => $orderId));
      $paywayService->send('payment', $paymentMessage);
    }
    else {
      drupal_set_message(t('Thank you! We have sent you a confirmation email!'));
    }
  }
  else {
    drupal_set_message(t('Currently it is not possible to proceed to create a new order. ' .
      'We are sorry for the inconvenience. Please try again later.'), 'error');
  }
}

/**
 * Implements hook_form()
 */
function iish_payments_friends_donation_form($form, &$form_state) {
  drupal_set_title(t('Donation'));

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

  $form['amount'] = array(
    '#type' => 'textfield',
    '#title' => t('Amount in EUR'),
    '#required' => TRUE,
    '#default_value' => 0,
  );

  $form['submit_online'] = array(
    '#type' => 'submit',
    '#name' => 'submit_online',
    '#value' => t('Pay online with iDEAL or credit card'),
  );

  $form['submit_invoice'] = array(
    '#type' => 'submit',
    '#name' => 'submit_invoice',
    '#value' => t('Send me an invoice'),
  );

  return $form;
}

/**
 * Implements hook_form_validate()
 */
function iish_payments_friends_donation_form_validate($form, &$form_state) {
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
function iish_payments_friends_donation_form_submit($form, &$form_state) {
  global $language;

  $isOnlinePayment = ($form_state['triggering_element']['#name'] === 'submit_online');

  $createOrderMessage = new PayWayMessage(array(
    'amount' => ((int) $form_state['values']['amount']) * 100,
    'currency' => 'EUR',
    'language' => ($language->language === 'nl') ? 'nl_NL' : 'en_US',
    'cn' => $form_state['values']['name'],
    'email' => $form_state['values']['email'],
    'com' => 'Donation',
    'paymentmethod' => $isOnlinePayment ? PayWayMessage::ORDER_OGONE_PAYMENT : PayWayMessage::ORDER_BANK_PAYMENT,
  ));

  $paywayService = new PayWayService();
  $orderMessage = $paywayService->send('createOrder', $createOrderMessage);

  // If creating a new order is successful, redirect to PayWay
  if ($orderMessage !== FALSE) {
    $orderId = $orderMessage->get('orderid');

    $mailParams = $form_state['values'];
    $mailParams['order_id'] = $orderId;
    $mailParams['is_online_payment'] = $isOnlinePayment;

    $emailFriends = variable_get('iish_payments_friends_email');
    drupal_mail('iish_payments_donation', NULL, $form_state['values']['email'], $language, $mailParams);
    drupal_mail('iish_payments_donation', NULL, $emailFriends, $language, $mailParams);

    // if set, send also bcc
    $emailBcc = trim(variable_get('iish_payments_bcc_email'));
    if ( $emailBcc != '' ) {
      drupal_mail('iish_payments_donation', NULL, $emailBcc, $language, $mailParams);
    }

    if ($isOnlinePayment) {
      $paymentMessage = new PayWayMessage(array('orderid' => $orderId));
      $paywayService->send('payment', $paymentMessage);
    }
    else {
      drupal_set_message(t('Thank you! We have sent you a confirmation email!'));
    }
  }
  else {
    drupal_set_message(t('Currently it is not possible to proceed to create a new order. ' .
      'We are sorry for the inconvenience. Please try again later.'), 'error');
  }
}

/**
 * Implements hook_mail().
 */
function iish_payments_membership_mail($key, &$message, $params) {
  $isNewMembership = ($params['choice'] === 'new');
  $body = t('Dear @name,', array('@name' => check_plain($params['name']))) . "\r\n\r\n";

  if ($isNewMembership) {
    $body .= t('With this email we confirm your request for a new Friends membership.') . "\r\n";
  }
  else {
    $body .= t('With this email we confirm your request to renew your Friends membership.') . "\r\n";
  }
  $body .= t('These are the details you have sent us:') . "\r\n\r\n";

  if ($isNewMembership) {
    $body .= t('Gender') . ': ' . (($params['gender'] === 'male') ? t('Male') : t('Female')) . "\r\n";
  }

  $body .= t('Name') . ': ' . check_plain($params['name']) . "\r\n";

  if ($isNewMembership) {
    $body .= t('Address') . ': ' . check_plain($params['address']) . "\r\n";
    $body .= t('Zip code') . ': ' . check_plain($params['zipcode']) . "\r\n";
    $body .= t('City') . ': ' . check_plain($params['city']) . "\r\n";
    $body .= t('Country') . ': ' . check_plain($params['country']) . "\r\n";
  }

  $body .= t('E-mail') . ': ' . check_plain($params['email']) . "\r\n";

  if ($isNewMembership) {
    $body .= t('Date of birth') . ': ' . date('d-m-Y', strtotime($params['date_of_birth'])) . "\r\n";
  }

  $body .= "\r\n";
  $body .= t('Friends membership') . ': EUR ' . number_format($params['amount'], 2, ',', '.') . "\r\n";

  if (!$isNewMembership) {
    $body .= t('Year') . ': ' . check_plain($params['year']) . "\r\n";
    $body .= t('Invoice number') . ': ' . check_plain($params['invoice_number']) . "\r\n";
  }

  $body .= "\r\n";
  $body .= t('An order has been created with order id: @orderId', array('@orderId' => $params['order_id'])) . "\r\n";

  if ($isNewMembership && $params['is_online_payment']) {
    $body .= t('You have chosen to pay online using iDEAL or credit card.') . "\r\n";
  }
  else {
    if ($isNewMembership && !$params['is_online_payment']) {
      $body .= t('You have chosen to receive an invoice from us.') . "\r\n";
    }
  }

  $body .= "\r\n";
  $body .= t('With kind regards,') . "\r\n";
  $body .= t('IISH') . "\r\n";

  $message['subject'] = t('Confirmation of Friends membership request');
  $message['body'][] = $body;
}

/**
 * Implements hook_mail().
 */
function iish_payments_donation_mail($key, &$message, $params) {
  $body = t('Dear @name,', array('@name' => check_plain($params['name']))) . "\r\n\r\n";

  $body .= t('With this email we confirm your donation request.') . "\r\n\r\n";

  $body .= t('Name') . ': ' . check_plain($params['name']) . "\r\n";
  $body .= t('E-mail') . ': ' . check_plain($params['email']) . "\r\n\r\n";

  $body .= t('Donation') . ': EUR ' . number_format($params['amount'], 2, ',', '.') . "\r\n\r\n";

  $body .= t('An order has been created with order id: @orderId', array('@orderId' => $params['order_id'])) . "\r\n";

  if ($params['is_online_payment']) {
    $body .= t('You have chosen to pay online using iDEAL or credit card.') . "\r\n";
  }
  else {
    $body .= t('You have chosen to receive an invoice.') . "\r\n";
  }

  $body .= "\r\n";
  $body .= t('With kind regards,') . "\r\n";
  $body .= t('IISH') . "\r\n";

  $message['subject'] = t('Confirmation of donation request');
  $message['body'][] = $body;
}

function iish_payments_date_popup_process_alter(&$element, &$form_state, $context) {
  unset($element['date']['#title'], $element['date']['#description']);
}
