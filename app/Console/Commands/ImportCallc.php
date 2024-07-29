<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Carbon\Carbon;

class ImportCallc extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'import:callc';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Command callc NEW, CallBack, Deposit, howmany for imports';

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
    $imports = DB::table('imports')->where('callc', 1);

    //echo (vsprintf(str_replace(array('?'), array('\'%s\''), $imports->toSql()), $imports->getBindings()));

    foreach ($imports->get() as $import) {
      $a_hm = (object) [];
      if ($import->message != '') {
        $a_hm = DB::selectOne('SELECT SUM(status_id = 8)hmnew,SUM(status_id = 9)hmcb,SUM(status_id = 10)hmdp,COUNT(*)hm FROM lids l WHERE l.`load_mess` = "' . $import->message . '"');
        $a_hm->hmnew = isset($a_hm->hmnew) ? $a_hm->hmnew : 0;
        $a_hm->hmcb = isset($a_hm->hmcb) ? $a_hm->hmcb : 0;
        $a_hm->hmdp = isset($a_hm->hmdp) ? $a_hm->hmdp : 0;
        $a_hm->callc = 0;
        // $a_hm->updated_at = date('Y-m-d H:m:s');
        DB::table('imports')->where('id', $import->id)->update((array)$a_hm);
      }
      $a_hm->callc = 0;
      // $a_hm->updated_at = date('Y-m-d H:m:s');
      DB::table('imports')->where('id', $import->id)->update((array)$a_hm);
    }

    $imports_provider = DB::table('imports_provider')
      ->where('callc', 1);
    //echo (vsprintf(str_replace(array('?'), array('\'%s\''), $imports_provider->toSql()), $imports_provider->getBindings()));

    foreach ($imports_provider->get() as $import) {
      $a_hm = (object) [];
      $lid_ids = DB::table('imported_leads')
        ->where('api_key_id', $import->provider_id)
        ->whereDate('upload_time', $import->date)
        ->where('geo', $import->geo)
        ->pluck('lead_id')
        ->toArray();
      if (count($lid_ids) == 0) continue;

      $a_hm = DB::selectOne('SELECT SUM(status_id = 8)hmnew,SUM(status_id = 9)hmcb, SUM(status_id = 10)hmdp, COUNT(*)hm FROM lids l WHERE l.`id` IN (' . implode(",", $lid_ids) . ')');
      $a_hm->hmnew = isset($a_hm->hmnew) ? $a_hm->hmnew : 0;
      $a_hm->hmcb = isset($a_hm->hmcb) ? $a_hm->hmcb : 0;
      $a_hm->hmdp = isset($a_hm->hmdp) ? $a_hm->hmdp : 0;
      $a_hm->callc = 0;

      DB::table('imports_provider')->where('id', $import->id)->update((array)$a_hm);
    }

    return 0;
  }
}
