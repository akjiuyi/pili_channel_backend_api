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
        $channelTodayActiveMemberCount = Member::getTodayActiveMemberCountByChannelId($channel_id);  //今日活跃用户

        $channelIosDeviceCount = Member::getDeviceCountByChannelId($channel_id,'ios',$dataOptionValue,$startDate,$endDate);          //苹果设备数
        $channelAndroidDeviceCount = Member::getDeviceCountByChannelId($channel_id,'android',$dataOptionValue,$startDate,$endDate);  //安卓设备数
        $res = Member::getChargeCountByChannelId($channel_id,$dataOptionValue,$startDate,$endDate);
        $channelChargeCount = $res['charge_times'];  //充值次数
        //$channelChargeMemberCount = $res['charge_member_count'];  //充值人数
        $channelChargeAmount = Member::getChargeAmountByChannelId($channel_id,$dataOptionValue,$startDate,$endDate);             //充值金额

        //$channeGeneralMemberCount = Member::getChannelGeneralMemberCount($channelInfo->id,$dataOptionValue,$startDate,$endDate);    //普通用户数
        $channeVipMemberCount = Member::getChannelVipMemberCount($channel_id,$dataOptionValue,$startDate,$endDate);              //Vip用户数
        $channelTotalMemberCount = Member::getChannelTotalMemberCount($channel_id,$dataOptionValue,$startDate,$endDate);         //累计用户

        if($channelTotalMemberCount == 0){
            $channelChargeRate = 0;
            $channelAvgConsumption = 0;
        }else{
            $channelChargeRate = $channeVipMemberCount/$channelTotalMemberCount;  //充值比例
            $channelAvgConsumption = $channelChargeAmount/$channelTotalMemberCount;  //人均消费
        }

        $data = [
                    [
                        'channelAllMemberCount' => $channelAllMemberCount,
                        'channelTodayMemberCount' => $channelTodayMemberCount,
                        'channelTodayActiveMemberCount' => $channelTodayActiveMemberCount,
                        'channelIosDeviceCount' => $channelIosDeviceCount,
                        'channelAndroidDeviceCount' => $channelAndroidDeviceCount,
                        'channelChargeCount' => $channelChargeCount,
                        'channelChargeAmount' => $channelChargeAmount,
                        'channelChargeRate' => sprintf("%.2f",$channelChargeRate*100)."%",
                        'channelAvgConsumption' => sprintf("%.2f",$channelAvgConsumption)
                    ]
                ];

        $header = array('channelAllMemberCount'=>'累计用户','channelTodayMemberCount'=>'今日新增','channelTodayActiveMemberCount'=>'今日活跃','channelIosDeviceCount'=>'苹果设备数量','channelAndroidDeviceCount'=>'安卓设备数量','channelChargeCount'=>'充值数量','channelChargeAmount'=>'充值金额','channelChargeRate'=>'充值比例','channelAvgConsumption'=>'人均消费');

        exportExcelV2($data, $header, '渠道统计报表','渠道统计报表');
    }
}
