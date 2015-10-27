<?php
/**
 * @file
 * Defines the main form for users to register for a membership or donate money.
 */

/**
 * Implements hook_form()
 */
function iish_donations_main_form($form, &$form_state) {
  $friendsInfo = variable_get('iish_donations_friends_information');

  $form['body'] = array(
    '#type' => 'fieldset',
  );

  $form['body']['text'] = array(
    '#markup' => nl2br(filter_xss_admin($friendsInfo['value'])),
  );

  $form['personal_info'] = array(
    '#type' => 'fieldset',
    '#title' => t('General'),
  );

  $form['personal_info']['gender'] = array(
    '#type' => 'radios',
    '#title' => t('Gender'),
    '#required' => TRUE,
    '#options' => array(
      'male' => t('Male'),
      'female' => t('Female'),
    ),
  );

  $form['personal_info']['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#maxlength' => 255,
    '#required' => TRUE,
  );

  $form['personal_info']['address'] = array(
    '#type' => 'textfield',
    '#title' => t('Address'),
    '#maxlength' => 255,
    '#required' => TRUE,
  );

  $form['personal_info']['zipcode'] = array(
    '#type' => 'textfield',
    '#title' => t('Zipcode'),
    '#maxlength' => 255,
    '#required' => TRUE,
  );

  $form['personal_info']['city'] = array(
    '#type' => 'textfield',
    '#title' => t('City'),
    '#maxlength' => 255,
    '#required' => TRUE,
  );

  $form['personal_info']['country'] = array(
    '#type' => 'textfield',
    '#title' => t('Country'),
    '#maxlength' => 255,
    '#required' => TRUE,
  );

  $form['personal_info']['email'] = array(
    '#type' => 'textfield',
    '#title' => t('E-mail'),
    '#maxlength' => 255,
    '#required' => TRUE,
  );

  $form['personal_info']['date_of_birth'] = array(
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

  $form['friends'] = array(
    '#type' => 'fieldset',
    '#title' => t('Membership Friends'),
  );

  $form['friends']['membership'] = array(
    '#type' => 'checkbox',
    '#title' => t('Membership'),
  );

  $form['friends']['amount'] = array(
    '#type' => 'radios',
    '#title' => t('Type of membership'),
    '#options' => array(
      100 => '&euro; 100,-',
      500 => '&euro; 500,-',
      1500 => '&euro; 1500,- (' . t('Lifetime member') . ')',
    ),
  );

  $form['friends']['year'] = array(
    '#type' => 'select',
    '#title' => t('Year'),
    '#options' => array_combine(range(2000, date('Y')), range(2000, date('Y'))),
    '#default_value' => date('Y'),
  );

  $form['friends']['bank_account'] = array(
    '#type' => 'textfield',
    '#title' => t('Bank account number'),
  );

  $form['donation'] = array(
    '#type' => 'fieldset',
    '#title' => t('Donations'),
  );

  $form['donation']['donation'] = array(
    '#type' => 'textfield',
    '#title' => t('Donation in EUR'),
    '#required' => FALSE,
    '#default_value' => 0,
  );

  $form['submit_online'] = array(
    '#type' => 'submit',
    '#name' => 'submit_online',
    '#value' => t('Pay online with iDeal or credit card'),
  );

  $form['submit_invoice'] = array(
    '#type' => 'submit',
    '#name' => 'submit_invoice',
    '#value' => t('Sent me an invoice'),
  );

  return $form;
}

/**
 * Implements hook_form_validate()
 */
function iish_donations_main_form_validate($form, &$form_state) {
  if (!valid_email_address($form_state['values']['email'])) {
    form_set_error('email', t('Please specify a valid e-mail address.'));
  }

  if (($form_state['values']['membership'] === 0) && (intval($form_state['values']['donation']) <= 0)) {
    form_set_error('donation', t('Please specify a membership and/or a donation.'));
  }

  if (($form_state['values']['membership'] === 1) && ($form_state['values']['amount'] === NULL)) {
    form_set_error('amount', t('Please select a membership option.'));
  }

  if (($form_state['values']['membership'] === 1) && (strlen(trim($form_state['values']['bank_account'])) === 0)) {
    form_set_error('bank_account', t('Please specify your bank account number.'));
  }

  if (!ctype_digit($form_state['values']['donation'])) {
    form_set_error('donation', t('Please specify a valid donation amount.'));
  }
}

/**
 * Implements hook_form_submit()
 */
function iish_donations_main_form_submit($form, &$form_state) {
  global $language;

  $personId = db_insert('iish_donations_persons')
    ->fields(array(
      'name' => $form_state['values']['name'],
      'address' => $form_state['values']['address'],
      'zipcode' => $form_state['values']['zipcode'],
      'city' => $form_state['values']['city'],
      'country' => $form_state['values']['country'],
      'email' => $form_state['values']['email'],
      'date_of_birth' => $form_state['values']['date_of_birth'],
      'gender' => $form_state['values']['gender'],
      'created_on' => date("Y-m-d H:i:s", time()),
    ))->execute();

  $total = 0;
  $descriptions = array();
  $isOnlinePayment = ($form_state['triggering_element']['#name'] === 'submit_online');

  // Determine Friends membership
  if ($form_state['values']['membership'] === 1) {
    $year = (int) $form_state['values']['year'];
    $membership = (int) $form_state['values']['amount'];
    $total += $membership;
    $descriptions[] = t('Friends membership') . ' ' . $year . ': ' . $membership . ' EUR';

    db_insert('iish_donations_memberships')
      ->fields(array(
        'person_id' => $personId,
        'year' => $year,
        'bank_account' => $form_state['values']['bank_account'],
        'amount' => $membership,
      ))->execute();
  }

  // Determine a possible donation
  $donation = intval($form_state['values']['donation']);
  if ($donation > 0) {
    $total += $donation;
    $descriptions[] = t('Donation') . ': ' . $donation . ' EUR';

    db_insert('iish_donations_donations')
      ->fields(array(
        'person_id' => $personId,
        'amount' => $donation,
      ))->execute();
  }

  $createOrderMessage = new PayWayMessage(array(
    'amount' => $total * 100,
    'currency' => 'EUR',
    'language' => ($language->language === 'nl') ? 'nl_NL' : 'en_US',
    'cn' => $form_state['values']['name'],
    'email' => $form_state['values']['email'],
    'com' => implode(', ', $descriptions),
    'paymentmethod' => ($isOnlinePayment) ? PayWayMessage::ORDER_OGONE_PAYMENT : PayWayMessage::ORDER_BANK_PAYMENT,
    'userid' => $personId,
  ));

  $paywayService = new PayWayService();
  $orderMessage = $paywayService->send('createOrder', $createOrderMessage);

  // If creating a new order is successful, redirect to PayWay or to bank transfer information?
  if ($orderMessage !== FALSE) {
    $orderId = $orderMessage->get('orderid');

    $orderDetailsMessage = $paywayService->send('orderDetails', new PayWayMessage(array('orderid' => $orderId)));
    if ($orderDetailsMessage !== FALSE) {
      db_insert('iish_donations_orders')
        ->fields(iish_donations_map_payway_message($orderDetailsMessage))
        ->execute();

      $mailParams = $form_state['values'];
      $mailParams['order_id'] = $orderId;

      $emailFriends = variable_get('iish_donations_friends_email');
      drupal_mail('iish_donations', NULL, $form_state['values']['email'], $language, $mailParams);
      drupal_mail('iish_donations', NULL, $emailFriends, $language, $mailParams);

      if ($isOnlinePayment) {
        $paymentMessage = new PayWayMessage(array('orderid' => $orderId));
        $paywayService->send('payment', $paymentMessage);
      }
      else {
        drupal_set_message(t('Thank you! We have sent you a confirmation email!'));
      }
    }
  }
  else {
    drupal_set_message(t('Currently it is not possible to proceed to create a new order. ' .
      'We are sorry for the inconvenience. Please try again later.'));
  }
}

/**
 * Implements hook_mail().
 */
function iish_donations_mail($key, &$message, $params) {
  $hasMembership = ($params['membership'] === 1);
  $hasDonation = (intval($params['donation']) > 0);

  if ($hasMembership && $hasDonation) {
    $type = t('Friends membership and donation');
  }
  else {
    if ($hasMembership) {
      $type = t('Friends membership');
    }
    else {
      $type = t('donation');
    }
  }

  $body = t('Dear @name,', array('@name' => check_plain($params['name']))) . "\r\n\r\n";
  $body .= t('With this email we confirm your !type.', array('!type' => $type)) . "\r\n";
  $body .= t('These are the details you have sent us:') . "\r\n\r\n";

  $body .= t('Gender') . ': ' . (($params['gender'] === 'male') ? t('Male') : t('Female')) . "\r\n";
  $body .= t('Name') . ': ' . check_plain($params['name']) . "\r\n";
  $body .= t('Address') . ': ' . check_plain($params['address']) . "\r\n";
  $body .= t('Zip code') . ': ' . check_plain($params['zipcode']) . "\r\n";
  $body .= t('City') . ': ' . check_plain($params['city']) . "\r\n";
  $body .= t('Country') . ': ' . check_plain($params['country']) . "\r\n";
  $body .= t('E-mail') . ': ' . check_plain($params['email']) . "\r\n";
  $body .= t('Date of birth') . ': ' . date('d-m-Y', strtotime($params['date_of_birth'])) . "\r\n\r\n";

  if ($hasMembership) {
    $body .= t('Membership Friends') . ': EUR ' . number_format($params['amount'], 2, ',', '.') . "\r\n";
    $body .= t('Year') . ': ' . check_plain($params['year']) . "\r\n";
    $body .= t('Bank account number') . ': ' . check_plain($params['bank_account']) . "\r\n\r\n";
  }

  if ($hasDonation) {
    $body .= t('Donation') . ': EUR ' . number_format($params['donation'], 2, ',', '.') . "\r\n\r\n";
  }

  $body .= t('An order has been created with order id: @orderId', array('@orderId' => $params['order_id']))
    . "\r\n\r\n";

  $body .= t('With kind regards,') . "\r\n";
  $body .= t('IISH') . "\r\n";

  $message['subject'] = t('Confirmation of !type', array('!type' => $type));
  $message['body'][] = $body;
}

function iish_donations_date_popup_process_alter(&$element, &$form_state, $context) {
  unset($element['date']['#title']);
  unset($element['date']['#description']);
}