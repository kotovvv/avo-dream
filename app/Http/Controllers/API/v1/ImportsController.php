<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Import;
use App\Models\Lid;
use App\Models\Log;
use App\Models\User;
use DB;
use Debugbar;
use Storage;

class ImportsController extends Controller
{
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index($from = 0, $to = 0)
  {
    $imports = Import::select(['imports.*', DB::raw('(SELECT `name` FROM `providers` WHERE `id` = `provider_id`) AS provider'), DB::raw('(SELECT `name` FROM `users` WHERE `id` = `user_id`) AS user'), DB::raw('(SELECT `group_id` FROM `users` WHERE `id` = `user_id`) AS group_id')])
      ->when($from != 0 && $to != 0, function ($query) use ($from, $to) {
        return $query->whereBetween('start', [$from . ' 00:00:00', $to . ' 23:59:59']);
      })
      ->orderByDesc('end')->get();
    foreach ($imports as $import) {
      Import::where('id', $import->id)->update(['callc' => 1]);
    }

    return $imports;
  }

  public function putBTC(Request $request)
  {
    $data = $request->all();
    foreach ($data['data'] as $key => $row) {
      $data['data'][$key]['office_id'] = $data['office_id'];
    }
    $response = DB::table('btc_list')->insertOrIgnore($data['data']);
    return $response;
  }

  private function  date_range($first, $last, $step = '+1 day', $output_format = 'Y-m-d')
  {
    $dates = array();
    $current = strtotime($first);
    $last = strtotime($last);
    while ($current <= $last) {
      $dates[] = date($output_format, $current);
      $current = strtotime($step, $current);
    }
    return $dates;
  }

  private function between_dates($date, $datefrom, $dateto)
  {
    $dateFrom = strtotime($datefrom);
    $dateTo = strtotime($dateto);
    $u_date = strtotime($date);
    if ($u_date >= $dateFrom && $u_date <= $dateTo) {
      return true;
    }
    return false;
  }

  public function getBTCotherOnDate(Request $request)
  {
    $req = $request->all();
    $office_id = session()->get('office_id');
    $where = $office_id > 0 ? "  bl.`office_id` = " . $office_id . " AND " : "";
    $dateFrom = $req['datefrom'];
    $dateTo = $req['dateto'];
    $onlynew = $req['onlynew'];

    $sql = "SELECT bl.`id`, bl.`address`, bl.`summ`, bl.`office_id`, bl.`other`, bl.`trx_count`, l.`id` lid_id, l.`name`, l.`created_at`, l.`tel`, l.`email`, l.`provider_id`, l.`status_id`, s.`name` s_name, s.`color` s_color, p.`name` p_name, (SELECT IF (SUM(d.`depozit`), SUM(d.`depozit`), '') FROM `depozits` d WHERE l.`id` = d.`lid_id` AND d.`created_at` > '" . $dateFrom . " 00:00:00' AND d.`created_at` < '" . $dateTo . " 23:59:59') depozit FROM `btc_list` bl INNER JOIN `lids` l ON (bl.`lid_id` = l.`id`) INNER JOIN `providers` p ON (l.`provider_id` = p.`id`) INNER JOIN `statuses` s ON (l.`status_id` = s.`id`) WHERE " . $where . "`other` REGEXP '[^|].*' ORDER BY l.`id`";
    $rows = DB::select(DB::raw($sql));
    //array dates (from to)
    $a_list_date = $this->date_range($dateFrom, $dateTo);
    $res['data'] = $data =  [];
    $res['providers'] = [];
    $res['statuses'] = [];
    $res['result'] = "success";

    $compareLidId = $sum_lid = $ia =  0;
    if ($rows) {
      //foreach row
      foreach ($rows as $lid) {
        $a_date_sum = $a_intersect = [];
        $sum_dat = 0;
        $other = $lid->other;
        $a_date_sum[0] = explode('|', $other);
        $max = count($a_date_sum[0]);
        for (
          $i = 1;
          $i < $max;
          $i += 2
        ) {
          $a_date_sum[1][] = date('Y-m-d', $a_date_sum[0][$i]);
        }

        $a_intersect = array_intersect($a_date_sum[1], $a_list_date);
        if ($onlynew && count($a_date_sum[1]) != count($a_intersect)) {
          continue;
        }
        if ($a_intersect) {
          foreach ($a_intersect as $key => $date) {
            if ($this->between_dates($date, $dateFrom, $dateTo)) {
              $sum_dat += $a_date_sum[0][($key + 1) * 2];
            }
            $res['providers'][] = ['id' => $lid->provider_id, 'name' => $lid->p_name];
            $res['statuses'][] = ['id' => $lid->status_id, 'name' => $lid->s_name, 'color' => $lid->s_color];
          }
          // group same lids
          if ($compareLidId == $lid->lid_id) {
            $data[$ia - 1]['summ']  += $lid->summ;
            $data[$ia - 1]['sum_dat']  += $sum_dat;
            continue;
          } else {
            $compareLidId = $lid->lid_id;
            $data[$ia++] = [
              'id' => $lid->id,
              'name' => $lid->name,
              'email' => $lid->email,
              'tel' => $lid->tel,
              'created_at' => $lid->created_at,
              'address' => $lid->address,
              'lid_id' => $lid->lid_id,
              'status_id' =>  $lid->status_id,
              's_name' =>  $lid->s_name,
              'summ' => $lid->summ,
              'office_id' => $lid->office_id,
              'provider_id' => $lid->provider_id,
              'p_name' => $lid->p_name,
              'sum_dat' => $sum_dat,
              'depozit' => (int) $lid->depozit
            ];
          }
        }
        //next row
      }
    }

    $res['data'] = array_values($data);
    return $res;
  }

  public function getBTCsOnDate(Request $request)
  {
    $res = [];
    $req = $request->all();
    if (isset($req['office_id'])) {
      $office_id = $req['office_id'];
    } else {
      $office_id = session()->get('office_id');
    }
    $where = $office_id > 0 ? "  l.`office_id` = " . $office_id . " AND " : "";

    $date = [date('Y-m-d', strtotime($req['datefrom'])) . ' ' . date("H:i:s", mktime(0, 0, 0)), date('Y-m-d', strtotime($req['dateto'])) . ' ' . date("H:i:s", mktime(23, 59, 59))];

    $res['list'] = DB::select("SELECT bl.`address`, bl.`summ`, bl.`trx_count`, bl.`date_time`, l.`id` lid_id, u.`fio`,u.`office_id` FROM `btc_list` bl INNER JOIN `lids` l ON (bl.`lid_id` = l.`id` ) INNER JOIN `users` u ON (bl.`user_id` = u.`id` ) WHERE " . $where . " bl.`date_time` >= '" . $date[0] . "' AND bl.`date_time` <= '" . $date[1] . "'");

    $res['free'] = DB::select('SELECT COUNT(*) count,office_id FROM `btc_list` WHERE used = 0 GROUP BY office_id');


    return response($res, 200);
  }

  /**
   * Show the form for creating a new resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function create()
  {
    //
  }

  /**
   * Store a newly created resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function store(Request $request, $from = 0, $to = 0)
  {
    $a_import = $request->all();
    DB::table('imports')->insert($a_import);
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function show($id)
  {
    //
  }

  /**
   * Show the form for editing the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function edit($id)
  {
  }

  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request

   * @return \Illuminate\Http\Response
   */
  public function importUpdate(Request $request)
  {
    $data = $request->all();
    if (isset($data['message'])) {
      $import = DB::table('imports')->where('id', $data['id'])->first();
      $date = date('Y-m-d', strtotime($import->start));
      DB::table('lids')->where('load_mess', $import->message)->whereDate('created_at', $date)->update(['load_mess' => $data['message']]);
      if ($import->provider_id != $data['provider_id']) {
        Lid::where('load_mess', $import->message)->update(['provider_id' => $data['provider_id']]);
      }
      DB::table('imports')->where('id', $data['id'])->update(['message' => $data['message'], 'sum' => $data['sum'], 'cp' => $data['cp'], 'provider_id' => $data['provider_id'], 'baer' => $data['baer'] == 'null' ? '' : $data['baer']]);
    } else {
      DB::table('imports_provider')->where('id', $data['id'])->update(['sum' => $data['sum'], 'cp' => $data['cp']]);
    }
  }


  public function getHistory(Request $request)
  {
    $data = $request->all();
    $loads_id = $data['id'];
    $message = isset($data['message']) ? $data['message'] : null;
    $response = [];
    $response['statuses'] = [];
    $lidids = DB::table('historyimport')->where('imports_id', $loads_id)->when($message != null, function ($query) use ($message) {
      return $query->where('load_mess', $message);
    })->orderBy('created_at', 'DESC')->get()->pluck('lids')->toArray();
    // $sql= "SELECT lids FROM `historyimport` WHERE imports_id = ". $loads_id." ORDER BY created_at DESC LIMIT 1";
    if (count($lidids)) {
      $getLiads = Lid::whereIn('lids.id', explode(',', $lidids[0]));
      $response['statuses'] = $getLiads->select(DB::Raw('count(statuses.id) hm'), 'statuses.id', 'statuses.name', 'statuses.color')
        ->leftJoin('statuses', 'statuses.id', '=', 'status_id')
        ->groupBy('statuses.id')
        ->orderBy('statuses.order', 'ASC')
        ->get();
    }

    $response['history'] = DB::table('historyimport')->where('imports_id', $loads_id)->orderBy('created_at', 'DESC')->get();
    return $response;
  }

  public function redistribute(Request $request)
  {
    $data = $request->all();
    $imoprtsIdsm = $data['importsIdsm'];
    $usersIds = $data['usersIds'];
    $resetStatus = $data['resetStatus'];
    $alliads = [];
    $setLiads = [];
    $users_ids = [];
    $files = [];
    $office_id = null;
    $where_ids_off = '';

    if (session()->has('user_id')) {
      $user = User::where('id', (int) session()->get('user_id'))->first();
      if ($user['group_id']) {
        $res = User::select('id')->whereIn('group_id', $user['group_id'])->get()->toArray();
        foreach ($res as $item) {
          $users_ids[] = $item['id'];
        }
        $where_ids_off = ' user_id IN (' . implode(',', $users_ids) . ') AND ';
      }
      $office_id = $user['office_id'];
      if ($office_id > 0) {
        $where_ids_off .= 'office_id = ' . (int) $office_id . ' AND ';
      }
    }

    foreach ($imoprtsIdsm as $import_) {

      $historyimp = [];
      if (isset($import_['message'])) {

        //?- get row from imports on id - $import_['id']
        //- get id from lids on load_mess and date
        $getLiads = Lid::where('load_mess', $import_['message'])
          ->when($office_id > 0, function ($query) use ($office_id) {
            return $query->where('office_id', $office_id);
          })
          ->when(count($users_ids) > 0, function ($query) use ($users_ids) {
            return $query->whereIn('user_id', $users_ids);
          })
          ->whereDate('lids.created_at', date('Y-m-d', strtotime($import_['start'])));
        //- get statuses for this leads
        $historyimp['statuses'] = $getLiads->select(DB::Raw('count(statuses.id) hm'), 'statuses.id', 'statuses.name', 'statuses.color')
          ->leftJoin('statuses', 'statuses.id', '=', 'status_id')
          ->groupBy('statuses.id')
          ->orderBy('statuses.order', 'ASC')
          ->get();
        //- select lids which must changed
        $setLiads = Lid::where('load_mess', $import_['message'])
          ->when($office_id > 0, function ($query) use ($office_id) {
            return $query->where('office_id', $office_id);
          })
          ->when(count($users_ids) > 0, function ($query) use ($users_ids) {
            return $query->whereIn('user_id', $users_ids);
          })
          ->when(count($resetStatus) > 0, function ($query) use ($resetStatus) {
            return $query->whereIn('status_id', $resetStatus);
          })->whereDate('lids.created_at', date('Y-m-d', strtotime($import_['start'])))->get()->pluck('id')->toArray();

        $historyimp['lids'] = implode(',', $setLiads);

        $historyimp['imports_id'] = $import_['id'];
        $historyimp['load_mess'] = $import_['message'];
        $historyimp['created_at'] = Now();
        //- insert to history
        DB::table('historyimport')->insert($historyimp);
        //- collect $alliads
        $alliads = array_merge($alliads, $setLiads);
      } else {
        //- get id lids from imported_lids on provider_id and date
        $lidsId = DB::table('imported_leads')->where('api_key_id', $import_['provider_id'])->whereDate('upload_time', $import_['start'])->where('geo', $import_['geo'])->pluck('lead_id')->toArray();

        $getLiads = Lid::whereIn('lids.id', $lidsId);
        //- get statuses for this liads
        $historyimp['statuses'] = $getLiads->select(DB::Raw('count(statuses.id) hm'), 'statuses.id', 'statuses.name', 'statuses.color')
          ->when($office_id > 0, function ($query) use ($office_id) {
            return $query->where('office_id', $office_id);
          })
          ->when(count($users_ids) > 0, function ($query) use ($users_ids) {
            return $query->whereIn('user_id', $users_ids);
          })
          ->leftJoin('statuses', 'statuses.id', '=', 'status_id')
          ->groupBy('statuses.id')
          ->orderBy('statuses.order', 'ASC')
          ->get();
        //- select lids which must changed
        $setLiads = Lid::whereIn('lids.id', $lidsId)
          ->when($office_id > 0, function ($query) use ($office_id) {
            return $query->where('office_id', $office_id);
          })
          ->when(count($users_ids) > 0, function ($query) use ($users_ids) {
            return $query->whereIn('user_id', $users_ids);
          })
          ->when(count($resetStatus) > 0, function ($query) use ($resetStatus) {
            return $query->whereIn('status_id', $resetStatus);
          })->get()->pluck('id')->toArray();
        $historyimp['lids'] = implode(',', $setLiads);

        $historyimp['imports_id'] = $import_['id'];
        $historyimp['created_at'] = Now();
        //- insert to history
        DB::table('historyimport')->insert($historyimp);
        //- collect $alliads
        $alliads = array_merge($alliads, $setLiads);
      }
    }
    $hm = ceil(count($alliads) / count($usersIds));

    foreach (array_chunk($alliads, $hm) as $n_user => $lid_ids) {

      Lid::whereIn('id', $lid_ids)->update(['user_id' => $usersIds[$n_user], 'updated_at' => Now(), 'status_id' => 8, 'text' => '', 'qtytel' => 0]);
    }
    Log::whereIn('lid_id', $alliads)->delete();
    return response('All done', 200);
  }


  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function destroy($id)
  {
    //
  }


  public function reportCalls(Request $request)
  {
    $res = [];
    $data = $request->all();
    $dateFrom = $data['dateFrom'];
    $dateTo = $data['dateTo'];
    //SELECT COUNT(`operator`) 'alloperator',`operator` FROM `calls` WHERE DATE(`timecall`) BETWEEN '2024-05-09' AND '2024-05-09' GROUP BY `operator`;
    $sql = "SELECT `operator` name FROM `calls` WHERE DATE(`timecall`) BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "' GROUP BY `operator`";
    $res['operators'] = DB::select(DB::raw($sql));
    $sql = "SELECT `geo` name FROM `calls` WHERE geo != '' AND DATE(`timecall`) BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "' GROUP BY `geo`";
    $res['geos'] = DB::select(DB::raw($sql));

    $sql = "SELECT (SELECT COUNT(`geo`) allgeo FROM `calls` c1 WHERE geo != '' AND DATE (`timecall`) BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "' AND c1.geo = c2.geo AND c1.operator = c2.operator GROUP BY `operator`, geo) allgeo, SUM(`duration` = 0) badgeo, SUM(`duration` > 0 AND `duration` < 15) callgeo, SUM(`duration` >= 15) goodgeo, SUM(`duration` > 0) allgood, geo, `operator` NAME FROM `calls` c2 WHERE geo != '' AND DATE (`timecall`) BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "' GROUP BY `operator`, `geo` ORDER BY (SUM(`duration` > 0) / COUNT(*) * 100) DESC";
    //$sql = "SELECT (SELECT COUNT(`geo`) allgeo FROM `calls` c1 WHERE geo != '' AND DATE(`timecall`) BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "' AND c1.geo = c2.geo AND c1.operator = c2.operator GROUP BY `operator`, geo) allgeo, COUNT(geo) badgeo, geo,`operator` name FROM `calls` c2 WHERE `duration` = 0 AND geo != '' AND DATE(`timecall`) BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "' GROUP BY `operator`,`geo`";
    $res['geo'] = DB::select(DB::raw($sql));
    return $res;
  }
  public function reportManagerCalls(Request $request)
  {
    $res = $group = [];
    $data = $request->all();
    $dateFrom = $data['dateFrom'];
    $dateTo = $data['dateTo'];

    $res['users'] = DB::table('calls')->select('users.id', 'users.fio', 'users.group_id')->leftJoin('users', 'users.id', '=', 'calls.user_id')->whereBetween('timecall', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])->groupBy('user_id')->get();
    foreach ($res['users'] as  $user) {
      $group[] = $user->group_id;
    }
    array_unique($group);

    $res['groups'] = User::select('id', 'fio')->whereIn('id', $group)->get();

    //$sql = "SELECT `geo` name FROM `calls` WHERE geo != '' AND DATE(`timecall`) BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "' GROUP BY `geo`";
    //$res['geos'] = DB::select(DB::raw($sql));

    //$sql = "SELECT (SELECT COUNT(`geo`) allgeo FROM `calls` c1 WHERE geo != '' AND DATE (`timecall`) BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "' AND c1.geo = c2.geo AND c1.operator = c2.operator GROUP BY `operator`, geo) allgeo, SUM(`duration` = 0) badgeo, SUM(`duration` > 0 AND `duration` < 15) callgeo, SUM(`duration` >= 15) goodgeo, SUM(`duration` > 0) allgood, geo, `operator` NAME FROM `calls` c2 WHERE geo != '' AND DATE (`timecall`) BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "' GROUP BY `operator`, `geo` ORDER BY allgood DESC";
    $sql = "SELECT COUNT(*) allgeo, SUM(`duration` = 0) badgeo, SUM(`duration` > 0 AND `duration` < 15) callgeo, SUM(`duration` >= 15) goodgeo, SUM(`duration` > 0) allgood, user_id,users.fio, users.group_id FROM `calls` c2 left join users on (users.id = c2.user_id) WHERE geo != '' AND DATE (`timecall`) BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "' GROUP BY `user_id` ORDER BY (SUM(`duration` > 0) / COUNT(*) * 100) DESC";
    $res['geo'] = DB::select(DB::raw($sql));
    return $res;
  }
  public function getProfit(Request $request)
  {
    $res = [];
    $a_providers = [];
    $a_messages = [];
    $data = $request->all();
    $dateFrom = $data['dateFrom'];
    $dateTo = $data['dateTo'];
    $who = $data['who'];
    $type = $data['type'];



    $imports = DB::table('imports')->select('provider_id', 'cp', DB::raw("SUM(sum) as s"))->whereBetween('start', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])->when($type != '', function ($query) use ($type) {
      return $query->where('cp', $type);
    })->groupBy('provider_id');

    $a_messages = DB::table('imports')->whereBetween('start', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])->when($type != '', function ($query) use ($type) {
      return $query->where('cp', $type);
    })->pluck('message')->toArray();

    $message_provider = DB::table('imports_provider')->select(DB::Raw("concat(providers.name, DATE_FORMAT(date, ' %d-%m'), '(API)') as message"))->whereBetween('date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])->when($type != '', function ($query) use ($type) {
      return $query->where('cp', $type);
    })->leftJoin('providers', 'providers.id', '=', 'imports_provider.provider_id')->groupBy('message')->pluck('message')->toArray();
    $a_messages = array_filter(array_merge($a_messages, $message_provider));

    $imports_provider = DB::table('imports_provider')->select('provider_id', 'cp', DB::raw("SUM(sum) as s"))->whereBetween('date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])->when($type != '', function ($query) use ($type) {
      return $query->where('cp', $type);
    })->groupBy('provider_id');
    DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
    foreach ($imports->get() as $value) {
      $a_providers[] = $value->provider_id;
    }
    foreach ($imports_provider->get() as $value) {
      $a_providers[] = $value->provider_id;
    }
    $a_providers = array_unique($a_providers);
    if (count($a_providers) > 0) {
      $res['providers'] = DB::table('providers')->select('id', 'name', 'related_provider_ids')->whereIn('id', $a_providers)->orderBy('name')->get();
    }
    $where_date_d  = " AND created_at >= '" . $dateFrom . " 00:00:00' AND created_at <= '" . $dateTo . " 23:59:59'";
    $res['items'] = DB::table('lids')
      ->select(
        'lids.provider_id',
        DB::raw("ROUND(SUM((SELECT SUM(`depozit`) FROM `depozits` WHERE `lids`.`id` = `depozits`.`lid_id` " . $where_date_d . ")),2) as deposit"),
        DB::raw("SUM((SELECT count(`depozit`) FROM `depozits` WHERE `lids`.`id` = `depozits`.`lid_id` " . $where_date_d . ")) as dephm"),

        DB::raw("SUM(lids.pending) as pending"),
        DB::raw("'' as profit"),
        // 'lids.dep_reg',
        'sums.s',
        'sumsp.s as spp',
        'sums.cp',
        'sumsp.cp as cpp',
        DB::raw("COUNT(*) as hm")
      )
      ->leftJoinSub($imports, 'sums', function ($join) {
        $join->on('lids.provider_id', '=', 'sums.provider_id');
      })
      ->leftJoinSub($imports_provider, 'sumsp', function ($join) {
        $join->on('lids.provider_id', '=', 'sumsp.provider_id');
      })
      ->whereBetween('lids.created_at', ['' . $dateFrom . ' 00:00:00', '' . $dateTo . ' 23:59:59'])
      ->when($who != 0, function ($query) use ($who) {
        return $query->where('lids.dep_reg', $who);
      })
      ->when(count($a_messages) > 0, function ($query) use ($a_messages) {
        return $query->whereIn('lids.load_mess', $a_messages);
      })
      ->when(count($a_providers) > 0, function ($query) use ($a_providers) {
        return $query->whereIn('lids.provider_id', $a_providers);
      })
      ->groupBy('lids.provider_id')
      ->get();


    return $res;
  }
}
