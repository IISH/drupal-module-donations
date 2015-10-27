<?php
/**
 * @file
 * Defines the overview of users who registered for a membership or donated money.
 */

/**
 * Creates a HTML overview of all Friend memberships and donations by year of creation.
 * @param int|string|null $year The year.
 * @return string The overview in HTML.
 */
function iish_donations_overview_all($year = NULL) {
  return iish_donations_overview('all', $year);
}

/**
 * Creates a HTML overview of all Friend memberships (and donations) by year of membership.
 * @param int|string|null $year The year.
 * @return string The overview in HTML.
 */
function iish_donations_overview_friends($year = NULL) {
  return iish_donations_overview('friends', $year);
}

/**
 * Creates a HTML overview of Friend memberships and donations.
 * @param string $type The type of overview.
 * @param int|string|null $year The year.
 * @return string The overview in HTML.
 */
function iish_donations_overview($type, $year = NULL) {
  $curYear = (int) date('Y');
  if (!ctype_digit($year) || (((int) $year) < 2000) || (((int) $year) > $curYear)) {
    $year = $curYear;
  }
  $year = (int) $year;

  $headerData = iish_donations_get_header_data();
  $results = iish_donations_get_list($type, $year, $headerData);

  $rows = array();
  foreach ($results as $row) {
    $detailInfoHtml = '<div class="iish-donations-toggle-div" style="display:none;">';
    $detailInfoHtml .= iish_donations_get_detailed_data($row);
    $detailInfoHtml .= '</div>';

    $rows[] = array(
      'class' => array('iish-donations-click'),
      'data' => array(
        date('d-m-Y H:i', strtotime($row->created_on)),
        check_plain($row->name),
        check_plain($row->email),
        ($row->amount_membership) ? '&euro; ' . number_format($row->amount_membership, 2, ',', '.') : '-',
        ($row->amount_donation) ? '&euro; ' . number_format($row->amount_donation, 2, ',', '.') : '-',
        ((int) $row->payment_method === PayWayMessage::ORDER_OGONE_PAYMENT)
          ? t('Online payment')
          : t('By bank transfer'),
        iish_donations_get_payment_status((int) $row->order_id, (int) $row->payed, (int) $row->payment_method),
      ),
    );

    $rows[] = array(
      'no_striping' => TRUE,
      'class' => array('iish-donations-toggle'),
      'data' => array(
        array(
          'colspan' => 7,
          'data' => $detailInfoHtml,
        ),
      ),
    );
  }

  if (count($rows) > 0) {
    $totals = iish_donations_get_totals($type, $year);
    $rows[] = array(
      'no_striping' => TRUE,
      'class' => array('iish-donations-totals'),
      'data' => array(
        array(
          'colspan' => 3,
          'data' => t('Total') . ':'
        ),
        array(
          'colspan' => 1,
          'data' => '&euro; ' . number_format($totals[0], 2, ',', '.')
        ),
        array(
          'colspan' => 1,
          'data' => '&euro; ' . number_format($totals[1], 2, ',', '.')
        ),
        array('colspan' => 2, 'data' => '&nbsp;'),
      ),
    );
  }

  $htmlPrev = l('<<', 'donations/overview/' . $type . '/' . ($year - 1));
  $htmlNext = l('>>', 'donations/overview/' . $type . '/' . ($year + 1));

  $header = '<div class="iish-donations-prev-next">';
  if ($year > 2000) {
    $header .= $htmlPrev . '&nbsp;';
  }
  $header .= t('Year') . ': ' . $year . '&nbsp;';
  if ($year < $curYear) {
    $header .= $htmlNext . '&nbsp;';
  }
  $header .= '</div>';

  $htmlTable = theme('table', array(
    'header' => $headerData,
    'rows' => $rows,
    'attributes' => array('class' => array('iish-donations-table')),
  ));

  return $header . $htmlTable;
}

/**
 * Let PayWay know that the payment of a non-Ogone payment has been accepted.
 * @param int $orderId The id of the order in question.
 * @return string The order details in JSON.
 */
function iish_donations_order_set_payed($orderId) {
  $paywayService = new PayWayService();
  $paymentResultMessage = $paywayService->send('nonOgonePaymentResponse', new PayWayMessage(array(
    'orderid' => $orderId,
    'paymentresult' => PayWayMessage::PAYMENT_ACCEPTED,
  )));

  if ($paymentResultMessage !== FALSE) {
    $orderDetailsMessage = $paywayService->send('orderDetails', new PayWayMessage(array('orderid' => $orderId)));
    if ($orderDetailsMessage !== FALSE) {
      $result = iish_donations_map_payway_message($orderDetailsMessage);

      db_update('iish_donations_orders')
        ->fields($result)
        ->condition('order_id', $orderId, '=')
        ->execute();

      return iish_donations_get_payment_status(
        (int) $result['order_id'],
        (int) $result['payed'],
        (int) $result['payment_method']
      );
    }
  }

  drupal_not_found();
  exit();
}

/**
 * Defines the header data.
 * @return array The header data.
 */
function iish_donations_get_header_data() {
  return array(
    array(
      'data' => t('Created on'),
      'field' => 'created_on',
      'sort' => 'desc',
    ),
    array(
      'data' => t('Name'),
      'field' => 'name',
      'sort' => 'asc',
    ),
    array(
      'data' => t('E-mail'),
      'field' => 'email',
      'sort' => 'asc',
    ),
    array(
      'data' => t('Friends'),
      'field' => 'amount_membership',
      'sort' => 'asc',
    ),
    array(
      'data' => t('Donation'),
      'field' => 'amount_donation',
      'sort' => 'asc',
    ),
    array(
      'data' => t('Payment method'),
      'field' => 'payment_method',
      'sort' => 'asc',
    ),
    array(
      'data' => t('Payment status'),
      'field' => 'payed',
      'sort' => 'asc',
    ),
  );
}

/**
 * Queries the database for a list of memberships and donations.
 * @param string $type The type of overview.
 * @param int $year The year to query for.
 * @param array $headerData The header data (for sorting).
 * @return The query results.
 */
function iish_donations_get_list($type, $year, $headerData) {
  $select = db_select('iish_donations_persons', 'p')->extend('TableSort');

  $select->leftJoin('iish_donations_memberships', 'm', 'p.id = m.person_id');
  $select->leftJoin('iish_donations_donations', 'd', 'p.id = d.person_id');
  $select->leftJoin('iish_donations_orders', 'o', 'p.id = o.person_id');

  $select->addField('m', 'amount', 'amount_membership');
  $select->addField('d', 'amount', 'amount_donation');

  if ($type === 'friends') {
    $select->condition('year', $year, '=');
  }
  else {
    $select
      ->condition('created_on', date('Y-m-d', strtotime('01-01-' . $year)), '>=')
      ->condition('created_on', date('Y-m-d', strtotime('31-12-' . $year)), '<=');
  }

  $select
    ->fields('p', array(
      'name',
      'address',
      'zipcode',
      'city',
      'country',
      'email',
      'date_of_birth',
      'gender',
      'created_on'
    ))
    ->fields('m', array('year', 'bank_account'))
    ->fields('o', array('order_id', 'payment_method', 'payed'))
    ->orderByHeader($headerData);

  return $select->execute();
}

/**
 * Queries the database for the total amounts.
 * @param string $type The type of overview.
 * @param int $year The year to query for.
 * @return array The total amounts of memberships and donations.
 */
function iish_donations_get_totals($type, $year) {
  $selectFriends = db_select('iish_donations_persons', 'p');
  $selectDonations = db_select('iish_donations_persons', 'p');

  $selectFriends->leftJoin('iish_donations_memberships', 'm', 'p.id = m.person_id');
  $selectDonations->leftJoin('iish_donations_donations', 'd', 'p.id = d.person_id');

  $selectFriends->addExpression('SUM(m.amount)', 'total_amount');
  $selectDonations->addExpression('SUM(d.amount)', 'total_amount');

  if ($type === 'friends') {
    $selectDonations->leftJoin('iish_donations_memberships', 'm', 'p.id = m.person_id');
    $selectDonations->condition('year', $year, '=');
    $selectFriends->condition('year', $year, '=');
  }
  else {
    $selectFriends
      ->condition('created_on', date('Y-m-d', strtotime('01-01-' . $year)), '>=')
      ->condition('created_on', date('Y-m-d', strtotime('31-12-' . $year)), '<=');

    $selectDonations
      ->condition('created_on', date('Y-m-d', strtotime('01-01-' . $year)), '>=')
      ->condition('created_on', date('Y-m-d', strtotime('31-12-' . $year)), '<=');
  }


  return array(
    $selectFriends->execute()->fetchField(),
    $selectDonations->execute()->fetchField()
  );
}

/**
 * Creates the HTML for detailed information of a single result.
 * @param object $data The data.
 * @return string The HTML.
 */
function iish_donations_get_detailed_data($data) {
  $output = '<div class="iish-donations-data-column">';

  $output .= '<strong>' . t('Gender') . '</strong> ' . (($data->gender === 'male') ? t('Male') : t('Female')) . '<br/>';
  $output .= '<strong>' . t('Name') . '</strong> ' . check_plain($data->name) . '<br/>';
  $output .= '<strong>' . t('Address') . '</strong> ' . check_plain($data->address) . '<br/>';
  $output .= '<strong>' . t('Zip code') . '</strong> ' . check_plain($data->zipcode) . '<br/>';
  $output .= '<strong>' . t('City') . '</strong> ' . check_plain($data->city) . '<br/>';
  $output .= '<strong>' . t('Country') . '</strong> ' . check_plain($data->country) . '<br/>';
  $output .= '<strong>' . t('E-mail') . '</strong> ' . check_plain($data->email) . '<br/>';
  $output .= '<strong>' . t('Date of birth') . '</strong> ' . date('d-m-Y', strtotime($data->date_of_birth)) . '<br/>';
  $output .= '<strong>' . t('Created on') . '</strong> ' . date('d-m-Y H:i', strtotime($data->created_on)) . '<br/>';

  $output .= '</div><div class="iish-donations-data-column">';

  $output .= '<strong>' . t('Membership Friends') . '</strong> ';
  $output .= ($data->amount_membership)
    ? '&euro; ' . number_format($data->amount_membership, 2, ',', '.') . '<br/>'
    : '- <br/>';
  $output .= '<strong>' . t('Year') . '</strong> ' . (($data->year) ? check_plain($data->year) : '-') . '<br/>';
  $output .= '<strong>' . t('Bank account number') . '</strong> ' . (($data->bank_account) ? check_plain($data->bank_account) : '-') . '<br/>';

  $output .= '<br/>';

  $output .= '<strong>' . t('Donation') . '</strong> ';
  $output .= ($data->amount_donation)
    ? '&euro; ' . number_format($data->amount_donation, 2, ',', '.') . '<br/>'
    : '- <br/>';

  $output .= '<br/>';

  $paymentMethod = (((int) $data->payment_method === PayWayMessage::ORDER_OGONE_PAYMENT)
    ? t('Online payment')
    : t('By bank transfer'));
  $output .= '<strong>' . t('Order id') . '</strong> ' . check_plain($data->order_id) . '<br/>';
  $output .= '<strong>' . t('Payment method') . '</strong> ' . $paymentMethod . '<br/>';
  $output .= '<strong>' . t('Payed') . '</strong> ' .
    iish_donations_get_payment_status((int) $data->order_id, (int) $data->payed, (int) $data->payment_method)
    . '<br/>';

  $output .= '</div>';

  return $output;
}

/**
 * Returns the HTML for displaying the payment status.
 * @param int $orderId The order id.
 * @param int $payed The payment status.
 * @param int $paymentMethod The payment method.
 * @return string The HTML.
 */
function iish_donations_get_payment_status($orderId, $payed, $paymentMethod) {
  $status = '<div class="iish-donations-payment-status">';

  if ($payed === PayWayMessage::ORDER_PAYED) {
    $status .= '<span class="iish-donations-payed">' . t('Payed') . '</span> ';
  }
  else {
    $status .= '<span class="iish-donations-not-payed">' . t('Not payed') . '</span>';

    if ($paymentMethod === PayWayMessage::ORDER_BANK_PAYMENT) {
      $status .= '<button class="iish-donations-confirm-payed" data-order-id="' . $orderId . '"">'
        . t('Confirm payed') . '</button>';
    }
  }

  $status .= '</div>';
  return $status;
}