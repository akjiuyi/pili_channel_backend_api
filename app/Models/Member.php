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

        $startTime = strtotime(date('Y-m-d 00:00:00'),time());
        $endTime = strtotime(date('Y-m-d 23:59:59'),time());

        return self::query()->where('channel_id', $channelId)
                            ->where('create_time', '>=', $startTime)
                            ->where('create_time', '<=', $endTime)
                            ->count();
    }


    //今日活跃用户
    public static function getTodayActiveMemberCountByChannelId($channelId) {
        if ($channelId <= 0) return 0;

        $startTime = strtotime(date('Y-m-d 00:00:00'),time());
        $endTime = strtotime(date('Y-m-d 23:59:59'),time());

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
                ->where('mzfk_member.channel_id', $channelId)
                ->where('account.register_os', $os);

        if ($dataOptionValue) {
            if($dataOptionValue == 1){   //今天
                $startTime = strtotime(date('Y-m-d 00:00:00'),time());
                $endTime = strtotime(date('Y-m-d 23:59:59'),time());

                $query->where('mzfk_member.create_time', '>=', $startTime);
                $query->where('mzfk_member.create_time', '<=', $endTime);
            }else if($dataOptionValue == 2){  //昨天
                $startTime = strtotime(date('Y-m-d 00:00:00'),strtotime("-1 day"));
                $endTime = strtotime(date('Y-m-d 23:59:59'),strtotime("-1 day"));

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


    //充值数
    public static function getChargeCountByChannelId($channelId,$dataOptionValue,$startDate,$endDate) {
        if ($channelId <= 0) return 0;

        $query = parent::query()
            ->leftJoin('mzfk_member_order as order', 'order.member_id', 'mzfk_member.id')
            ->where('mzfk_member.channel_id', $channelId)
            ->where('order.pay_state', 2);


        if ($dataOptionValue) {
            if($dataOptionValue == 1){   //今天
                $startTime = strtotime(date('Y-m-d 00:00:00'),time());
                $endTime = strtotime(date('Y-m-d 23:59:59'),time());

                $query->where('mzfk_member.create_time', '>=', $startTime);
                $query->where('mzfk_member.create_time', '<=', $endTime);
            }else if($dataOptionValue == 2){  //昨天
                $startTime = strtotime(date('Y-m-d 00:00:00'),strtotime("-1 day"));
                $endTime = strtotime(date('Y-m-d 23:59:59'),strtotime("-1 day"));

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

        $charge_member_count = $query->select('mzfk_member.id')
                                     ->distinct()
                                     ->count('mzfk_member.id');

        return ['charge_times'=>$query->count(),'charge_member_count'=>$charge_member_count];
    }


    //充值金额
    public static function getChargeAmountByChannelId($channelId,$dataOptionValue,$startDate,$endDate) {
        if ($channelId <= 0) return 0;

        $query = parent::query()
            ->leftJoin('mzfk_member_order as order', 'order.member_id', 'mzfk_member.id')
            ->where('mzfk_member.channel_id', $channelId)
            ->whereIn('order.type', [1,2])
            ->where('order.pay_state', 2);


        if ($dataOptionValue) {
            if($dataOptionValue == 1){   //今天
                $startTime = strtotime(date('Y-m-d 00:00:00'),time());
                $endTime = strtotime(date('Y-m-d 23:59:59'),time());

                $query->where('mzfk_member.create_time', '>=', $startTime);
                $query->where('mzfk_member.create_time', '<=', $endTime);
            }else if($dataOptionValue == 2){  //昨天
                $startTime = strtotime(date('Y-m-d 00:00:00'),strtotime("-1 day"));
                $endTime = strtotime(date('Y-m-d 23:59:59'),strtotime("-1 day"));

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


        /*$charge_amount =  $query->sum('trade_amount');
        $charge_member_count =  $query->select('mzfk_member.id')
                                ->distinct()
                                ->count('mzfk_member.id');*/

        return $query->sum('trade_amount');
    }


    //渠道活跃人数
    public static function getChannelActiveMemberCount($channelId,$dataOptionValue,$startDate,$endDate) {
        if ($channelId <= 0) return 0;

        $query = parent::query()
            ->leftJoin('mzfk_member_event_log as record', 'record.member_id', 'mzfk_member.id')
            ->where('mzfk_member.channel_id', $channelId);

        if ($dataOptionValue) {
            if($dataOptionValue == 1){   //今天
                $startTime = strtotime(date('Y-m-d 00:00:00'),time());
                $endTime = strtotime(date('Y-m-d 23:59:59'),time());

                $query->where('mzfk_member.create_time', '>=', $startTime);
                $query->where('mzfk_member.create_time', '<=', $endTime);
            }else if($dataOptionValue == 2){  //昨天
                $startTime = strtotime(date('Y-m-d 00:00:00'),strtotime("-1 day"));
                $endTime = strtotime(date('Y-m-d 23:59:59'),strtotime("-1 day"));

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
            ->where('mzfk_member.channel_id', $channelId);

        if ($dataOptionValue) {
            if($dataOptionValue == 1){   //今天
                $startTime = strtotime(date('Y-m-d 00:00:00'),time());
                $endTime = strtotime(date('Y-m-d 23:59:59'),time());

                $query->where('mzfk_member.create_time', '>=', $startTime);
                $query->where('mzfk_member.create_time', '<=', $endTime);
            }else if($dataOptionValue == 2){  //昨天
                $startTime = strtotime(date('Y-m-d 00:00:00'),strtotime("-1 day"));
                $endTime = strtotime(date('Y-m-d 23:59:59'),strtotime("-1 day"));

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


    //Vip用户数
    public static function getChannelVipMemberCount($channelId,$dataOptionValue,$startDate,$endDate) {
        if ($channelId <= 0) return 0;
        $query = parent::query()
            ->where('mzfk_member.channel_id', $channelId)
            ->where('mzfk_member.vip_level', 2);


        if ($dataOptionValue) {
            if($dataOptionValue == 1){   //今天
                $startTime = strtotime(date('Y-m-d 00:00:00'),time());
                $endTime = strtotime(date('Y-m-d 23:59:59'),time());

                $query->where('mzfk_member.create_time', '>=', $startTime);
                $query->where('mzfk_member.create_time', '<=', $endTime);
            }else if($dataOptionValue == 2){  //昨天
                $startTime = strtotime(date('Y-m-d 00:00:00'),strtotime("-1 day"));
                $endTime = strtotime(date('Y-m-d 23:59:59'),strtotime("-1 day"));

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
                $startTime = strtotime(date('Y-m-d 00:00:00'),time());
                $endTime = strtotime(date('Y-m-d 23:59:59'),time());

                $query->where('mzfk_member.create_time', '>=', $startTime);
                $query->where('mzfk_member.create_time', '<=', $endTime);
            }else if($dataOptionValue == 2){  //昨天
                $startTime = strtotime(date('Y-m-d 00:00:00'),strtotime("-1 day"));
                $endTime = strtotime(date('Y-m-d 23:59:59'),strtotime("-1 day"));

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
            //->leftJoin('mzfk_member_order as order', 'order.member_id', 'mzfk_member.id')
            ->leftJoin('mzfk_member_account as account', 'account.id', 'mzfk_member.id')
            //->leftJoin('mzfk_app_product as product', 'product.id', 'order.product_id')
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

        $lists = [];
        /*$query->selectRaw('mzfk_member.id,mzfk_member.nickname,mzfk_member.create_time as m_create_time,mzfk_member.vip_level,mzfk_member.vip_expired,account.register_os,mzfk_member.state')
            ->limit($pageSize)->offset(($page - 1) * $pageSize)->chunk(100, function ($users) use(&$lists){
                foreach ($users as $user) {
                    $res = DB::table('mzfk_member_order')
                        ->leftJoin('mzfk_app_product as product', 'product.id', 'mzfk_member_order.product_id')
                        ->where('mzfk_member_order.member_id', $user->id)
                        ->orderBy('mzfk_member_order.id','desc')
                        ->select('mzfk_member_order.trade_amount','mzfk_member_order.order_no','mzfk_member_order.type','mzfk_member_order.real_amount','product.title','mzfk_member_order.create_time')
                        ->first();


                    if($res){
                        $user->trade_amount = $res->trade_amount;
                        $user->order_no = $res->order_no;
                        $user->type = $res->type;
                        $user->real_amount = $res->real_amount;
                        $user->title = $res->title;
                        $user->create_time = $res->create_time;
                    }

                    $lists[] = $user;
                }
            });*/

        $member_lists = $query->selectRaw('mzfk_member.id,mzfk_member.nickname,mzfk_member.create_time as m_create_time,mzfk_member.vip_level,mzfk_member.vip_expired,account.register_os,mzfk_member.state')
                              ->limit($pageSize)
                              ->offset(($page - 1) * $pageSize)
                              ->get();


        foreach ($member_lists as $member) {
            $res = DB::table('mzfk_member_order')
                ->leftJoin('mzfk_app_product as product', 'product.id', 'mzfk_member_order.product_id')
                ->where('mzfk_member_order.member_id', $member->id)
                ->orderBy('mzfk_member_order.id','desc')
                ->select('mzfk_member_order.trade_amount','mzfk_member_order.order_no','mzfk_member_order.type','mzfk_member_order.real_amount','product.title','mzfk_member_order.create_time')
                ->first();


            if($res){
                $member->trade_amount = $res->trade_amount;
                $member->order_no = $res->order_no;
                $member->type = $res->type;
                $member->real_amount = $res->real_amount;
                $member->title = $res->title;
                $member->create_time = $res->create_time;
            }

            $lists[] = $member;
        }


        $data = [];
        foreach($lists as $info) {
            if($info->type == 1){
                $order_info = "购买时间：{$info->create_time}/购买会员：{$info->title}";
            }elseif($info->type == 2){
                $order_info = "购买时间：{$info->create_time}/购买金币：{$info->title}";
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


            //vip到期时间
            /*$vip_expired = '';
            if($info->vip_level > 1){
                $vip_expired = displayCreatedTime($info->vip_expired,'Y-m-d H:i:s');
            }*/

            //最近访问
            $last_access = '';
            $create_time = DB::table('mzfk_member_event_log')
                ->where('member_id', $info->id)
                ->orderBy('id','desc')
                ->value('create_time');

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
                'trade_amount' => $info->trade_amount,
                'order_info' => $order_info,
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
