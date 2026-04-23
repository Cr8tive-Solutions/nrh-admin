<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $countries = [
            // ── Africa ────────────────────────────────────────────────────────
            ['name' => 'Algeria',                         'code' => 'DZA', 'flag' => '🇩🇿', 'region' => 'Africa'],
            ['name' => 'Angola',                          'code' => 'AGO', 'flag' => '🇦🇴', 'region' => 'Africa'],
            ['name' => 'Benin',                           'code' => 'BEN', 'flag' => '🇧🇯', 'region' => 'Africa'],
            ['name' => 'Botswana',                        'code' => 'BWA', 'flag' => '🇧🇼', 'region' => 'Africa'],
            ['name' => 'Burkina Faso',                    'code' => 'BFA', 'flag' => '🇧🇫', 'region' => 'Africa'],
            ['name' => 'Burundi',                         'code' => 'BDI', 'flag' => '🇧🇮', 'region' => 'Africa'],
            ['name' => 'Cabo Verde',                      'code' => 'CPV', 'flag' => '🇨🇻', 'region' => 'Africa'],
            ['name' => 'Cameroon',                        'code' => 'CMR', 'flag' => '🇨🇲', 'region' => 'Africa'],
            ['name' => 'Central African Republic',        'code' => 'CAF', 'flag' => '🇨🇫', 'region' => 'Africa'],
            ['name' => 'Chad',                            'code' => 'TCD', 'flag' => '🇹🇩', 'region' => 'Africa'],
            ['name' => 'Comoros',                         'code' => 'COM', 'flag' => '🇰🇲', 'region' => 'Africa'],
            ['name' => 'DR Congo',                        'code' => 'COD', 'flag' => '🇨🇩', 'region' => 'Africa'],
            ['name' => 'Republic of Congo',               'code' => 'COG', 'flag' => '🇨🇬', 'region' => 'Africa'],
            ['name' => 'Djibouti',                        'code' => 'DJI', 'flag' => '🇩🇯', 'region' => 'Africa'],
            ['name' => 'Egypt',                           'code' => 'EGY', 'flag' => '🇪🇬', 'region' => 'Africa'],
            ['name' => 'Equatorial Guinea',               'code' => 'GNQ', 'flag' => '🇬🇶', 'region' => 'Africa'],
            ['name' => 'Eritrea',                         'code' => 'ERI', 'flag' => '🇪🇷', 'region' => 'Africa'],
            ['name' => 'Eswatini',                        'code' => 'SWZ', 'flag' => '🇸🇿', 'region' => 'Africa'],
            ['name' => 'Ethiopia',                        'code' => 'ETH', 'flag' => '🇪🇹', 'region' => 'Africa'],
            ['name' => 'Gabon',                           'code' => 'GAB', 'flag' => '🇬🇦', 'region' => 'Africa'],
            ['name' => 'Gambia',                          'code' => 'GMB', 'flag' => '🇬🇲', 'region' => 'Africa'],
            ['name' => 'Ghana',                           'code' => 'GHA', 'flag' => '🇬🇭', 'region' => 'Africa'],
            ['name' => 'Guinea',                          'code' => 'GIN', 'flag' => '🇬🇳', 'region' => 'Africa'],
            ['name' => 'Guinea-Bissau',                   'code' => 'GNB', 'flag' => '🇬🇼', 'region' => 'Africa'],
            ['name' => 'Ivory Coast',                     'code' => 'CIV', 'flag' => '🇨🇮', 'region' => 'Africa'],
            ['name' => 'Kenya',                           'code' => 'KEN', 'flag' => '🇰🇪', 'region' => 'Africa'],
            ['name' => 'Lesotho',                         'code' => 'LSO', 'flag' => '🇱🇸', 'region' => 'Africa'],
            ['name' => 'Liberia',                         'code' => 'LBR', 'flag' => '🇱🇷', 'region' => 'Africa'],
            ['name' => 'Libya',                           'code' => 'LBY', 'flag' => '🇱🇾', 'region' => 'Africa'],
            ['name' => 'Madagascar',                      'code' => 'MDG', 'flag' => '🇲🇬', 'region' => 'Africa'],
            ['name' => 'Malawi',                          'code' => 'MWI', 'flag' => '🇲🇼', 'region' => 'Africa'],
            ['name' => 'Mali',                            'code' => 'MLI', 'flag' => '🇲🇱', 'region' => 'Africa'],
            ['name' => 'Mauritania',                      'code' => 'MRT', 'flag' => '🇲🇷', 'region' => 'Africa'],
            ['name' => 'Mauritius',                       'code' => 'MUS', 'flag' => '🇲🇺', 'region' => 'Africa'],
            ['name' => 'Morocco',                         'code' => 'MAR', 'flag' => '🇲🇦', 'region' => 'Africa'],
            ['name' => 'Mozambique',                      'code' => 'MOZ', 'flag' => '🇲🇿', 'region' => 'Africa'],
            ['name' => 'Namibia',                         'code' => 'NAM', 'flag' => '🇳🇦', 'region' => 'Africa'],
            ['name' => 'Niger',                           'code' => 'NER', 'flag' => '🇳🇪', 'region' => 'Africa'],
            ['name' => 'Nigeria',                         'code' => 'NGA', 'flag' => '🇳🇬', 'region' => 'Africa'],
            ['name' => 'Rwanda',                          'code' => 'RWA', 'flag' => '🇷🇼', 'region' => 'Africa'],
            ['name' => 'São Tomé and Príncipe',           'code' => 'STP', 'flag' => '🇸🇹', 'region' => 'Africa'],
            ['name' => 'Senegal',                         'code' => 'SEN', 'flag' => '🇸🇳', 'region' => 'Africa'],
            ['name' => 'Seychelles',                      'code' => 'SYC', 'flag' => '🇸🇨', 'region' => 'Africa'],
            ['name' => 'Sierra Leone',                    'code' => 'SLE', 'flag' => '🇸🇱', 'region' => 'Africa'],
            ['name' => 'Somalia',                         'code' => 'SOM', 'flag' => '🇸🇴', 'region' => 'Africa'],
            ['name' => 'South Africa',                    'code' => 'ZAF', 'flag' => '🇿🇦', 'region' => 'Africa'],
            ['name' => 'South Sudan',                     'code' => 'SSD', 'flag' => '🇸🇸', 'region' => 'Africa'],
            ['name' => 'Sudan',                           'code' => 'SDN', 'flag' => '🇸🇩', 'region' => 'Africa'],
            ['name' => 'Tanzania',                        'code' => 'TZA', 'flag' => '🇹🇿', 'region' => 'Africa'],
            ['name' => 'Togo',                            'code' => 'TGO', 'flag' => '🇹🇬', 'region' => 'Africa'],
            ['name' => 'Tunisia',                         'code' => 'TUN', 'flag' => '🇹🇳', 'region' => 'Africa'],
            ['name' => 'Uganda',                          'code' => 'UGA', 'flag' => '🇺🇬', 'region' => 'Africa'],
            ['name' => 'Zambia',                          'code' => 'ZMB', 'flag' => '🇿🇲', 'region' => 'Africa'],
            ['name' => 'Zimbabwe',                        'code' => 'ZWE', 'flag' => '🇿🇼', 'region' => 'Africa'],

            // ── Americas ───────────────────────────────────────────────────────
            ['name' => 'Antigua and Barbuda',             'code' => 'ATG', 'flag' => '🇦🇬', 'region' => 'Americas'],
            ['name' => 'Argentina',                       'code' => 'ARG', 'flag' => '🇦🇷', 'region' => 'Americas'],
            ['name' => 'Bahamas',                         'code' => 'BHS', 'flag' => '🇧🇸', 'region' => 'Americas'],
            ['name' => 'Barbados',                        'code' => 'BRB', 'flag' => '🇧🇧', 'region' => 'Americas'],
            ['name' => 'Belize',                          'code' => 'BLZ', 'flag' => '🇧🇿', 'region' => 'Americas'],
            ['name' => 'Bolivia',                         'code' => 'BOL', 'flag' => '🇧🇴', 'region' => 'Americas'],
            ['name' => 'Brazil',                          'code' => 'BRA', 'flag' => '🇧🇷', 'region' => 'Americas'],
            ['name' => 'Canada',                          'code' => 'CAN', 'flag' => '🇨🇦', 'region' => 'Americas'],
            ['name' => 'Chile',                           'code' => 'CHL', 'flag' => '🇨🇱', 'region' => 'Americas'],
            ['name' => 'Colombia',                        'code' => 'COL', 'flag' => '🇨🇴', 'region' => 'Americas'],
            ['name' => 'Costa Rica',                      'code' => 'CRI', 'flag' => '🇨🇷', 'region' => 'Americas'],
            ['name' => 'Cuba',                            'code' => 'CUB', 'flag' => '🇨🇺', 'region' => 'Americas'],
            ['name' => 'Dominica',                        'code' => 'DMA', 'flag' => '🇩🇲', 'region' => 'Americas'],
            ['name' => 'Dominican Republic',              'code' => 'DOM', 'flag' => '🇩🇴', 'region' => 'Americas'],
            ['name' => 'Ecuador',                         'code' => 'ECU', 'flag' => '🇪🇨', 'region' => 'Americas'],
            ['name' => 'El Salvador',                     'code' => 'SLV', 'flag' => '🇸🇻', 'region' => 'Americas'],
            ['name' => 'Grenada',                         'code' => 'GRD', 'flag' => '🇬🇩', 'region' => 'Americas'],
            ['name' => 'Guatemala',                       'code' => 'GTM', 'flag' => '🇬🇹', 'region' => 'Americas'],
            ['name' => 'Guyana',                          'code' => 'GUY', 'flag' => '🇬🇾', 'region' => 'Americas'],
            ['name' => 'Haiti',                           'code' => 'HTI', 'flag' => '🇭🇹', 'region' => 'Americas'],
            ['name' => 'Honduras',                        'code' => 'HND', 'flag' => '🇭🇳', 'region' => 'Americas'],
            ['name' => 'Jamaica',                         'code' => 'JAM', 'flag' => '🇯🇲', 'region' => 'Americas'],
            ['name' => 'Mexico',                          'code' => 'MEX', 'flag' => '🇲🇽', 'region' => 'Americas'],
            ['name' => 'Nicaragua',                       'code' => 'NIC', 'flag' => '🇳🇮', 'region' => 'Americas'],
            ['name' => 'Panama',                          'code' => 'PAN', 'flag' => '🇵🇦', 'region' => 'Americas'],
            ['name' => 'Paraguay',                        'code' => 'PRY', 'flag' => '🇵🇾', 'region' => 'Americas'],
            ['name' => 'Peru',                            'code' => 'PER', 'flag' => '🇵🇪', 'region' => 'Americas'],
            ['name' => 'Saint Kitts and Nevis',           'code' => 'KNA', 'flag' => '🇰🇳', 'region' => 'Americas'],
            ['name' => 'Saint Lucia',                     'code' => 'LCA', 'flag' => '🇱🇨', 'region' => 'Americas'],
            ['name' => 'Saint Vincent and the Grenadines','code' => 'VCT', 'flag' => '🇻🇨', 'region' => 'Americas'],
            ['name' => 'Suriname',                        'code' => 'SUR', 'flag' => '🇸🇷', 'region' => 'Americas'],
            ['name' => 'Trinidad and Tobago',             'code' => 'TTO', 'flag' => '🇹🇹', 'region' => 'Americas'],
            ['name' => 'United States',                   'code' => 'USA', 'flag' => '🇺🇸', 'region' => 'Americas'],
            ['name' => 'Uruguay',                         'code' => 'URY', 'flag' => '🇺🇾', 'region' => 'Americas'],
            ['name' => 'Venezuela',                       'code' => 'VEN', 'flag' => '🇻🇪', 'region' => 'Americas'],

            // ── Asia ───────────────────────────────────────────────────────────
            ['name' => 'Afghanistan',                     'code' => 'AFG', 'flag' => '🇦🇫', 'region' => 'Asia'],
            ['name' => 'Armenia',                         'code' => 'ARM', 'flag' => '🇦🇲', 'region' => 'Asia'],
            ['name' => 'Azerbaijan',                      'code' => 'AZE', 'flag' => '🇦🇿', 'region' => 'Asia'],
            ['name' => 'Bahrain',                         'code' => 'BHR', 'flag' => '🇧🇭', 'region' => 'Asia'],
            ['name' => 'Bangladesh',                      'code' => 'BGD', 'flag' => '🇧🇩', 'region' => 'Asia'],
            ['name' => 'Bhutan',                          'code' => 'BTN', 'flag' => '🇧🇹', 'region' => 'Asia'],
            ['name' => 'Brunei',                          'code' => 'BRN', 'flag' => '🇧🇳', 'region' => 'Asia'],
            ['name' => 'Cambodia',                        'code' => 'KHM', 'flag' => '🇰🇭', 'region' => 'Asia'],
            ['name' => 'China',                           'code' => 'CHN', 'flag' => '🇨🇳', 'region' => 'Asia'],
            ['name' => 'Cyprus',                          'code' => 'CYP', 'flag' => '🇨🇾', 'region' => 'Asia'],
            ['name' => 'Georgia',                         'code' => 'GEO', 'flag' => '🇬🇪', 'region' => 'Asia'],
            ['name' => 'Hong Kong',                       'code' => 'HKG', 'flag' => '🇭🇰', 'region' => 'Asia'],
            ['name' => 'India',                           'code' => 'IND', 'flag' => '🇮🇳', 'region' => 'Asia'],
            ['name' => 'Indonesia',                       'code' => 'IDN', 'flag' => '🇮🇩', 'region' => 'Asia'],
            ['name' => 'Iran',                            'code' => 'IRN', 'flag' => '🇮🇷', 'region' => 'Asia'],
            ['name' => 'Iraq',                            'code' => 'IRQ', 'flag' => '🇮🇶', 'region' => 'Asia'],
            ['name' => 'Israel',                          'code' => 'ISR', 'flag' => '🇮🇱', 'region' => 'Asia'],
            ['name' => 'Japan',                           'code' => 'JPN', 'flag' => '🇯🇵', 'region' => 'Asia'],
            ['name' => 'Jordan',                          'code' => 'JOR', 'flag' => '🇯🇴', 'region' => 'Asia'],
            ['name' => 'Kazakhstan',                      'code' => 'KAZ', 'flag' => '🇰🇿', 'region' => 'Asia'],
            ['name' => 'Kuwait',                          'code' => 'KWT', 'flag' => '🇰🇼', 'region' => 'Asia'],
            ['name' => 'Kyrgyzstan',                      'code' => 'KGZ', 'flag' => '🇰🇬', 'region' => 'Asia'],
            ['name' => 'Laos',                            'code' => 'LAO', 'flag' => '🇱🇦', 'region' => 'Asia'],
            ['name' => 'Lebanon',                         'code' => 'LBN', 'flag' => '🇱🇧', 'region' => 'Asia'],
            ['name' => 'Macau',                           'code' => 'MAC', 'flag' => '🇲🇴', 'region' => 'Asia'],
            ['name' => 'Malaysia',                        'code' => 'MYS', 'flag' => '🇲🇾', 'region' => 'Asia'],
            ['name' => 'Maldives',                        'code' => 'MDV', 'flag' => '🇲🇻', 'region' => 'Asia'],
            ['name' => 'Mongolia',                        'code' => 'MNG', 'flag' => '🇲🇳', 'region' => 'Asia'],
            ['name' => 'Myanmar',                         'code' => 'MMR', 'flag' => '🇲🇲', 'region' => 'Asia'],
            ['name' => 'Nepal',                           'code' => 'NPL', 'flag' => '🇳🇵', 'region' => 'Asia'],
            ['name' => 'North Korea',                     'code' => 'PRK', 'flag' => '🇰🇵', 'region' => 'Asia'],
            ['name' => 'Oman',                            'code' => 'OMN', 'flag' => '🇴🇲', 'region' => 'Asia'],
            ['name' => 'Pakistan',                        'code' => 'PAK', 'flag' => '🇵🇰', 'region' => 'Asia'],
            ['name' => 'Palestine',                       'code' => 'PSE', 'flag' => '🇵🇸', 'region' => 'Asia'],
            ['name' => 'Philippines',                     'code' => 'PHL', 'flag' => '🇵🇭', 'region' => 'Asia'],
            ['name' => 'Qatar',                           'code' => 'QAT', 'flag' => '🇶🇦', 'region' => 'Asia'],
            ['name' => 'Saudi Arabia',                    'code' => 'SAU', 'flag' => '🇸🇦', 'region' => 'Asia'],
            ['name' => 'Singapore',                       'code' => 'SGP', 'flag' => '🇸🇬', 'region' => 'Asia'],
            ['name' => 'South Korea',                     'code' => 'KOR', 'flag' => '🇰🇷', 'region' => 'Asia'],
            ['name' => 'Sri Lanka',                       'code' => 'LKA', 'flag' => '🇱🇰', 'region' => 'Asia'],
            ['name' => 'Syria',                           'code' => 'SYR', 'flag' => '🇸🇾', 'region' => 'Asia'],
            ['name' => 'Taiwan',                          'code' => 'TWN', 'flag' => '🇹🇼', 'region' => 'Asia'],
            ['name' => 'Tajikistan',                      'code' => 'TJK', 'flag' => '🇹🇯', 'region' => 'Asia'],
            ['name' => 'Thailand',                        'code' => 'THA', 'flag' => '🇹🇭', 'region' => 'Asia'],
            ['name' => 'Timor-Leste',                     'code' => 'TLS', 'flag' => '🇹🇱', 'region' => 'Asia'],
            ['name' => 'Turkey',                          'code' => 'TUR', 'flag' => '🇹🇷', 'region' => 'Asia'],
            ['name' => 'Turkmenistan',                    'code' => 'TKM', 'flag' => '🇹🇲', 'region' => 'Asia'],
            ['name' => 'United Arab Emirates',            'code' => 'ARE', 'flag' => '🇦🇪', 'region' => 'Asia'],
            ['name' => 'Uzbekistan',                      'code' => 'UZB', 'flag' => '🇺🇿', 'region' => 'Asia'],
            ['name' => 'Vietnam',                         'code' => 'VNM', 'flag' => '🇻🇳', 'region' => 'Asia'],
            ['name' => 'Yemen',                           'code' => 'YEM', 'flag' => '🇾🇪', 'region' => 'Asia'],

            // ── Europe ─────────────────────────────────────────────────────────
            ['name' => 'Albania',                         'code' => 'ALB', 'flag' => '🇦🇱', 'region' => 'Europe'],
            ['name' => 'Andorra',                         'code' => 'AND', 'flag' => '🇦🇩', 'region' => 'Europe'],
            ['name' => 'Austria',                         'code' => 'AUT', 'flag' => '🇦🇹', 'region' => 'Europe'],
            ['name' => 'Belarus',                         'code' => 'BLR', 'flag' => '🇧🇾', 'region' => 'Europe'],
            ['name' => 'Belgium',                         'code' => 'BEL', 'flag' => '🇧🇪', 'region' => 'Europe'],
            ['name' => 'Bosnia and Herzegovina',          'code' => 'BIH', 'flag' => '🇧🇦', 'region' => 'Europe'],
            ['name' => 'Bulgaria',                        'code' => 'BGR', 'flag' => '🇧🇬', 'region' => 'Europe'],
            ['name' => 'Croatia',                         'code' => 'HRV', 'flag' => '🇭🇷', 'region' => 'Europe'],
            ['name' => 'Czech Republic',                  'code' => 'CZE', 'flag' => '🇨🇿', 'region' => 'Europe'],
            ['name' => 'Denmark',                         'code' => 'DNK', 'flag' => '🇩🇰', 'region' => 'Europe'],
            ['name' => 'Estonia',                         'code' => 'EST', 'flag' => '🇪🇪', 'region' => 'Europe'],
            ['name' => 'Finland',                         'code' => 'FIN', 'flag' => '🇫🇮', 'region' => 'Europe'],
            ['name' => 'France',                          'code' => 'FRA', 'flag' => '🇫🇷', 'region' => 'Europe'],
            ['name' => 'Germany',                         'code' => 'DEU', 'flag' => '🇩🇪', 'region' => 'Europe'],
            ['name' => 'Greece',                          'code' => 'GRC', 'flag' => '🇬🇷', 'region' => 'Europe'],
            ['name' => 'Hungary',                         'code' => 'HUN', 'flag' => '🇭🇺', 'region' => 'Europe'],
            ['name' => 'Iceland',                         'code' => 'ISL', 'flag' => '🇮🇸', 'region' => 'Europe'],
            ['name' => 'Ireland',                         'code' => 'IRL', 'flag' => '🇮🇪', 'region' => 'Europe'],
            ['name' => 'Italy',                           'code' => 'ITA', 'flag' => '🇮🇹', 'region' => 'Europe'],
            ['name' => 'Kosovo',                          'code' => 'XKX', 'flag' => '🇽🇰', 'region' => 'Europe'],
            ['name' => 'Latvia',                          'code' => 'LVA', 'flag' => '🇱🇻', 'region' => 'Europe'],
            ['name' => 'Liechtenstein',                   'code' => 'LIE', 'flag' => '🇱🇮', 'region' => 'Europe'],
            ['name' => 'Lithuania',                       'code' => 'LTU', 'flag' => '🇱🇹', 'region' => 'Europe'],
            ['name' => 'Luxembourg',                      'code' => 'LUX', 'flag' => '🇱🇺', 'region' => 'Europe'],
            ['name' => 'Malta',                           'code' => 'MLT', 'flag' => '🇲🇹', 'region' => 'Europe'],
            ['name' => 'Moldova',                         'code' => 'MDA', 'flag' => '🇲🇩', 'region' => 'Europe'],
            ['name' => 'Monaco',                          'code' => 'MCO', 'flag' => '🇲🇨', 'region' => 'Europe'],
            ['name' => 'Montenegro',                      'code' => 'MNE', 'flag' => '🇲🇪', 'region' => 'Europe'],
            ['name' => 'Netherlands',                     'code' => 'NLD', 'flag' => '🇳🇱', 'region' => 'Europe'],
            ['name' => 'North Macedonia',                 'code' => 'MKD', 'flag' => '🇲🇰', 'region' => 'Europe'],
            ['name' => 'Norway',                          'code' => 'NOR', 'flag' => '🇳🇴', 'region' => 'Europe'],
            ['name' => 'Poland',                          'code' => 'POL', 'flag' => '🇵🇱', 'region' => 'Europe'],
            ['name' => 'Portugal',                        'code' => 'PRT', 'flag' => '🇵🇹', 'region' => 'Europe'],
            ['name' => 'Romania',                         'code' => 'ROU', 'flag' => '🇷🇴', 'region' => 'Europe'],
            ['name' => 'Russia',                          'code' => 'RUS', 'flag' => '🇷🇺', 'region' => 'Europe'],
            ['name' => 'San Marino',                      'code' => 'SMR', 'flag' => '🇸🇲', 'region' => 'Europe'],
            ['name' => 'Serbia',                          'code' => 'SRB', 'flag' => '🇷🇸', 'region' => 'Europe'],
            ['name' => 'Slovakia',                        'code' => 'SVK', 'flag' => '🇸🇰', 'region' => 'Europe'],
            ['name' => 'Slovenia',                        'code' => 'SVN', 'flag' => '🇸🇮', 'region' => 'Europe'],
            ['name' => 'Spain',                           'code' => 'ESP', 'flag' => '🇪🇸', 'region' => 'Europe'],
            ['name' => 'Sweden',                          'code' => 'SWE', 'flag' => '🇸🇪', 'region' => 'Europe'],
            ['name' => 'Switzerland',                     'code' => 'CHE', 'flag' => '🇨🇭', 'region' => 'Europe'],
            ['name' => 'Ukraine',                         'code' => 'UKR', 'flag' => '🇺🇦', 'region' => 'Europe'],
            ['name' => 'United Kingdom',                  'code' => 'GBR', 'flag' => '🇬🇧', 'region' => 'Europe'],
            ['name' => 'Vatican City',                    'code' => 'VAT', 'flag' => '🇻🇦', 'region' => 'Europe'],

            // ── Oceania ────────────────────────────────────────────────────────
            ['name' => 'Australia',                       'code' => 'AUS', 'flag' => '🇦🇺', 'region' => 'Oceania'],
            ['name' => 'Fiji',                            'code' => 'FJI', 'flag' => '🇫🇯', 'region' => 'Oceania'],
            ['name' => 'Kiribati',                        'code' => 'KIR', 'flag' => '🇰🇮', 'region' => 'Oceania'],
            ['name' => 'Marshall Islands',                'code' => 'MHL', 'flag' => '🇲🇭', 'region' => 'Oceania'],
            ['name' => 'Micronesia',                      'code' => 'FSM', 'flag' => '🇫🇲', 'region' => 'Oceania'],
            ['name' => 'Nauru',                           'code' => 'NRU', 'flag' => '🇳🇷', 'region' => 'Oceania'],
            ['name' => 'New Zealand',                     'code' => 'NZL', 'flag' => '🇳🇿', 'region' => 'Oceania'],
            ['name' => 'Palau',                           'code' => 'PLW', 'flag' => '🇵🇼', 'region' => 'Oceania'],
            ['name' => 'Papua New Guinea',                'code' => 'PNG', 'flag' => '🇵🇬', 'region' => 'Oceania'],
            ['name' => 'Samoa',                           'code' => 'WSM', 'flag' => '🇼🇸', 'region' => 'Oceania'],
            ['name' => 'Solomon Islands',                 'code' => 'SLB', 'flag' => '🇸🇧', 'region' => 'Oceania'],
            ['name' => 'Tonga',                           'code' => 'TON', 'flag' => '🇹🇴', 'region' => 'Oceania'],
            ['name' => 'Tuvalu',                          'code' => 'TUV', 'flag' => '🇹🇻', 'region' => 'Oceania'],
            ['name' => 'Vanuatu',                         'code' => 'VUT', 'flag' => '🇻🇺', 'region' => 'Oceania'],
        ];

        // Build canonical code→name map for deduplication
        $canonicalCodes = collect($countries)->pluck('code', 'name');

        // Remove duplicates: for each country name appearing more than once,
        // keep the row with the most scope_types (or lowest id), reassign
        // scope_types references, then delete the extras.
        $duplicateNames = DB::table('countries')
            ->select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('name');

        foreach ($duplicateNames as $name) {
            $rows = DB::table('countries')
                ->leftJoin('scope_types', 'countries.id', '=', 'scope_types.country_id')
                ->select('countries.id', DB::raw('COUNT(scope_types.id) as scope_count'))
                ->where('countries.name', $name)
                ->groupBy('countries.id')
                ->orderByDesc('scope_count')
                ->orderBy('countries.id')
                ->get();

            $keepId    = $rows->first()->id;
            $deleteIds = $rows->skip(1)->pluck('id');

            // Reassign any scope_types pointing to duplicate rows
            DB::table('scope_types')
                ->whereIn('country_id', $deleteIds)
                ->update(['country_id' => $keepId]);

            DB::table('countries')->whereIn('id', $deleteIds)->delete();

            // Update the kept row with the canonical code if we know it
            if ($canonicalCodes->has($name)) {
                DB::table('countries')->where('id', $keepId)->update([
                    'code' => $canonicalCodes[$name],
                ]);
            }
        }

        // Upsert all countries: Malaysia gets MYR, everyone else USD
        $rows = array_map(fn ($c) => array_merge($c, [
            'currency'   => $c['name'] === 'Malaysia' ? 'MYR' : 'USD',
            'created_at' => $now,
            'updated_at' => $now,
        ]), $countries);

        DB::table('countries')->upsert(
            $rows,
            ['code'],
            ['name', 'currency', 'flag', 'region', 'updated_at']
        );
    }
}
