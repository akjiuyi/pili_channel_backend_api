<?php
/**
 * Created by PhpStorm.
 * User: yy
 * Date: 2021/6/4
 * Time: 11:24
 */

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Member;
use Illuminate\Http\Request;


class TestController extends Controller
{
    public function index(Request $request) {


        /*$startTime = strtotime(date('Y-m-d 00:00:00'),time());
        $endTime = strtotime(date('Y-m-d 23:59:59'),time());

        echo $startTime."--".$endTime."<br/>";


        $startTime = strtotime(date('Y-m-d 00:00:00'),strtotime("-1 day"));
        $endTime = strtotime(date('Y-m-d 23:59:59'),strtotime("-1 day"));

        echo $startTime."--".$endTime."<br/>";die;*/


        $startTime = date('Y-m-d 00:00:00',time());
        $endTime = date('Y-m-d 23:59:59',time());

        echo $startTime."--".$endTime."<br/>";


        $startTime = date('Y-m-d 00:00:00',strtotime("-1 day"));
        $endTime = date('Y-m-d 23:59:59',strtotime("-1 day"));

        echo $startTime."--".$endTime."<br/>";die;



        echo date('Y-m-d H:i:s');die;

        $s = \Illuminate\Support\Facades\Cache::put('testexpire', 1111, 4);

        var_dump($s);echo PHP_EOL;
    }


    public function exportFirstPageStatistics(Request $request) {

        $dataOptionValue = (int) $request->input('dataOptionValue');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $channel_id = $request->input('channel_id');
        if(!$channel_id){
            die(参数错误);
        }

        $channelAllMemberCount = Member::getChannelMemberCount($channel_id);                         //累计用户
        $channelTodayMemberCount = Member::getTodayMemberCountByChannelId($channel_id);              //今日新增用户
        //$channelTodayActiveMemberCount = Member::getTodayActiveMemberCountByChannelId($channel_id);  //今日活跃用户

        //今日活跃用户
        $start_date_up = date("Y-m-d");
        $end_date_up = date("Y-m-d");
        $channelTodayActiveMemberCount = Member::getActiveMemberCountByChannelId($channel_id,$start_date_up,$end_date_up);

        $channelIosDeviceCount = Member::getDeviceCountByChannelId($channel_id,'ios',$dataOptionValue,$startDate,$endDate);          //苹果设备数
        $channelAndroidDeviceCount = Member::getDeviceCountByChannelId($channel_id,'android',$dataOptionValue,$startDate,$endDate);  //安卓设备数
        $res = Member::getChargeCountByChannelId($channel_id,$dataOptionValue,$startDate,$endDate);
        //$channelChargeCount = $res['charge_times'];  //充值次数
        $channelChargeMemberCount = $res['charge_member_count']??0;  //充值人数
        $channelChargeAmount = Member::getChargeAmountByChannelId($channel_id,$dataOptionValue,$startDate,$endDate);             //充值金额

        //$channeActiveMemberCount = Member::getChannelActiveMemberCount($channelInfo->id,$dataOptionValue,$startDate,$endDate);        //活跃人数
        $channeTotalMemberCount = $channelIosDeviceCount + $channelAndroidDeviceCount;


        //$channelTotalMemberCount = Member::getChannelTotalMemberCount($channelInfo->id,$dataOptionValue,$startDate,$endDate);         //累计用户

        if($channeTotalMemberCount == 0){
            $channelChargeRate = 0;
        }else{
            $channelChargeRate = $channelChargeMemberCount/$channeTotalMemberCount;  //充值比例
        }

        if($channelChargeMemberCount == 0){
            $channelAvgConsumption = 0;  //人均消费
        }else{
            $channelAvgConsumption = $channelChargeAmount/$channeTotalMemberCount;  //人均消费
        }

        $data = [
                    [
                        'channelAllMemberCount' => $channelAllMemberCount,
                        'channelTodayMemberCount' => $channelTodayMemberCount,
                        'channelTodayActiveMemberCount' => $channelTodayActiveMemberCount,
                        'channelIosDeviceCount' => $channelIosDeviceCount,
                        'channelAndroidDeviceCount' => $channelAndroidDeviceCount,
                        'channelChargeCount' => $channelChargeMemberCount,
                        'channelChargeAmount' => $channelChargeAmount,
                        'channelChargeRate' => sprintf("%.2f",$channelChargeRate*100)."%",
                        'channelAvgConsumption' => sprintf("%.2f",$channelAvgConsumption)
                    ]
                ];

        $header = array('channelAllMemberCount'=>'累计用户','channelTodayMemberCount'=>'今日新增'.date("m.d"),'channelTodayActiveMemberCount'=>'今日活跃'.date("m.d"),'channelIosDeviceCount'=>'苹果设备数量','channelAndroidDeviceCount'=>'安卓设备数量','channelChargeCount'=>'充值数量','channelChargeAmount'=>'充值金额','channelChargeRate'=>'充值比例','channelAvgConsumption'=>'人均消费');

        exportExcelV2($data, $header, '渠道统计报表','渠道统计报表');
    }
}


