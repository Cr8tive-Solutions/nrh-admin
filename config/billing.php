<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SST rate
    |--------------------------------------------------------------------------
    |
    | 6% Malaysian SST, applied to monthly invoice subtotals AND to
    | per-request cash totals — keep in sync with the client portal's
    | config/billing.php.
    */

    'sst_rate' => 0.06,

    /*
    |--------------------------------------------------------------------------
    | Invoice PDF — bank, issuing company and cheque payee
    |--------------------------------------------------------------------------
    |
    | Printed on the official invoice PDF. Kept in sync with the client
    | portal's config/billing.php so both portals render the same document.
    */
    'bank' => [
        'name' => env('BILLING_BANK_NAME', 'CIMB BANK BERHAD'),
        'account_holder' => env('BILLING_BANK_HOLDER', 'NRH INTELLIGENCE SDN BHD'),
        'account_number' => env('BILLING_BANK_ACCOUNT', '8011600106'),
        'swift' => env('BILLING_BANK_SWIFT', 'CIBBMYKL'),
    ],

    'company' => [
        'name' => env('BILLING_COMPANY_NAME', 'NRH INTELLIGENCE SDN BHD'),
        'reg_no' => env('BILLING_COMPANY_REG', '202101036456 (1436756D)'),
        'tax_no' => env('BILLING_COMPANY_TAX', 'C 26861781030'),
        'address' => env('BILLING_COMPANY_ADDRESS', 'No. B1-1, Jalan Selaman 1, Dataran Palma, 68000 Ampang, Selangor'),
        'phone' => env('BILLING_COMPANY_PHONE', '+60124395006'),
        'email' => env('BILLING_COMPANY_EMAIL', 'nadiaa@nrhintelligence.com'),
    ],

    'cheque_payee' => env('BILLING_CHEQUE_PAYEE', 'NRH INTELLIGENCE SDN BHD'),

    'currency' => env('BILLING_CURRENCY', 'MYR'),

];
