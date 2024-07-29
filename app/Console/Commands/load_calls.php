<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use DB;

class load_calls extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'loadcalls:daily';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Parse file with calls and write in table calls on users';

  /**
   * Create a new command instance.
   *
   * @return void
   */
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * Execute the console command.
   *
   * @return int
   */
  public function handle()
  {
    $file = base_path() . '/calls/LogRecords.csv';
    if (!file_exists($file)) exit;

    $row = 1;

    if (($handle = fopen($file, "r")) !== FALSE) {
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        if ($row++ == 1) {
          continue;
        }
        $item = [];
        $item['user_id'] = DB::table('users')->where('name', $data[1])->value('id');
        //$data[1]; //name

        $item['timecall'] = $data[4]; //CreatedAtUtc
        $item['duration'] = $data[5]; //CallDurationSeconds
        $item['tel'] = $data[7]; //Number
        $item['operator'] = $data[8]; //Operator
        $item['geo'] = $this->getGeo($data[7]);
        if ($item['user_id']) {
          DB::table('calls')->insert($item);
        }
      }
      fclose($handle);
    }
    unlink($file);
    return 0;
  }

  private function getGeo($tel)
  {
    $geo = '';
    $telcod = ["93" => "AF", "358" => "AX", "355" => "AL", "213" => "DZ", "1684" => "AS", "376" => "AD", "244" => "AO", "1264" => "AI", "672" => "AQ", "1268" => "AG", "54" => "AR", "374" => "AM", "297" => "AW", "61" => "AU", "43" => "AT", "994" => "AZ", "1242" => "BS", "973" => "BH", "880" => "BD", "1246" => "BB", "375" => "BY", "32" => "BE", "501" => "BZ", "229" => "BJ", "1441" => "BM", "975" => "BT", "591" => "BO", "387" => "BA", "267" => "BW", "55" => "BR", "246" => "IO", "673" => "BN", "359" => "BG", "226" => "BF", "257" => "BI", "855" => "KH", "237" => "CM", "1" => "CA", "238" => "CV", " 345" => "KY", "236" => "CF", "235" => "TD", "56" => "CL", "86" => "CN", "61" => "CX", "61" => "CC", "57" => "CO", "269" => "KM", "242" => "CG", "243" => "CD", "682" => "CK", "506" => "CR", "225" => "CI", "385" => "HR", "53" => "CU", "357" => "CY", "420" => "CZ", "45" => "DK", "253" => "DJ", "1767" => "DM", "1849" => "DO", "593" => "EC", "20" => "EG", "503" => "SV", "240" => "GQ", "291" => "ER", "372" => "EE", "251" => "ET", "500" => "FK", "298" => "FO", "679" => "FJ", "358" => "FI", "33" => "FR", "594" => "GF", "689" => "PF", "241" => "GA", "220" => "GM", "995" => "GE", "49" => "DE", "233" => "GH", "350" => "GI", "30" => "GR", "299" => "GL", "1473" => "GD", "590" => "GP", "1671" => "GU", "502" => "GT", "44" => "GG", "224" => "GN", "245" => "GW", "595" => "GY", "509" => "HT", "379" => "VA", "504" => "HN", "852" => "HK", "36" => "HU", "354" => "IS", "91" => "IN", "62" => "ID", "98" => "IR", "964" => "IQ", "353" => "IE", "44" => "IM", "972" => "IL", "39" => "IT", "1876" => "JM", "81" => "JP", "44" => "JE", "962" => "JO", "77" => "KZ", "254" => "KE", "686" => "KI", "850" => "KP", "82" => "KR", "965" => "KW", "996" => "KG", "856" => "LA", "371" => "LV", "961" => "LB", "266" => "LS", "231" => "LR", "218" => "LY", "423" => "LI", "370" => "LT", "352" => "LU", "853" => "MO", "389" => "MK", "261" => "MG", "265" => "MW", "60" => "MY", "960" => "MV", "223" => "ML", "356" => "MT", "692" => "MH", "596" => "MQ", "222" => "MR", "230" => "MU", "262" => "YT", "52" => "MX", "691" => "FM", "373" => "MD", "377" => "MC", "976" => "MN", "382" => "ME", "1664" => "MS", "212" => "MA", "258" => "MZ", "95" => "MM", "264" => "NA", "674" => "NR", "977" => "NP", "31" => "NL", "599" => "AN", "687" => "NC", "64" => "NZ", "505" => "NI", "227" => "NE", "234" => "NG", "683" => "NU", "672" => "NF", "1670" => "MP", "47" => "NO", "968" => "OM", "92" => "PK", "680" => "PW", "970" => "PS", "507" => "PA", "675" => "PG", "595" => "PY", "51" => "PE", "63" => "PH", "872" => "PN", "48" => "PL", "351" => "PT", "1939" => "PR", "974" => "QA", "40" => "RO", "7" => "RU", "250" => "RW", "262" => "RE", "590" => "BL", "290" => "SH", "1869" => "KN", "1758" => "LC", "590" => "MF", "508" => "PM", "1784" => "VC", "685" => "WS", "378" => "SM", "239" => "ST", "966" => "SA", "221" => "SN", "381" => "RS", "248" => "SC", "232" => "SL", "65" => "SG", "421" => "SK", "386" => "SI", "677" => "SB", "252" => "SO", "27" => "ZA", "211" => "SS", "500" => "GS", "34" => "ES", "94" => "LK", "249" => "SD", "597" => "SR", "47" => "SJ", "268" => "SZ", "46" => "SE", "41" => "CH", "963" => "SY", "886" => "TW", "992" => "TJ", "255" => "TZ", "66" => "TH", "670" => "TL", "228" => "TG", "690" => "TK", "676" => "TO", "1868" => "TT", "216" => "TN", "90" => "TR", "993" => "TM", "1649" => "TC", "688" => "TV", "256" => "UG", "380" => "UA", "971" => "AE", "44" => "GB", "1" => "US", "598" => "UY", "998" => "UZ", "678" => "VU", "58" => "VE", "84" => "VN", "1284" => "VG", "1340" => "VI", "681" => "WF", "967" => "YE", "260" => "ZM", "263" => "ZW"];
    for ($i = 2; $i < 5; $i++) {
      $first = substr($tel, 0, $i);
      if (array_key_exists($first, $telcod)) {
        $geo = $telcod[$first];
        break;
      }
    }
    return $geo;
  }
}
