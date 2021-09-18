<?php
namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Member;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function info(Request $request) {
        $channelInfo = $request->get('channelInfo');
        return $this->successJson([
            'username' => $channelInfo->nickname,
            'avatar' => '',
            'phone' => $channelInfo->phone,
            'lastLoginDate' => displayCreatedTime($channelInfo->last_login_time)
        ]);
    }

    public function resetPwd(Request $request) {
        $channelInfo = $request->get('channelInfo');
        $password = trim($request->input('password'));
        $newPassword = trim($request->input('newPassword'));
        Channel::resetPwd($channelInfo->id, $password, $newPassword);
        return $this->successJson(['message' => '修改成功', 'resetToken' => true]);
    }


    public function channelUserLists(Request $request) {
        $page = $request->input('page') ?: 1;
        $pageSize = $request->input('pageSize') ?: 10;
        $dataOptionValue = (int) $request->input('dataOptionValue');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        $channelInfo = $request->get('channelInfo');

        $lists = Member::getChannelUserLists($channelInfo->id, $dataOptionValue, $startDate, $endDate, $page, $pageSize);


        return $this->successJson($lists);
    }


    public function summaryInfo(Request $request) {
        $dataOptionValue = (int) $request->input('dataOptionValue');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        $channelInfo = $request->get('channelInfo');
        //$channelInfo = Channel::getInfoById($channelInfo->id);

        $channelAllMemberCount = Member::getChannelMemberCount($channelInfo->id);                         //累计用户
        $channelTodayMemberCount = Member::getTodayMemberCountByChannelId($channelInfo->id);              //今日新增用户
        $channelTodayActiveMemberCount = Member::getTodayActiveMemberCountByChannelId($channelInfo->id);  //今日活跃用户

        $channelIosDeviceCount = Member::getDeviceCountByChannelId($channelInfo->id,'ios',$dataOptionValue,$startDate,$endDate);          //苹果设备数
        $channelAndroidDeviceCount = Member::getDeviceCountByChannelId($channelInfo->id,'android',$dataOptionValue,$startDate,$endDate);  //安卓设备数
        $res = Member::getChargeCountByChannelId($channelInfo->id,$dataOptionValue,$startDate,$endDate);
        $channelChargeCount = $res['charge_times'];  //充值次数
        //$channelChargeMemberCount = $res['charge_member_count'];  //充值人数
        $channelChargeAmount = Member::getChargeAmountByChannelId($channelInfo->id,$dataOptionValue,$startDate,$endDate);             //充值金额

        //$channeGeneralMemberCount = Member::getChannelGeneralMemberCount($channelInfo->id,$dataOptionValue,$startDate,$endDate);    //普通用户数
        $channeVipMemberCount = Member::getChannelVipMemberCount($channelInfo->id,$dataOptionValue,$startDate,$endDate);              //Vip用户数
        $channelTotalMemberCount = Member::getChannelTotalMemberCount($channelInfo->id,$dataOptionValue,$startDate,$endDate);         //累计用户

        if($channelTotalMemberCount == 0){
            $channelChargeRate = 0;
            $channelAvgConsumption = 0;
        }else{
            $channelChargeRate = $channeVipMemberCount/$channelTotalMemberCount;  //充值比例
            $channelAvgConsumption = $channelChargeAmount/$channelTotalMemberCount;  //人均消费
        }

        return $this->successJson([
            'channelAllMemberCount' => $channelAllMemberCount,
            'channelTodayMemberCount' => $channelTodayMemberCount,
            'channelTodayActiveMemberCount' => $channelTodayActiveMemberCount,
            'channelIosDeviceCount' => $channelIosDeviceCount,
            'channelAndroidDeviceCount' => $channelAndroidDeviceCount,
            'channelChargeCount' => $channelChargeCount,
            'channelChargeAmount' => $channelChargeAmount,
            'channelChargeRate' => sprintf("%.2f",$channelChargeRate*100)."%",
            'channelAvgConsumption' => sprintf("%.2f",$channelAvgConsumption),
            'channelId' => $channelInfo->id
        ]);
    }


    public function exportFirstPageStatistics(Request $request) {
        $dataOptionValue = (int) $request->input('dataOptionValue');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        $channelInfo = $request->get('channelInfo');
        $channelInfo = Channel::getInfoById($channelInfo->id);

        $channelAllMemberCount = Member::getChannelMemberCount($channelInfo->id);                         //累计用户
        $channelTodayMemberCount = Member::getTodayMemberCountByChannelId($channelInfo->id);              //今日新增用户
        $channelTodayActiveMemberCount = Member::getTodayActiveMemberCountByChannelId($channelInfo->id);  //今日活跃用户

        $channelIosDeviceCount = Member::getDeviceCountByChannelId($channelInfo->id,'ios',$dataOptionValue,$startDate,$endDate);          //苹果设备数
        $channelAndroidDeviceCount = Member::getDeviceCountByChannelId($channelInfo->id,'android',$dataOptionValue,$startDate,$endDate);  //安卓设备数
        $res = Member::getChargeCountByChannelId($channelInfo->id,$dataOptionValue,$startDate,$endDate);
        $channelChargeCount = $res['charge_times'];  //充值次数
        //$channelChargeMemberCount = $res['charge_member_count'];  //充值人数
        $channelChargeAmount = Member::getChargeAmountByChannelId($channelInfo->id,$dataOptionValue,$startDate,$endDate);             //充值金额

        //$channeGeneralMemberCount = Member::getChannelGeneralMemberCount($channelInfo->id,$dataOptionValue,$startDate,$endDate);    //普通用户数
        $channeVipMemberCount = Member::getChannelVipMemberCount($channelInfo->id,$dataOptionValue,$startDate,$endDate);              //Vip用户数
        $channelTotalMemberCount = Member::getChannelTotalMemberCount($channelInfo->id,$dataOptionValue,$startDate,$endDate);         //累计用户

        if($channelTotalMemberCount == 0){
            $channelChargeRate = 0;
            $channelAvgConsumption = 0;
        }else{
            $channelChargeRate = $channeVipMemberCount/$channelTotalMemberCount;  //充值比例
            $channelAvgConsumption = $channelChargeAmount/$channelTotalMemberCount;  //人均消费
        }

        $data = [
            'channelAllMemberCount' => $channelAllMemberCount,
            'channelTodayMemberCount' => $channelTodayMemberCount,
            'channelTodayActiveMemberCount' => $channelTodayActiveMemberCount,
            'channelIosDeviceCount' => $channelIosDeviceCount,
            'channelAndroidDeviceCount' => $channelAndroidDeviceCount,
            'channelChargeCount' => $channelChargeCount,
            'channelChargeAmount' => $channelChargeAmount,
            'channelChargeRate' => sprintf("%.2f",$channelChargeRate*100)."%",
            'channelAvgConsumption' => sprintf("%.2f",$channelAvgConsumption)
        ];

        $header = array('channelAllMemberCount'=>'累计用户','channelTodayMemberCount'=>'今日新增','channelTodayActiveMemberCount'=>'今日活跃','channelIosDeviceCount'=>'苹果设备数量','channelAndroidDeviceCount'=>'安卓设备数量','channelChargeCount'=>'充值数量','channelChargeAmount'=>'充值金额','channelChargeRate'=>'充值比例','channelAvgConsumption'=>'人均消费');

        exportExcelV2($data, $header, 'FirstPageStatistics','渠道统计报表');
    }


}
