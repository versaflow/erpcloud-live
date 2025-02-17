<?php

/*
public function paygate_checksum(Request $request) {
        $paygate_key = 'secret';
        $paygate_form = $request->all();

        unset($paygate_form['CHECKSUM']);

        $paygate_form_values = array_values($paygate_form);


        $paygate_form_values[] = $paygate_key;

        $checksum_str = implode('|',$paygate_form_values);

        $md5 = md5($checksum_str);

        if($request->ajax() == true)
        echo json_encode(['checksum' => $md5]);
        else
        return $md5;
    }

public function paygate_debit_order_response(Request $request) {


    //	Recurring payments set to begin processing on 28th
    //	http://cloudtools.versaflow.io/paygate_docs/PayGate%20PaySubs%20Web%20Interface%20v2.2.pdf
    //	for transacton_status and result_code reference
    //	transacton_status: 0 Not Done 1 Approved 2 Declined 5 Received by PayGate



    //	paygate id - 1024959100012
    //	testing id - 10011072130
    //	test card number - 5200000000000007


        $paygate = $request->all();
        $checksum = $request->CHECKSUM;
        $valid_checksum = $this->paygate_checksum($request);

        $response = 'Checksum Failed';
        $status = 'error';
        if($checksum == $valid_checksum){

            $data = array_change_key_case($paygate,CASE_LOWER);
            $data['account_id'] = explode('_',$data['reference'])[0];
            $data['amount'] = currency($data['amount']/100);
            $data['created_at'] = date('Y-m-d H:i:s');

            dbinsert('acc_debit_orders', $data);
            if($data['transaction_status'] == 1 || $data['transaction_status'] == 5)
            $status = 'success';
            $response = $data['result_desc'];
        }
        return notify_message($response,$status,'payments');
    }
*/

function check_pg_trx()
{
    // Make query to paygate to check for transaction
    //encryption key set in the Merchant Access Portal

    $encryptionKey = 'secret';
    unset($data);

    $data = [
        'PAYGATE_ID' => 10011072130,
        'PAY_REQUEST_ID' => '23B785AE-C96C-32AF-4879-D2C9363DB6E8',
        'REFERENCE' => 'pgtest_123456789',
    ];

    //	$data['PAYGATE_ID'] = 10011013800;

    $checksum = md5(implode('', $data).$encryptionKey);
    $data['CHECKSUM'] = $checksum;
    $fieldsString = http_build_query($data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://secure.paygate.co.za/payweb3/query.trans');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, false);
    curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsString);

    $result = curl_exec($ch);

    curl_close($ch);
}

function paygate_get_transactions()
{
    $pg = new PayGate_PayWeb3;
    $encryptionKey = 'secret';

    $data = [
        'PAYGATE_ID' => 1024959100012,
        'PAY_REQUEST_ID' => '23B785AE-C96C-32AF-4879-D2C9363DB6E8',
        'REFERENCE' => 'pgtest_123456789',
    ];

    $pg->setEncryptionKey($encryptionKey);
    $pg->setQueryRequest($data);
    $r = $pg->validateChecksum($data);
    $pg->setDebug(true);

    $result = $pg->doQuery();
}

if (! class_exists('CountryCodes')) {
    class CountryCodes
    {
        public static $mostUsedCountryArray = [
            'DEU' => 'Germany',
            'ZAF' => 'South Africa',
            'USA' => 'United States',
        ];

        public static $countryArray = [
            'ARG' => 'Argentina',
            'BRA' => 'Brazil',
            'CHL' => 'Chile',
            'KEN' => 'Kenya',
            'MEX' => 'Mexico',
            'GBR' => 'United Kingdom',
            'USA' => 'United States',
            'ZAF' => 'South Africa',
            'AFG' => 'Afghanistan',
            'ALB' => 'Albania',
            'DZA' => 'Algeria',
            'ASM' => 'American Samoa',
            'AND' => 'Andorra',
            'AGO' => 'Angola',
            'AIA' => 'Anguilla',
            'ATA' => 'Antarctica',
            'ATG' => 'Antigua and Barbuda',
            'ARG' => 'Argentina',
            'ARM' => 'Armenia',
            'ABW' => 'Aruba',
            'AUS' => 'Australia',
            'AUT' => 'Austria',
            'AZE' => 'Azerbaijan',
            'BHS' => 'Bahamas',
            'BHR' => 'Bahrain',
            'BGD' => 'Bangladesh',
            'BRB' => 'Barbados',
            'BLR' => 'Belarus',
            'BEL' => 'Belgium',
            'BLZ' => 'Belize',
            'BEN' => 'Benin',
            'BMU' => 'Bermuda',
            'BTN' => 'Bhutan',
            'BOL' => 'Bolivia',
            'BIH' => 'Bosnia and Herzegovina',
            'BWA' => 'Botswana',
            'BVT' => 'Bouvet Island',
            'BRA' => 'Brazil',
            'IOT' => 'British Indian Ocean Territory',
            'VGB' => 'British Virgin Islands',
            'BRN' => 'Brunei Darussalam',
            'BGR' => 'Bulgaria',
            'BFA' => 'Burkina Faso',
            'BDI' => 'Burundi',
            'KHM' => 'Cambodia',
            'CMR' => 'Cameroon',
            'CAN' => 'Canada',
            'CPV' => 'Cape Verde',
            'CYM' => 'Cayman Islands',
            'CAF' => 'Central African Republic',
            'TCD' => 'Chad',
            'CHL' => 'Chile',
            'CHN' => 'China',
            'CXR' => 'Christmas Island',
            'CCK' => 'Cocos (Keeling) Islands',
            'COL' => 'Colombia',
            'COL' => 'Comoros',
            'COG' => 'Congo',
            'COD' => 'Congo, The Democratic Republic of The',
            'COK' => 'Cook Islands',
            'CRI' => 'Costa Rica',
            'CIV' => 'Cote D\'ivoire',
            'CHRV' => 'Croatia',
            'CUB' => 'Cuba',
            'CYP' => 'Cyprus',
            'CZE' => 'Czech Republic',
            'DNK' => 'Denmark',
            'DJI' => 'Djibouti',
            'DMA' => 'Dominica',
            'DOM' => 'Dominican Republic',
            'ECU' => 'Ecuador',
            'EGY' => 'Egypt',
            'SLV' => 'El Salvador',
            'GNQ' => 'Equatorial Guinea',
            'ERI' => 'Eritrea',
            'EST' => 'Estonia',
            'ETH' => 'Ethiopia',
            'FLK' => 'Falkland Islands (Malvinas)',
            'FRO' => 'Faroe Islands',
            'FJI' => 'Fiji',
            'FIN' => 'Finland',
            'FRA' => 'France',
            'FXX' => 'French Metropolitan',
            'GUF' => 'French Guiana',
            'PYF' => 'French Polynesia',
            'ATF' => 'French Southern Territories',
            'GAB' => 'Gabon',
            'GMB' => 'Gambia',
            'GEO' => 'Georgia',
            'DEU' => 'Germany',
            'GHA' => 'Ghana',
            'GIB' => 'Gibraltar',
            'GRC' => 'Greece',
            'GRL' => 'Greenland',
            'GRD' => 'Grenada',
            'GLP' => 'Guadeloupe',
            'GUM' => 'Guam',
            'GTM' => 'Guatemala',
            'GIN' => 'Guinea',
            'GNB' => 'Guinea-bissau',
            'GUY' => 'Guyana',
            'HTI' => 'Haiti',
            'HMD' => 'Heard Island and Mcdonald Islands',
            'VAT' => 'Holy See (Vatican City State)',
            'HND' => 'Honduras',
            'HKG' => 'Hong Kong',
            'HUN' => 'Hungary',
            'ISL' => 'Iceland',
            'IND' => 'India',
            'IDN' => 'Indonesia',
            'IRN' => 'Iran, Islamic Republic of',
            'IRQ' => 'Iraq',
            'IRL' => 'Ireland',
            'ISR' => 'Israel',
            'ITA' => 'Italy',
            'JAM' => 'Jamaica',
            'JPN' => 'Japan',
            'JOR' => 'Jordan',
            'KAZ' => 'Kazakhstan',
            'KEN' => 'Kenya',
            'KIR' => 'Kiribati',
            'PRK' => 'Korea, Democratic People\'s Republic of',
            'KOR' => 'Korea, Republic of',
            'KWT' => 'Kuwait',
            'KGZ' => 'Kyrgyzstan',
            'LAO' => 'Lao People\'s Democratic Republic',
            'LVA' => 'Latvia',
            'LBN' => 'Lebanon',
            'LSO' => 'Lesotho',
            'LBR' => 'Liberia',
            'LBY' => 'Libyan Arab Jamahiriya',
            'LIE' => 'Liechtenstein',
            'LTU' => 'Lithuania',
            'LUX' => 'Luxembourg',
            'MAC' => 'Macau China',
            'MKD' => 'Macedonia, The Former Yugoslav Republic of',
            'MDG' => 'Madagascar',
            'MWI' => 'Malawi',
            'MYS' => 'Malaysia',
            'MDV' => 'Maldives',
            'MLI' => 'Mali',
            'MLT' => 'Malta',
            'MHL' => 'Marshall Islands',
            'MTQ' => 'Martinique',
            'MRT' => 'Mauritania',
            'MUS' => 'Mauritius',
            'MYT' => 'Mayotte',
            'MEX' => 'Mexico',
            'FSM' => 'Micronesia, Federated States of',
            'MDA' => 'Moldova, Republic of',
            'MCO' => 'Monaco',
            'MNG' => 'Mongolia',
            'MSR' => 'Montserrat',
            'MAR' => 'Morocco',
            'MOZ' => 'Mozambique',
            'MMR' => 'Myanmar',
            'NAM' => 'Namibia',
            'NRU' => 'Nauru',
            'NPL' => 'Nepal',
            'NLD' => 'Netherlands',
            'ANT' => 'Netherlands Antilles',
            'NCL' => 'New Caledonia',
            'NZL' => 'New Zealand',
            'NIC' => 'Nicaragua',
            'NER' => 'Niger',
            'NGA' => 'Nigeria',
            'NIU' => 'Niue',
            'NFK' => 'Norfolk Island',
            'MNP' => 'Northern Mariana Islands',
            'NOR' => 'Norway',
            'OMN' => 'Oman',
            'PAK' => 'Pakistan',
            'PLW' => 'Palau',
            'PAN' => 'Panama',
            'PNG' => 'Papua New Guinea',
            'PRY' => 'Paraguay',
            'PER' => 'Peru',
            'PHL' => 'Philippines',
            'PCN' => 'Pitcairn',
            'POL' => 'Poland',
            'PRT' => 'Portugal',
            'PRI' => 'Puerto Rico',
            'QAT' => 'Qatar',
            'REU' => 'Reunion',
            'ROM' => 'Romania',
            'RUS' => 'Russian Federation',
            'RWA' => 'Rwanda',
            'SHN' => 'Saint Helena',
            'KNA' => 'Saint Kitts and Nevis',
            'LCA' => 'Saint Lucia',
            'SPM' => 'Saint Pierre and Miquelon',
            'VCT' => 'Saint Vincent and The Grenadines',
            'WSM' => 'Samoa',
            'SMR' => 'San Marino',
            'STP' => 'Sao Tome and Principe',
            'SAU' => 'Saudi Arabia',
            'SEN' => 'Senegal',
            'SYC' => 'Seychelles',
            'SLE' => 'Sierra Leone',
            'SGP' => 'Singapore',
            'SVK' => 'Slovakia',
            'SVN' => 'Slovenia',
            'SLB' => 'Solomon Islands',
            'SOM' => 'Somalia',
            'ZAF' => 'South Africa',
            'SGS' => 'South Georgia and The South Sandwich Islands',
            'ESP' => 'Spain',
            'LKA' => 'Sri Lanka',
            'SDN' => 'Sudan',
            'SUR' => 'Suriname',
            'SJM' => 'Svalbard and Jan Mayen',
            'SWZ' => 'Swaziland',
            'SWE' => 'Sweden',
            'CHE' => 'Switzerland',
            'SYR' => 'Syrian Arab Republic',
            'TWN' => 'Taiwan, Province of China',
            'TJK' => 'Tajikistan',
            'TZA' => 'Tanzania, United Republic of',
            'THA' => 'Thailand',
            'TGO' => 'Togo',
            'TKL' => 'Tokelau',
            'TON' => 'Tonga',
            'TTO' => 'Trinidad and Tobago',
            'TUN' => 'Tunisia',
            'TUR' => 'Turkey',
            'TKM' => 'Turkmenistan',
            'TCA' => 'Turks and Caicos Islands',
            'TUV' => 'Tuvalu',
            'UGA' => 'Uganda',
            'UKR' => 'Ukraine',
            'ARE' => 'United Arab Emirates',
            'GBR' => 'United Kingdom',
            'USA' => 'United States',
            'UMI' => 'United States Minor Outlying Islands',
            'VIR' => 'U.S. Virgin Islands',
            'URY' => 'Uruguay',
            'UZB' => 'Uzbekistan',
            'VUT' => 'Vanuatu',
            'VEN' => 'Venezuela',
            'VNM' => 'Vietnam',
            'WLF' => 'Wallis and Futuna',
            'ESH' => 'Western Sahara',
            'YEM' => 'Yemen',
            'YUG' => 'Yugoslavia',
            'ZMB' => 'Zambia',
            'ZWE' => 'Zimbabwe',
        ];

        public static $countryArrayShort = [
            'AF' => 'Afghanistan',
            'AX' => 'Aland Islands',
            'AL' => 'Albania',
            'DZ' => 'Algeria',
            'AS' => 'American Samoa',
            'AD' => 'Andorra',
            'AO' => 'Angola',
            'AI' => 'Anguilla',
            'AQ' => 'Antarctica',
            'AG' => 'Antigua And Barbuda',
            'AR' => 'Argentina',
            'AM' => 'Armenia',
            'AW' => 'Aruba',
            'AU' => 'Australia',
            'AT' => 'Austria',
            'AZ' => 'Azerbaijan',
            'BS' => 'Bahamas',
            'BH' => 'Bahrain',
            'BD' => 'Bangladesh',
            'BB' => 'Barbados',
            'BY' => 'Belarus',
            'BE' => 'Belgium',
            'BZ' => 'Belize',
            'BJ' => 'Benin',
            'BM' => 'Bermuda',
            'BT' => 'Bhutan',
            'BO' => 'Bolivia',
            'BA' => 'Bosnia And Herzegovina',
            'BW' => 'Botswana',
            'BV' => 'Bouvet Island',
            'BR' => 'Brazil',
            'IO' => 'British Indian Ocean Territory',
            'BN' => 'Brunei Darussalam',
            'BG' => 'Bulgaria',
            'BF' => 'Burkina Faso',
            'BI' => 'Burundi',
            'KH' => 'Cambodia',
            'CM' => 'Cameroon',
            'CA' => 'Canada',
            'CV' => 'Cape Verde',
            'KY' => 'Cayman Islands',
            'CF' => 'Central African Republic',
            'TD' => 'Chad',
            'CL' => 'Chile',
            'CN' => 'China',
            'CX' => 'Christmas Island',
            'CC' => 'Cocos (Keeling) Islands',
            'CO' => 'Colombia',
            'KM' => 'Comoros',
            'CG' => 'Congo',
            'CD' => 'Congo, Democratic Republic',
            'CK' => 'Cook Islands',
            'CR' => 'Costa Rica',
            'CI' => 'Cote D\'Ivoire',
            'HR' => 'Croatia',
            'CU' => 'Cuba',
            'CY' => 'Cyprus',
            'CZ' => 'Czech Republic',
            'DK' => 'Denmark',
            'DJ' => 'Djibouti',
            'DM' => 'Dominica',
            'DO' => 'Dominican Republic',
            'EC' => 'Ecuador',
            'EG' => 'Egypt',
            'SV' => 'El Salvador',
            'GQ' => 'Equatorial Guinea',
            'ER' => 'Eritrea',
            'EE' => 'Estonia',
            'ET' => 'Ethiopia',
            'FK' => 'Falkland Islands (Malvinas)',
            'FO' => 'Faroe Islands',
            'FJ' => 'Fiji',
            'FI' => 'Finland',
            'FR' => 'France',
            'GF' => 'French Guiana',
            'PF' => 'French Polynesia',
            'TF' => 'French Southern Territories',
            'GA' => 'Gabon',
            'GM' => 'Gambia',
            'GE' => 'Georgia',
            'DE' => 'Germany',
            'GH' => 'Ghana',
            'GI' => 'Gibraltar',
            'GR' => 'Greece',
            'GL' => 'Greenland',
            'GD' => 'Grenada',
            'GP' => 'Guadeloupe',
            'GU' => 'Guam',
            'GT' => 'Guatemala',
            'GG' => 'Guernsey',
            'GN' => 'Guinea',
            'GW' => 'Guinea-Bissau',
            'GY' => 'Guyana',
            'HT' => 'Haiti',
            'HM' => 'Heard Island & Mcdonald Islands',
            'VA' => 'Holy See (Vatican City State)',
            'HN' => 'Honduras',
            'HK' => 'Hong Kong',
            'HU' => 'Hungary',
            'IS' => 'Iceland',
            'IN' => 'India',
            'ID' => 'Indonesia',
            'IR' => 'Iran, Islamic Republic Of',
            'IQ' => 'Iraq',
            'IE' => 'Ireland',
            'IM' => 'Isle Of Man',
            'IL' => 'Israel',
            'IT' => 'Italy',
            'JM' => 'Jamaica',
            'JP' => 'Japan',
            'JE' => 'Jersey',
            'JO' => 'Jordan',
            'KZ' => 'Kazakhstan',
            'KE' => 'Kenya',
            'KI' => 'Kiribati',
            'KR' => 'Korea',
            'KW' => 'Kuwait',
            'KG' => 'Kyrgyzstan',
            'LA' => 'Lao People\'s Democratic Republic',
            'LV' => 'Latvia',
            'LB' => 'Lebanon',
            'LS' => 'Lesotho',
            'LR' => 'Liberia',
            'LY' => 'Libyan Arab Jamahiriya',
            'LI' => 'Liechtenstein',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'MO' => 'Macao',
            'MK' => 'Macedonia',
            'MG' => 'Madagascar',
            'MW' => 'Malawi',
            'MY' => 'Malaysia',
            'MV' => 'Maldives',
            'ML' => 'Mali',
            'MT' => 'Malta',
            'MH' => 'Marshall Islands',
            'MQ' => 'Martinique',
            'MR' => 'Mauritania',
            'MU' => 'Mauritius',
            'YT' => 'Mayotte',
            'MX' => 'Mexico',
            'FM' => 'Micronesia, Federated States Of',
            'MD' => 'Moldova',
            'MC' => 'Monaco',
            'MN' => 'Mongolia',
            'ME' => 'Montenegro',
            'MS' => 'Montserrat',
            'MA' => 'Morocco',
            'MZ' => 'Mozambique',
            'MM' => 'Myanmar',
            'NA' => 'Namibia',
            'NR' => 'Nauru',
            'NP' => 'Nepal',
            'NL' => 'Netherlands',
            'AN' => 'Netherlands Antilles',
            'NC' => 'New Caledonia',
            'NZ' => 'New Zealand',
            'NI' => 'Nicaragua',
            'NE' => 'Niger',
            'NG' => 'Nigeria',
            'NU' => 'Niue',
            'NF' => 'Norfolk Island',
            'MP' => 'Northern Mariana Islands',
            'NO' => 'Norway',
            'OM' => 'Oman',
            'PK' => 'Pakistan',
            'PW' => 'Palau',
            'PS' => 'Palestinian Territory, Occupied',
            'PA' => 'Panama',
            'PG' => 'Papua New Guinea',
            'PY' => 'Paraguay',
            'PE' => 'Peru',
            'PH' => 'Philippines',
            'PN' => 'Pitcairn',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'PR' => 'Puerto Rico',
            'QA' => 'Qatar',
            'RE' => 'Reunion',
            'RO' => 'Romania',
            'RU' => 'Russian Federation',
            'RW' => 'Rwanda',
            'BL' => 'Saint Barthelemy',
            'SH' => 'Saint Helena',
            'KN' => 'Saint Kitts And Nevis',
            'LC' => 'Saint Lucia',
            'MF' => 'Saint Martin',
            'PM' => 'Saint Pierre And Miquelon',
            'VC' => 'Saint Vincent And Grenadines',
            'WS' => 'Samoa',
            'SM' => 'San Marino',
            'ST' => 'Sao Tome And Principe',
            'SA' => 'Saudi Arabia',
            'SN' => 'Senegal',
            'RS' => 'Serbia',
            'SC' => 'Seychelles',
            'SL' => 'Sierra Leone',
            'SG' => 'Singapore',
            'SK' => 'Slovakia',
            'SI' => 'Slovenia',
            'SB' => 'Solomon Islands',
            'SO' => 'Somalia',
            'ZA' => 'South Africa',
            'GS' => 'South Georgia And Sandwich Isl.',
            'ES' => 'Spain',
            'LK' => 'Sri Lanka',
            'SD' => 'Sudan',
            'SR' => 'Suriname',
            'SJ' => 'Svalbard And Jan Mayen',
            'SZ' => 'Swaziland',
            'SE' => 'Sweden',
            'CH' => 'Switzerland',
            'SY' => 'Syrian Arab Republic',
            'TW' => 'Taiwan',
            'TJ' => 'Tajikistan',
            'TZ' => 'Tanzania',
            'TH' => 'Thailand',
            'TL' => 'Timor-Leste',
            'TG' => 'Togo',
            'TK' => 'Tokelau',
            'TO' => 'Tonga',
            'TT' => 'Trinidad And Tobago',
            'TN' => 'Tunisia',
            'TR' => 'Turkey',
            'TM' => 'Turkmenistan',
            'TC' => 'Turks And Caicos Islands',
            'TV' => 'Tuvalu',
            'UG' => 'Uganda',
            'UA' => 'Ukraine',
            'AE' => 'United Arab Emirates',
            'GB' => 'United Kingdom',
            'US' => 'United States',
            'UM' => 'United States Outlying Islands',
            'UY' => 'Uruguay',
            'UZ' => 'Uzbekistan',
            'VU' => 'Vanuatu',
            'VE' => 'Venezuela',
            'VN' => 'Viet Nam',
            'VG' => 'Virgin Islands, British',
            'VI' => 'Virgin Islands, U.S.',
            'WF' => 'Wallis And Futuna',
            'EH' => 'Western Sahara',
            'YE' => 'Yemen',
            'ZM' => 'Zambia',
            'ZW' => 'Zimbabwe',
        ];

        public static function getCountryCode(&$country)
        {
            $country = array_search(CountryCodes::$countryArrayShort[$country], CountryCodes::$countryArray);
        }
    }
}

if (! class_exists('PayGateFunctions')) {
    class PayGateFunctions
    {
        public $fullPath;

        public $root;

        public $directory;

        public function __construct()
        {
            $fullPath = $this->getCurrentUrl();
            $root = $this->getRoot($fullPath);
            $directory = $this->getFinalDirectory($fullPath);
        }

        public function getCurrentUrl()
        {
            $url = [];

            // set protocol
            $url['protocol'] = 'http://';
            if (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) === 'on' || $_SERVER['HTTPS'] == 1)) {
                $url['protocol'] = 'https://';
            } elseif (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
                $url['protocol'] = 'https://';
            }

            // set host
            $url['host'] = $_SERVER['HTTP_HOST'];
            // set request uri in a secure way
            $url['request_uri'] = $_SERVER['REQUEST_URI'];

            return $url;
        }

        /*
    	 * Gets the root directory of the Sample Code
    	 *
    	 * @param $urlArray
    	 * @return string
    	 */
        public function getRoot($urlArray)
        {
            $pathArray = explode('/', $urlArray['request_uri']);

            return $pathArray[1];
        }

        /*
    	 * @param $urlArray
    	 * @return string
    	 */
        public function getFinalDirectory($urlArray)
        {
            $pathArray = explode('/', $urlArray['request_uri']);
            $numDirectories = count($pathArray);
            $finalDirectory = '';

            for ($i = 0; $i < ($numDirectories - 1); $i++) {
                $finalDirectory .= $pathArray[$i].'/';
            }

            return $finalDirectory;
        }

        /*
    	 * generates a string reference according to Date/Time
    	 *
    	 * @return string
    	 */
        public function generateReference()
        {
            return 'pgtest_'.$this->getDateTime('YmdHis');
        }

        public function getDateTime($format)
        {
            date_default_timezone_set('Africa/Johannesburg');
            $dateTime = new DateTime;

            return $dateTime->format($format);
        }

        public function generateCountrySelectOptions($country = 'ZA')
        {
            $options = '';

            $mostUsedCountryArray = CountryCodes::$mostUsedCountryArray;

            $countryArray = CountryCodes::$countryArray;
            CountryCodes::getCountryCode($country);
            $defaultSet = false;

            $options .= '<optgroup label="">
    						<option value="" >Select Country</option>
    					</optgroup>';

            $options .= '<optgroup label="Most Used">';
            foreach ($mostUsedCountryArray as $id => $name) {
                $options .= '   <option value="'.$id.'" ';
                if ($country == $id && ! $defaultSet) {
                    $options .= 'selected="selected" ';
                    $defaultSet = true;
                }
                $options .= '>'.$name.'</option>';
            }

            $options .= '</optgroup>';
            $options .= '<optgroup label="All Countries">';

            foreach ($countryArray as $id => $name) {
                $options .= '   <option value="'.$id.'" ';
                if ($country == $id && ! $defaultSet) {
                    $options .= 'selected="selected" ';
                    $defaultSet = true;
                }
                $options .= '>'.$name.'</option>';
            }

            $options .= '</optgroup>';

            return $options;
        }

        public function getLocale()
        {
            return 'en-za';
        }

        public function convertAmount($amount = '0')
        {
            return str_ireplace('.', '', $amount);
        }
    }
}

if (! class_exists('PayGate_PayWeb3')) {
    class PayGate_PayWeb3
    {
        /*
         * @var string the url of the PayGate PayWeb 3 initiate page
         */
        public static $initiate_url = 'https://secure.paygate.co.za/payweb3/initiate.trans';

        /*
         * @var string the url of the PayGate PayWeb 3 process page
         */
        public static $process_url = 'https://secure.paygate.co.za/payweb3/process.trans';

        /*
         * @var string the url of the PayGate PayWeb 3 query page
         */
        public static $query_url = 'https://secure.paygate.co.za/payweb3/query.trans';

        /*
         * @var array contains the data to be posted to PayGate PayWeb 3 initiate
         */
        public $initiateRequest;

        /*
         * @var array contains the response data from the initiate
         */
        public $initiateResponse;

        /*
         * @var array contains the data returned from the initiate, required for the redirect of the client
         */
        public $processRequest;

        /*
         * @var array contains the data to be posted to PayGate PayWeb 3 query service
         */
        public $queryRequest;

        /*
         * @var array contains the response data from the query
         */
        public $queryResponse;

        /*
         * @var string
         *
         * Most common errors returned will be:
         *
         * DATA_CHK    -> Checksum posted does not match the one calculated by PayGate, either due to an incorrect encryption key used or a field that has been excluded from the checksum calculation
         * DATA_PW     -> Mandatory fields have been excluded from the post to PayGate, refer to page 9 of the documentation as to what fields should be posted.
         * DATA_CUR    -> The currency that has been posted to PayGate is not supported.
         * PGID_NOT_EN -> The PayGate ID being used to post data to PayGate has not yet been enabled, or there are no payment methods setup on it.
         *
         */
        public $lastError;

        private $transactionStatusArray = [
            1 => 'Approved',
            2 => 'Declined',
            4 => 'Cancelled',
        ];

        public $debug = false;

        public $ssl = false;

        /*
         * @var string (as set up on the PayWeb 3 config page in the PayGate Back Office )
         */
        private $encryptionKey;

        public function __construct() {}

        /*
         * @return boolean
         */
        public function isDebug()
        {
            return $this->debug;
        }

        /*
         * @param boolean $debug
         */
        public function setDebug($debug)
        {
            $this->debug = $debug;
        }

        /*
         * @return boolean
         */
        public function isSsl()
        {
            return $this->ssl;
        }

        /*
         * @param boolean $ssl
         */
        public function setSsl($ssl)
        {
            $this->ssl = $ssl;
        }

        /*
         * @return array
         */
        public function getInitiateRequest()
        {
            return $this->initiateRequest;
        }

        /*
         * @param array $postData
         */
        public function setInitiateRequest($postData)
        {
            $this->initiateRequest = $postData;
        }

        /*
         * @return array
         */
        public function getQueryRequest()
        {
            return $this->queryRequest;
        }

        /*
         * @param array $queryRequest
         */
        public function setQueryRequest($queryRequest)
        {
            $this->queryRequest = $queryRequest;
        }

        /*
         * @return string
         */
        public function getEncryptionKey()
        {
            return $this->encryptionKey;
        }

        /*
         * @param string $encryptionKey
         */
        public function setEncryptionKey($encryptionKey)
        {
            $this->encryptionKey = $encryptionKey;
        }

        /*
         * @return bool
         */
        public function _is_curl_installed()
        {
            if (in_array('curl', get_loaded_extensions())) {
                return true;
            } else {
                return false;
            }
        }

        /*
         * returns a description of the transaction status number passed back from PayGate
         *
         * @param int $statusNumber
         * @return string
         */
        public function getTransactionStatusDescription($statusNumber)
        {
            return $this->transactionStatusArray[$statusNumber];
        }

        /*
         * Function to format date / time. php's DateTime object used to overcome limitation of standard date() function.
         * DateTime available from PHP 5.2.0
         *
         * @param string $format
         * @return string
         */
        public function getDateTime($format)
        {
            if (version_compare(PHP_VERSION, '5.2.0', '<')) {
                $dateTime = date('Y-m-d H:i:s');

                return $dateTime;
            } else {
                $dateTime = new DateTime;

                return $dateTime->format($format);
            }
        }

        /*
         * Function to generate the checksum to be passed in the initiate call. Refer to examples on Page 15 of the PayWeb3 documentation
         *
         * @param array $postData
         * @return string (md5 hash value)
         */
        public function generateChecksum($postData)
        {
            $checksum = '';

            foreach ($postData as $key => $value) {
                if ($value != '') {
                    $checksum .= $value;
                }
            }

            $checksum .= $this->getEncryptionKey();

            if ($this->isDebug()) {
            }

            return md5($checksum);
        }

        /*
         * function to compare checksums
         *
         * @param array $data
         * @return bool
         */
        public function validateChecksum($data)
        {
            $returnedChecksum = $data['CHECKSUM'];
            unset($data['CHECKSUM']);

            $checksum = $this->generateChecksum($data);

            return $returnedChecksum == $checksum;
        }

        /*
         * Function to handle response from initiate request and set error or processRequest as need be
         *
         * @return bool
         */
        public function handleInitiateResponse()
        {
            if (array_key_exists('ERROR', $this->initiateResponse)) {
                $this->lastError = $this->initiateResponse['ERROR'];
                unset($this->initiateResponse);

                return false;
            }

            $this->processRequest = [
                'PAY_REQUEST_ID' => $this->initiateResponse['PAY_REQUEST_ID'],
                'CHECKSUM' => $this->initiateResponse['CHECKSUM'],
            ];

            return true;
        }

        /*
         * Function to handle response from Query request and set error as need be
         *
         * @return bool
         */
        public function handleQueryResponse()
        {
            if (array_key_exists('ERROR', $this->queryResponse)) {
                $this->lastError = $this->queryResponse['ERROR'];
                unset($this->queryResponse);

                return false;
            }

            return true;
        }

        /*
         * Function to do curl post to PayGate to initiate a PayWeb 3 transaction
         *
         * @return bool
         */
        public function doInitiate()
        {
            $this->initiateRequest['CHECKSUM'] = $this->generateChecksum($this->initiateRequest);

            $result = $this->doCurlPost($this->initiateRequest, self::$initiate_url);

            if ($result !== false) {
                parse_str($result, $this->initiateResponse);
                $result = $this->handleInitiateResponse();
            }

            return $result;
        }

        /*
         * Function to do curl post to PayGate to query a PayWeb 3 transaction
         *
         * @return bool
         */
        public function doQuery()
        {
            $this->queryRequest['CHECKSUM'] = $this->generateChecksum($this->queryRequest);

            $result = $this->doCurlPost($this->queryRequest, self::$query_url);

            if ($result !== false) {
                parse_str($result, $this->queryResponse);
                $result = $this->handleQueryResponse();
            }

            return $result;
        }

        /*
         * function to do actual curl post to PayGate
         *
         * @param array $postData data to be posted
         * @param string $url to be posted to
         * @return bool | string
         */
        public function doCurlPost($postData, $url)
        {
            if ($this->_is_curl_installed()) {
                $fields_string = '';

                //url-ify the data for the POST
                foreach ($postData as $key => $value) {
                    $fields_string .= $key.'='.urlencode($value).'&';
                }
                //remove trailing '&'
                $fields_string = rtrim($fields_string, '&');

                if ($this->isDebug()) {
                }

                //open connection
                $ch = curl_init();

                //set the url, number of POST vars, POST data
                if (! $this->isSsl()) {
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                }
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_NOBODY, false);
                curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

                //execute post
                $result = curl_exec($ch);

                //close connection
                curl_close($ch);

                if ($this->isDebug()) {
                }

                return $result;
            } else {
                $this->lastError = 'cURL is NOT installed on this server. http://php.net/manual/en/curl.setup.php';

                return false;
            }
        }
    }
}
