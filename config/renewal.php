<?php

return [
    'general' => [
        'paginate' => 30,
        'mail_recipient' => 'client',
        'vat_column' => 1,
        'receipt_tabs' => 1,
    ],

    'validity' => [
        'instruct_before' => 30,
        'fee_factor' => 1.2,
        'before' => 8,
        'before_last' => 2,
    ],

    'invoice' => [
        'backend' => env('RENEWAL_INVOICE_BACKEND', 'none'),
        'vat_rate' => (float) env('RENEWAL_VAT_RATE', 0.2),
        'foreign_vat_rate' => (float) env('RENEWAL_FOREIGN_VAT_RATE', 0.0),
        'default_fee' => (float) env('RENEWAL_DEFAULT_FEE', 145),
        'captions' => [
            'task.id',
            'task.detail',
            'task.due_date',
            'task.done',
            'task.done_date',
            'matter_id',
            'cost',
            'fee',
            'cost_reduced',
            'fee_reduced',
            'cost_sup',
            'fee_sup',
            'cost_sup_reduced',
            'fee_sup_reduced',
            'task.trigger_id',
            'category',
            'matter.caseref',
            'matter.uid',
            'matter.country',
            'country_FR',
            'country_EN',
            'country_DE',
            'matter.origin',
            'small_entity',
            'fil_date',
            'fil_num',
            'grt_date',
            'event_name',
            'event_date',
            'number',
            'applicant_name',
            'client_name',
            'client_address',
            'client_country',
            'ren_discount',
            'client_id',
            'client_ref',
            'client_email',
            'client_language',
            'responsible',
            'short_title',
            'title',
            'pub_num',
            'task.step',
            'task.grace_period',
            'task.invoice_step',
            'expire_date',
            'table_fee',
        ],
    ],

    'api' => [
        // Keep these historical keys for existing installations and services.
        'dolibarr_url' => rtrim((string) env('DOLIBARR_URL', ''), '/'),
        'DOLAPIKEY' => env('DOLIBARR_API_KEY'),
        'fk_account' => (int) env('DOLIBARR_ACCOUNT_ID', 2),
        'fk_cond_reglement' => (int) env('DOLIBARR_PAYMENT_TERMS_ID', 1),
        'fk_mode_reglement' => (int) env('DOLIBARR_PAYMENT_METHOD_ID', 2),
        'connect_timeout' => (int) env('DOLIBARR_CONNECT_TIMEOUT', 5),
        'timeout' => (int) env('DOLIBARR_TIMEOUT', 15),
        'retry_times' => (int) env('DOLIBARR_RETRY_TIMES', 2),
        'retry_sleep_ms' => (int) env('DOLIBARR_RETRY_SLEEP_MS', 100),
        'lock_seconds' => (int) env('DOLIBARR_LOCK_SECONDS', 60),
        'lock_wait_seconds' => (int) env('DOLIBARR_LOCK_WAIT_SECONDS', 20),
    ],

    'description' => [
        'fr' => [
            'line1' => "%s : Annuité pour l'année %s du titre %s",
            'filed' => ' déposé le ',
            'granted' => ' délivré le ',
            'country_prefix' => ' en ',
            'subject' => 'Sujet : %s',
            'client_reference' => 'Votre référence : %s',
            'due_date' => 'Échéance le %s',
            'fees' => 'Honoraires pour la surveillance et le paiement',
            'fees_and_tax' => 'Honoraires et taxe',
            'tax' => 'Taxe',
        ],
        'en' => [
            'line1' => '%s: Renewal fee for year %s of title %s',
            'filed' => ' filed on ',
            'granted' => ' granted on ',
            'country_prefix' => ' in ',
            'subject' => 'Subject: %s',
            'client_reference' => 'Your reference: %s',
            'due_date' => 'Due on %s',
            'fees' => 'Fees for monitoring and payment',
            'fees_and_tax' => 'Fees and tax',
            'tax' => 'Tax',
        ],
        'de' => [
            'line1' => '%s: Jahresgebühr für das Jahr %s des Titels %s',
            'filed' => ' angemeldet am ',
            'granted' => ' erteilt am ',
            'country_prefix' => ' in ',
            'subject' => 'Betreff: %s',
            'client_reference' => 'Ihr Zeichen: %s',
            'due_date' => 'Fällig am %s',
            'fees' => 'Honorar für Überwachung und Zahlung',
            'fees_and_tax' => 'Honorar und Amtsgebühr',
            'tax' => 'Amtsgebühr',
        ],
    ],

    'xml' => [
        'body' => '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE batch-payment SYSTEM "batch-payment-v2-00.dtd" >
<batch-payment dtd-version="" date-produced="" ro="">
  <header>
    <sender>
      <name>' . env('COMPANY_NAME', 'NAME') . '</name>
      <registered-number></registered-number>
    </sender>
    <send-date></send-date>
    <mode-of-payment payment-type="deposit">
      <deposit-account>
        <account-no>DEPOSIT</account-no>
      </deposit-account>
    </mode-of-payment>
    <payment-reference-id>TRANSACTION</payment-reference-id>
  </header>
  <detail>
  </detail>
  <trailer>
    <mode-of-payment payment-type="deposit">
      <deposit-account>
        <account-no>DEPOSIT</account-no>
      </deposit-account>
    </mode-of-payment>
    <batch-pay-total-amount currency="EUR">TOTAL</batch-pay-total-amount>
    <total-records>COUNT</total-records>
  </trailer>
</batch-payment>',
        'EP_deposit' => 'Your EPO account',
        'FR_deposit' => 'Your INPI account',
    ],
];
