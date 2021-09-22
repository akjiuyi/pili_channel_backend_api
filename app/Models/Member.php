<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Member extends Model
{
    protected $table = 'mzfk_member';
    protected $dateFormat = 'U';
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    //累计用户
    public static function getChannelMemberCount($channelId) {
        if ($channelId <= 0) return 0;
        return self::query()->where('channel_id', $channelId)->count();
    }


    //今日新增用户
    public static function getTodayMemberCountByChannelId($channelId) {
        if ($channelId <= 0) return 0;

        $startTime = strtotime(date('Y-m-d 00:00:00',time()));
        $endTime = strtotime(date('Y-m-d 23:59:59',time()));

        return self::query()->where('channel_id', $channelId)
                            ->where('create_time', '>=', $startTime)
                            ->where('create_time', '<=', $endTime)
                            ->count();
    }


    //今日活跃用户
    public static function getTodayActiveMemberCountByChannelId($channelId) {
        if ($channelId <= 0) return 0;

        $startTime = strtotime(date('Y-m-d 00:00:00',time()));
        $endTime = strtotime(date('Y-m-d 23:59:59',time()));

        return self::query()
            ->leftJoin('mzfk_member_event_log as record', 'record.member_id', 'mzfk_member.id')
            ->where('mzfk_member.channel_id', $channelId)
            ->where('record.create_time', '>=', $startTime)
            ->where('record.create_time', '<=', $endTime)
            ->select('record.member_id')
            ->distinct()
            ->count('record.member_id');

    }


    //苹果\安卓设备数
    public static function getDeviceCountByChannelId($channelId,$os='ios',$dataOptionValue=0,$startDate='',$endDate='') {
        if ($channelId <= 0) return 0;

        $query = parent::query()
                ->leftJoin('mzfk_member_account as account', 'account.id', 'mzfk_member.id')
                ->leftJoin('mzfk_member_order as order', 'order.member_id', 'mzfk_member.id')
                ->leftJoin('mzfk_member_event_log as record', 'record.member_id', 'mzfk_member.id')
                ->where('mzfk_member.channel_id', $channelId)
                ->where('account.register_os', $os);
                //->whereIn('order.type', [1,2]);

        if ($dataOptionValue) {
            if($dataOptionValue == 1){   //今天
                $startTime = strtotime(date('Y-m-d 00:00:00',time()));
                $endTime = strtotime(date('Y-m-d 23:59:59',time()));

                $query->Where(function ($query)use($startTime,$endTime) {
                    //$query->where([['order.type', 'in', [1,2]],['order.pay_state', '=', 2],['order.update_time', '>=', $startTime],['order.update_time', '<=', $endTime]]);
                    $query->where(function ($query)use($startTime,$endTime){
                        $query->whereIn('order.type', [1,2])
                              ->where([['order.pay_state', '=', 2],['order.update_time', '>=', $startTime],['order.update_time', '<=', $endTime]]);
                    });
                    $query->orWhere([['record.create_time', '>=', $startTime],['record.create_time', '<=', $endTime]]);
                });
            }else if($dataOptionValue == 2){  //昨天
                $startTime = strtotime(date('Y-m-d 00:00:00',strtotime("-1 day")));
                $endTime = strtotime(date('Y-m-d 23:59:59',strtotime("-1 day")));

                $query->Where(function ($query)use($startTime,$endTime) {
                    //$query->where([['order.type', 'in', [1,2]],['order.pay_state', '=', 2],['order.update_time', '>=', $startTime],['order.update_time', '<=', $endTime]]);
                    $query->where(function ($query)use($startTime,$endTime){
                        $query->whereIn('order.type', [1,2])
                            ->where([['order.pay_state', '=', 2],['order.update_time', '>=', $startTime],['order.update_time', '<=', $endTime]]);
                    });
                    $query->orWhere([['record.create_time', '>=', $startTime],['record.create_time', '<=', $endTime]]);
                });
            }
        }


        if($startDate&&!$endDate){
            $startTime = strtotime($startDate." 00:00:00");

            $query->where(function ($query)use($startTime) {
                $query->where(function ($query)use($startTime){
                    $query->whereIn('order.type', [1,2])
                        ->where([['order.pay_state', '=', 2],['order.update_time', '>=', $startTime]]);
                });
                $query->orWhere('record.create_time', '>=', $startTime);
            });

        }else if(!$startDate&&$endDate){
            $endTime = strtotime($endDate." 23:59:59");

            $query->where(function ($query)use($endTime) {
                $query->where(function ($query)use($endTime){
                    $query->whereIn('order.type', [1,2])
                        ->where([['order.pay_state', '=', 2],['order.update_time', '<=', $endTime]]);
                });
                $query->orWhere('record.create_time', '<=', $endTime);
            });
        }else if($startDate&&$endDate){
            $startTime = strtotime($startDate." 00:00:00");
            $endTime = strtotime($endDate." 23:59:59");

            $query->where(function ($query)use($startTime,$endTime) {
                $query->where(function ($query)use($startTime,$endTime){
                    $query->whereIn('order.type', [1,2])
                        ->where([['order.pay_state', '=', 2],['order.update_time', '>=', $startTime],['order.update_time', '<=', $endTime]]);
                });
                $query->orWhere([['record.create_time', '>=', $startTime],['record.create_time', '<=', $endTime]]);
            });
        }

        //$count = $query->toSql();
        //print_r($count);die;

        $count = $query->select('mzfk_member.id')
                    ->distinct('mzfk_member.id')
                    ->count('mzfk_member.id');

        return $count;
    }


    //充值数
    public static function getChargeCountByChannelId($channelId,$dataOptionValue,$startDate,$endDate) {
        if ($channelId <= 0) return 0;

        $query = parent::query()
            ->leftJoin('mzfk_member_order as order', 'order.member_id', 'mzfk_member.id')
            //->leftJoin('mzfk_member_event_log as record', 'record.member_id', 'mzfk_member.id')
            ->where('mzfk_member.channel_id', $channelId)
            ->whereIn('order.type', [1,2])
            ->where('order.pay_state', 2);


        if ($dataOptionValue) {
            if($dataOptionValue == 1){   //今天
                $startTime = strtotime(date('Y-m-d 00:00:00',time()));
                $endTime = strtotime(date('Y-m-d 23:59:59',time()));

                $query->where('order.update_time', '>=', $startTime);
                $query->where('order.update_time', '<=', $endTime);
            }else if($dataOptionValue == 2){  //昨天
                $startTime = strtotime(date('Y-m-d 00:00:00',strtotime("-1 day")));
                $endTime = strtotime(date('Y-m-d 23:59:59',strtotime("-1 day")));

                $query->where('order.update_time', '>=', $startTime);
                $query->where('order.update_time', '<=', $endTime);
            }
        }

        if($startDate){
            $startTime = strtotime($startDate." 00:00:00");
            $query->where('order.update_time', '>=', $startTime);
        }

        if($endDate){
            $endTime = strtotime($endDate." 23:59:59");
            $query->where('order.update_time', '<=', $endTime);
        }

        $charge_member_count = $query->select('mzfk_member.id')
                                     ->distinct()
                                     ->count('mzfk_member.id');

        $charge_times = $query->select('order.id')
                                ->distinct()
                                ->count('order.id');

        return ['charge_times'=>$charge_times,'charge_member_count'=>$charge_member_count];
    }


    //充值金额
    public static function getChargeAmountByChannelId($channelId,$dataOptionValue,$startDate,$endDate) {
        if ($channelId <= 0) return 0;

        $query = parent::query()
            ->leftJoin('mzfk_member_order as order', 'order.member_id', 'mzfk_member.id')
            //->leftJoin('mzfk_member_event_log as record', 'record.member_id', 'mzfk_member.id')
            ->where('mzfk_member.channel_id', $channelId)
            ->whereIn('order.type', [1,2])
            ->where('order.pay_state', 2);


        if ($dataOptionValue) {
            if($dataOptionValue == 1){   //今天
                $startTime = strtotime(date('Y-m-d 00:00:00',time()));
                $endTime = strtotime(date('Y-m-d 23:59:59',time()));

                $query->where('order.update_time', '>=', $startTime);
                $query->where('order.update_time', '<=', $endTime);
            }else if($dataOptionValue == 2){  //昨天
                $startTime = strtotime(date('Y-m-d 00:00:00',strtotime("-1 day")));
                $endTime = strtotime(date('Y-m-d 23:59:59',strtotime("-1 day")));

                $query->where('order.update_time', '>=', $startTime);
                $query->where('order.update_time', '<=', $endTime);
            }
        }

        if($startDate){
            $startTime = strtotime($startDate." 00:00:00");
            $query->where('order.update_time', '>=', $startTime);
        }

        if($endDate){
            $endTime = strtotime($endDate." 23:59:59");
            $query->where('order.update_time', '<=', $endTime);
        }


        /*$charge_amount =  $query->sum('trade_amount');*/
        /*$charge_member_count =  $query->select('order.id')
                                ->distinct('order.id')
                                ->sum('trade_amount');*/

        return $query->sum('trade_amount');

        //return $query->sum('trade_amount');
    }


    //渠道活跃人数
    public static function getChannelActiveMemberCount($channelId,$dataOptionValue,$startDate,$endDate) {
        if ($channelId <= 0) return 0;

        $query = parent::query()
            ->leftJoin('mzfk_member_event_log as record', 'record.member_id', 'mzfk_member.id')
            ->where('mzfk_member.channel_id', $channelId);

        if ($dataOptionValue) {
            if($dataOptionValue == 1){   //今天
                $startTime = strtotime(date('Y-m-d 00:00:00',time()));
                $endTime = strtotime(date('Y-m-d 23:59:59',time()));

                $query->where('record.create_time', '>=', $startTime);
                $query->where('record.create_time', '<=', $endTime);
            }else if($dataOptionValue == 2){  //昨天
                $startTime = strtotime(date('Y-m-d 00:00:00',strtotime("-1 day")));
                $endTime = strtotime(date('Y-m-d 23:59:59',strtotime("-1 day")));

                $query->where('record.create_time', '>=', $startTime);
                $query->where('record.create_time', '<=', $endTime);
            }
        }

        if($startDate){
            $startTime = strtotime($startDate." 00:00:00");
            $query->where('record.create_time', '>=', $startTime);
        }

        if($endDate){
            $endTime = strtotime($endDate." 23:59:59");
            $query->where('record.create_time', '<=', $endTime);
        }

        if($dataOptionValue||$startDate||$endDate){
            $active_member_count = $query->select('record.member_id')
                ->distinct()
                ->count('record.member_id');
        }else{
            $active_member_count = parent::query()
                ->where('mzfk_member.channel_id', $channelId)
                ->count();
        }


        return $active_member_count;
    }


    //渠道全部用户数
    public static function getChannelTotalMemberCount($channelId,$dataOptionValue,$startDate,$endDate) {
        if ($channelId <= 0) return 0;

        $query = parent::query()
            ->leftJoin('mzfk_member_event_log as record', 'record.member_id', 'mzfk_member.id')
            ->where('mzfk_member.channel_id', $channelId);

        if ($dataOptionValue) {
            if($dataOptionValue == 1){   //今天
                $startTime = strtotime(date('Y-m-d 00:00:00',time()));
                $endTime = strtotime(date('Y-m-d 23:59:59',time()));

                $query->where('record.create_time', '>=', $startTime);
                $query->where('record.create_time', '<=', $endTime);
            }else if($dataOptionValue == 2){  //昨天
                $startTime = strtotime(date('Y-m-d 00:00:00',strtotime("-1 day")));
                $endTime = strtotime(date('Y-m-d 23:59:59',strtotime("-1 day")));

                $query->where('record.create_time', '>=', $startTime);
                $query->where('record.create_time', '<=', $endTime);
            }
        }

        if($startDate){
            $startTime = strtotime($startDate." 00:00:00");
            $query->where('record.create_time', '>=', $startTime);
        }

        if($endDate){
            $endTime = strtotime($endDate." 23:59:59");
            $query->where('record.create_time', '<=', $endTime);
        }

        if($dataOptionValue||$startDate||$endDate){
            $total_member_count = $query->select('record.member_id')
                                        ->distinct()
                                        ->count('record.member_id');
        }else{
            $total_member_count = parent::query()->where('mzfk_member.channel_id', $channelId)
                                        ->count();
        }

        return $total_member_count;
    }


    //Vip用户数
    public static function getChannelVipMemberCount($channelId,$dataOptionValue,$startDate,$endDate) {
        if ($channelId <= 0) return 0;
        $query = parent::query()
            ->where('mzfk_member.channel_id', $channelId)
            ->where('mzfk_member.vip_level', 2);


        if ($dataOptionValue) {
            if($dataOptionValue == 1){   //今天
                $startTime = strtotime(date('Y-m-d 00:00:00',time()));
                $endTime = strtotime(date('Y-m-d 23:59:59',time()));

                $query->where('mzfk_member.create_time', '>=', $startTime);
                $query->where('mzfk_member.create_time', '<=', $endTime);
            }else if($dataOptionValue == 2){  //昨天
                $startTime = strtotime(date('Y-m-d 00:00:00',strtotime("-1 day")));
                $endTime = strtotime(date('Y-m-d 23:59:59',strtotime("-1 day")));

                $query->where('mzfk_member.create_time', '>=', $startTime);
                $query->where('mzfk_member.create_time', '<=', $endTime);
            }
        }

        if($startDate){
            $startTime = strtotime($startDate." 00:00:00");
            $query->where('mzfk_member.create_time', '>=', $startTime);
        }

        if($endDate){
            $endTime = strtotime($endDate." 23:59:59");
            $query->where('mzfk_member.create_time', '<=', $endTime);
        }


        return $query->count();
    }



    public static function getChannelUserLists_($channe_id, $dataOptionValue, $startDate, $endDate, $page, $pageSize){
        $query = parent::query()
            ->leftJoin('mzfk_member_order as order', 'order.member_id', 'mzfk_member.id')
            ->leftJoin('mzfk_member_account as account', 'account.id', 'mzfk_member.id')
            ->leftJoin('mzfk_app_product as product', 'product.id', 'order.product_id')
            //->whereIn('order.type', [1,2])
            ->where([['mzfk_member.channel_id', $channe_id]]);


        if ($dataOptionValue) {
            if($dataOptionValue == 1){   //今天
                $startTime = strtotime(date('Y-m-d 00:00:00',time()));
                $endTime = strtotime(date('Y-m-d 23:59:59',time()));

                $query->where('mzfk_member.create_time', '>=', $startTime);
                $query->where('mzfk_member.create_time', '<=', $endTime);
            }else if($dataOptionValue == 2){  //昨天
                $startTime = strtotime(date('Y-m-d 00:00:00',strtotime("-1 day")));
                $endTime = strtotime(date('Y-m-d 23:59:59',strtotime("-1 day")));

                $query->where('mzfk_member.create_time', '>=', $startTime);
                $query->where('mzfk_member.create_time', '<=', $endTime);
            }
        }


        if ($startDate) {
            $startTime = strtotime($startDate." 00:00:00");
            $query->where('mzfk_member.create_time', '>=', $startTime);
        }

        if ($endDate) {
            $endTime = strtotime($endDate." 23:59:59");
            $query->where('mzfk_member.create_time', '<=', $endTime);
        }

        $count = $query->count();

        $lists = $query->selectRaw('mzfk_member.id,mzfk_member.nickname,mzfk_member.create_time as m_create_time,mzfk_member.vip_level,mzfk_member.vip_expired,account.register_os,mzfk_member.state,order.trade_amount,order.order_no,order.type,order.real_amount,product.title,order.create_time')
            ->limit($pageSize)->offset(($page - 1) * $pageSize)->get();

        $data = [];
        foreach($lists as $info) {
            if($info->type == 1){
                $order_info = "购买时间：{$info->create_time}/购买会员：{$info->title}";
            }elseif($info->type == 2){
                $order_info = "购买时间：{$info->create_time}/购买金币：{$info->title}";
            }else{
                $order_info = "";
            }

            $vip_expired = '';
            if($info->vip_level > 1){
                $vip_expired = displayCreatedTime($info->vip_expired,'Y-m-d H:i:s');
            }

            $data[] = [
                'id' => $info->id,
                'nickname' => $info->nickname,
                'state' => match($info->state) { //1 正常 2 封禁
                    1 => '正常',
                    2 => '封禁',
                },
                'os' => match($info->register_os) { //1 安卓 2 苹果
                    'android' => '安卓',
                    'ios' => '苹果',
                },
                'trade_amount' => $info->trade_amount,
                'order_info' => $order_info,
                'create_time' => displayCreatedTime($info->m_create_time,'Y-m-d H:i:s'),
                'vip_expired' => $vip_expired
             ];
        }

        return [
            'total' => $count,
            'items' => $data
        ];
    }



    public static function getChannelUserLists($channe_id, $dataOptionValue, $startDate, $endDate, $page, $pageSize){
        $query = parent::query()
            ->leftJoin('mzfk_member_order as order', 'order.member_id', 'mzfk_member.id')
            ->leftJoin('mzfk_member_account as account', 'account.id', 'mzfk_member.id')
            ->leftJoin('mzfk_member_event_log as record', 'record.member_id', 'mzfk_member.id')
            //->leftJoin('mzfk_app_product as product', 'product.id', 'order.product_id')
            //->whereIn('order.type', [1,2])
            //->where('order.pay_state', 2)
            ->where([['mzfk_member.channel_id', $channe_id]]);


        $startTime=$endTime='';
        if ($dataOptionValue) {
            if($dataOptionValue == 1){   //今天
                $startTime = strtotime(date('Y-m-d 00:00:00',time()));
                $endTime = strtotime(date('Y-m-d 23:59:59',time()));

                $query->Where(function ($query)use($startTime,$endTime) {
                    $query->where(function ($query)use($startTime,$endTime){
                        $query->whereIn('order.type', [1,2])
                            ->where([['order.pay_state', '=', 2],['order.update_time', '>=', $startTime],['order.update_time', '<=', $endTime]]);
                    });
                    $query->orWhere([['record.create_time', '>=', $startTime],['record.create_time', '<=', $endTime]]);
                });
            }else if($dataOptionValue == 2){  //昨天
                $startTime = strtotime(date('Y-m-d 00:00:00',strtotime("-1 day")));
                $endTime = strtotime(date('Y-m-d 23:59:59',strtotime("-1 day")));

                $query->Where(function ($query)use($startTime,$endTime) {
                    $query->where(function ($query)use($startTime,$endTime){
                        $query->whereIn('order.type', [1,2])
                            ->where([['order.pay_state', '=', 2],['order.update_time', '>=', $startTime],['order.update_time', '<=', $endTime]]);
                    });

                    $query->orWhere([['record.create_time', '>=', $startTime],['record.create_time', '<=', $endTime]]);
                });
            }
        }

        if($startDate&&!$endDate){
            $startTime = strtotime($startDate." 00:00:00");

            $query->where(function ($query)use($startTime) {
                $query->where(function ($query)use($startTime){
                    $query->whereIn('order.type', [1,2])
                        ->where([['order.pay_state', '=', 2],['order.update_time', '>=', $startTime]]);
                });
                $query->orWhere('record.create_time', '>=', $startTime);
            });

        }else if(!$startDate&&$endDate){
            $endTime = strtotime($endDate." 23:59:59");

            $query->where(function ($query)use($endTime) {
                $query->where(function ($query)use($endTime){
                    $query->whereIn('order.type', [1,2])
                          ->where([['order.pay_state', '=', 2],['order.update_time', '<=', $endTime]]);
                });

                $query->orWhere('record.create_time', '<=', $endTime);
            });
        }else if($startDate&&$endDate){
            $startTime = strtotime($startDate." 00:00:00");
            $endTime = strtotime($endDate." 23:59:59");

            $query->where(function ($query)use($startTime,$endTime) {
                $query->where(function ($query)use($startTime,$endTime){
                    $query->whereIn('order.type', [1,2])
                          ->where([['order.pay_state', '=', 2],['order.update_time', '>=', $startTime],['order.update_time', '<=', $endTime]]);
                });
                $query->orWhere([['record.create_time', '>=', $startTime],['record.create_time', '<=', $endTime]]);
            });
        }

        //print_r($query->toSql());die;

        $count = $query->distinct('mzfk_member.id')->count();

        $lists = [];
        $member_lists = $query->selectRaw('mzfk_member.id,mzfk_member.nickname,mzfk_member.create_time as m_create_time,mzfk_member.vip_level,mzfk_member.vip_expired,account.register_os,mzfk_member.state')
                              ->orderBy('mzfk_member.id','desc')
                              ->distinct('mzfk_member.id')
                              ->limit($pageSize)
                              ->offset(($page - 1) * $pageSize)
                              ->get();

        foreach ($member_lists as $member) {
            $order_query = DB::table('mzfk_member_order')
                ->leftJoin('mzfk_app_product as product', 'product.id', 'mzfk_member_order.product_id')
                ->where('mzfk_member_order.member_id', $member->id)
                ->whereIn('mzfk_member_order.type', [1,2])
                ->where('mzfk_member_order.pay_state', 2);

            if($startTime&&!$endTime){
                $order_query->where('mzfk_member_order.update_time', '>=', $startTime);
            }elseif (!$startTime&&$endTime){
                $order_query->where('mzfk_member_order.update_time', '<=', $endTime);
            }elseif($startTime&&$endTime){
                $order_query->where('mzfk_member_order.update_time', '>=', $startTime);
                $order_query->where('mzfk_member_order.update_time', '<=', $endTime);
            }

            $res = $order_query->orderBy('mzfk_member_order.id','desc')
                ->select('mzfk_member_order.trade_amount','mzfk_member_order.order_no','mzfk_member_order.type','mzfk_member_order.real_amount','product.title','mzfk_member_order.update_time')
                ->first();

            $order_count = $order_query->count();
            $total_amout = $order_query->sum('trade_amount');

            if($res){
                $member->total_trade_amount = $total_amout;
                $member->order_no = $res->order_no;
                $member->type = $res->type;
                $member->real_amount = $res->real_amount;
                $member->title = $res->title;
                $member->update_time = $res->update_time;
                $member->order_count = $order_count;
            }

            $lists[] = $member;
        }


        $data = [];
        foreach($lists as $info) {
            if($info->type == 1){
                $order_info = [];
                $order_info['buy_time'] = "购买时间：{$info->update_time}";
                $order_info['buy_title'] = "购买会员：{$info->title}";
            }elseif($info->type == 2){
                $order_info['buy_time'] = "购买时间：{$info->update_time}";
                $order_info['buy_title'] = "购买金币：{$info->title}";
            }else{
                $info->trade_amount = '';
                $order_info = "";
            }

            //vip状态
            $is_vip = '否';
            $vip_expired = '';
            if($info->vip_level>1&&$info->vip_expired>time()){
                $is_vip = '是';
                $vip_expired = displayCreatedTime($info->vip_expired,'Y-m-d H:i:s');
            }


            //最近访问
            $last_access = '';
            if($startTime&&!$endTime){
                $create_time = DB::table('mzfk_member_event_log')
                    ->where('member_id', $info->id)
                    ->where('create_time','>=', $startTime)
                    ->orderBy('id','desc')
                    ->value('create_time');
            }else if(!$startTime&&$endTime){
                $create_time = DB::table('mzfk_member_event_log')
                    ->where('member_id', $info->id)
                    ->where('create_time','<=', $endTime)
                    ->orderBy('id','desc')
                    ->value('create_time');
            }else if($startTime&&$endTime){
                $create_time = DB::table('mzfk_member_event_log')
                    ->where('member_id', $info->id)
                    ->where('create_time','>=', $startTime)
                    ->where('create_time','<=', $endTime)
                    ->orderBy('id','desc')
                    ->value('create_time');

                if(!$create_time){
                    $create_time = DB::table('mzfk_member_event_log')
                        ->where('member_id', $info->id)
                        ->orderBy('id','desc')
                        ->value('create_time');
                }
            }else{
                $create_time = DB::table('mzfk_member_event_log')
                    ->where('member_id', $info->id)
                    ->orderBy('id','desc')
                    ->value('create_time');
            }

            if($create_time){
                $last_access = displayCreatedTime($create_time,'Y-m-d H:i:s');
            }


            $data[] = [
                'id' => $info->id,
                'nickname' => $info->nickname,
                'state' => $is_vip,
                'os' => match($info->register_os) { //1 安卓 2 苹果
                    'android' => '安卓',
                    'ios' => '苹果',
                },
                'trade_amount' => $info->total_trade_amount,   //订单总交易额
                'order_info' => $order_info,    //订单信息
                'order_count' => $info->order_count,   //订单数量
                'create_time' => displayCreatedTime($info->m_create_time,'Y-m-d H:i:s'),
                'vip_expired' => $vip_expired,
                'last_access' => $last_access
             ];
        }

        return [
            'total' => $count,
            'items' => $data
        ];
    }



    public static function getOrderLists($memberId, $dataOptionValue, $startDate, $endDate, $page, $pageSize){

        $startTime=$endTime='';
        if ($dataOptionValue) {
            if($dataOptionValue == 1){   //今天
                $startTime = strtotime(date('Y-m-d 00:00:00',time()));
                $endTime = strtotime(date('Y-m-d 23:59:59',time()));
            }else if($dataOptionValue == 2){  //昨天
                $startTime = strtotime(date('Y-m-d 00:00:00',strtotime("-1 day")));
                $endTime = strtotime(date('Y-m-d 23:59:59',strtotime("-1 day")));
            }
        }


        if($startDate&&!$endDate){
            $startTime = strtotime($startDate." 00:00:00");
        }else if(!$startDate&&$endDate){
            $endTime = strtotime($endDate." 23:59:59");

        }else if($startDate&&$endDate){
            $startTime = strtotime($startDate." 00:00:00");
            $endTime = strtotime($endDate." 23:59:59");
        }

        $order_query = DB::table('mzfk_member_order')
            ->leftJoin('mzfk_app_product as product', 'product.id', 'mzfk_member_order.product_id')
            ->where('mzfk_member_order.member_id', $memberId)
            ->whereIn('mzfk_member_order.type', [1,2])
            ->where('mzfk_member_order.pay_state', 2);

        if($startTime&&!$endTime){
            $order_query->where('mzfk_member_order.update_time', '>=', $startTime);
        }elseif (!$startTime&&$endTime){
            $order_query->where('mzfk_member_order.update_time', '<=', $endTime);
        }elseif($startTime&&$endTime){
            $order_query->where('mzfk_member_order.update_time', '>=', $startTime);
            $order_query->where('mzfk_member_order.update_time', '<=', $endTime);
        }

        $count = $order_query->count();

        $lists = $order_query->orderBy('mzfk_member_order.id','desc')
            ->select('mzfk_member_order.trade_amount','product.title','mzfk_member_order.update_time')
            ->limit($pageSize)
            ->offset(($page - 1) * $pageSize)
            ->get();

        foreach ($lists as $v){
            $v->update_time = date('Y-m-d H:i:s',$v->update_time);
        }

        return [
            'total' => $count,
            'lists' => $lists
        ];
    }



    public function incomeStatInfo(Request $request) {
        $channelInfo = $request->get('channelInfo');
        $channelInfo = Channel::getInfoById($channelInfo->id);
        $channelMemberCount = Member::getChannelMemberCount($channelInfo->id);      //发展用户
        //$channelAccountInfo = ChannelAccount::getInfoByChannelId($channelInfo->id);
        //$historyIncome = $channelAccountInfo ?-> total_income;
        $historyIncome = $channelInfo?->total_income;      //累计收益
        $balance = $channelInfo?->balance;                 //余额
        $historyWithdraw = $channelInfo?->total_withdraw;  //已提现
        $freezeWithdraw = MemberWithdrawRecord::getFreezeWithdrawByChannelId($channelInfo->id); //待提现
        $withdrawFeeRate = SystemConfig::GetVal("system.channel_withdraw.fee_rate") ?: 0.07;

        return $this->successJson([
            'channelMemberCount' => $channelMemberCount,
            'historyIncome' => $historyIncome,
            'balance' => $balance,
            'historyWithdraw' => $historyWithdraw,
            'freezeWithdraw' => $freezeWithdraw,
            'withdrawLimit' => MemberWithdrawRecord::CHANNEL_WITHDRAW_LIMIT,
            'withdrawFeeRate' => $withdrawFeeRate,
            'withdrawFeeRatePercent' => $withdrawFeeRate * 100 . '%',
            //'loanAccountInfo' => $channelInfo->loan_account_info ? json_decode($channelInfo->loan_account_info, true) : []
            'loanAccountInfo' => ['phone'=>$channelInfo?->phone,'bankName'=>$channelInfo?->bank,'contactName'=>$channelInfo?->contact,'bankAccount'=>$channelInfo?->account]
        ]);
    }

}
