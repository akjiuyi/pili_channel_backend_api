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

        $channelAllMemberCount = Member::getChannelMemberCount($channelInfo->id);                               //累计用户
        //$channelTodayMemberCount = Member::getTodayMemberCountByChannelId($channelInfo->id);                  //今日新增用户
        $channelAddMemberCount = Member::getAddMemberCountByChannelId($channelInfo->id,$dataOptionValue,$startDate,$endDate);    //新增用户

        //今日活跃用户
        $start_date_up = date("Y-m-d");
        $end_date_up = date("Y-m-d");
        $channelTodayActiveMemberCount = Member::getActiveMemberCountByChannelId($channelInfo->id,$start_date_up,$end_date_up);
        $channelTodayActiveMemberCount = array('date'=>date("m.d"),'active_count'=>$channelTodayActiveMemberCount);

        //昨日活跃用户
        $start_date_up = date("Y-m-d",strtotime('-1 day'));
        $end_date_up = date("Y-m-d",strtotime('-1 day'));

        $channelYesterdayActiveMemberCount = Member::getActiveMemberCountByChannelId($channelInfo->id,$start_date_up,$end_date_up);
        $channelYesterdayActiveMemberCount = array('date'=>date("m.d",strtotime('-1 day')),'active_count'=>$channelYesterdayActiveMemberCount);

        //本周活跃用户
        $start_date_up = date("Y-m-d",strtotime( 'this week Monday' ,time()));  //周一
        $end_date_up = date("Y-m-d");    //今天
        $channelWeekActiveMemberCount = Member::getActiveMemberCountByChannelId($channelInfo->id,$start_date_up,$end_date_up);
        $channelWeekActiveMemberCount = array('date'=>['monday'=>date("m.d",strtotime( 'this week Monday' ,time())),'today'=>date("m.d")],'active_count'=>$channelWeekActiveMemberCount);

        //本月活跃用户
        $start_date_up = date("Y-m-01");  //1日
        $end_date_up = date("Y-m-d");    //今天
        $channelMonthActiveMemberCount = Member::getActiveMemberCountByChannelId($channelInfo->id,$start_date_up,$end_date_up);
        $channelMonthActiveMemberCount = array('date'=>['first_day'=>date("m.01"),'today'=>date("m.d")],'active_count'=>$channelMonthActiveMemberCount);

        $channelIosDeviceCount = Member::getDeviceCountByChannelId($channelInfo->id,'ios',$dataOptionValue,$startDate,$endDate);          //苹果设备数
        $channelAndroidDeviceCount = Member::getDeviceCountByChannelId($channelInfo->id,'android',$dataOptionValue,$startDate,$endDate);  //安卓设备数
        $res = Member::getChargeCountByChannelId($channelInfo->id,$dataOptionValue,$startDate,$endDate);
        //$channelChargeCount = $res['charge_times'];  //充值次数
        $channelChargeMemberCount = $res['charge_member_count']??0;  //充值人数
        $channelChargeAmount = Member::getChargeAmountByChannelId($channelInfo->id,$dataOptionValue,$startDate,$endDate);                     //充值金额

        $channeTotalMemberCount = $channelIosDeviceCount + $channelAndroidDeviceCount;
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

        return $this->successJson([
            'channelAllMemberCount' => $channelAllMemberCount,
            //'channelTodayMemberCount' => $channelTodayMemberCount,
            'channelAddMemberCount' => $channelAddMemberCount,
            'channelTodayActiveMemberCount' => $channelTodayActiveMemberCount,   //今日活跃
            'channelYesterdayActiveMemberCount' => $channelYesterdayActiveMemberCount,   //昨日活跃用户
            'channelWeekActiveMemberCount' => $channelWeekActiveMemberCount,   //本周活跃用户
            'channelMonthActiveMemberCount' => $channelMonthActiveMemberCount,   //本月活跃用户
            'channelIosDeviceCount' => $channelIosDeviceCount,
            'channelAndroidDeviceCount' => $channelAndroidDeviceCount,
            'channelChargeCount' => $channelChargeMemberCount,
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
        //$channelTodayActiveMemberCount = Member::getTodayActiveMemberCountByChannelId($channelInfo->id);  //今日活跃用户

        //今日活跃用户
        $start_date_up = date("Y-m-d");
        $end_date_up = date("Y-m-d");
        $channelTodayActiveMemberCount = Member::getActiveMemberCountByChannelId($channelInfo->id,$start_date_up,$end_date_up);
        //$channelTodayActiveMemberCount = array('date'=>date("m.d"),'active_count'=>$channelTodayActiveMemberCount);


        $channelIosDeviceCount = Member::getDeviceCountByChannelId($channelInfo->id,'ios',$dataOptionValue,$startDate,$endDate);          //苹果设备数
        $channelAndroidDeviceCount = Member::getDeviceCountByChannelId($channelInfo->id,'android',$dataOptionValue,$startDate,$endDate);  //安卓设备数
        $res = Member::getChargeCountByChannelId($channelInfo->id,$dataOptionValue,$startDate,$endDate);
        //$channelChargeCount = $res['charge_times'];  //充值次数
        $channelChargeMemberCount = $res['charge_member_count']??0;  //充值人数
        $channelChargeAmount = Member::getChargeAmountByChannelId($channelInfo->id,$dataOptionValue,$startDate,$endDate);             //充值金额

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
            'channelAllMemberCount' => $channelAllMemberCount,
            'channelTodayMemberCount' => $channelTodayMemberCount,
            'channelTodayActiveMemberCount' => $channelTodayActiveMemberCount,
            'channelIosDeviceCount' => $channelIosDeviceCount,
            'channelAndroidDeviceCount' => $channelAndroidDeviceCount,
            'channelChargeCount' => $channelChargeMemberCount,
            'channelChargeAmount' => $channelChargeAmount,
            'channelChargeRate' => sprintf("%.2f",$channelChargeRate*100)."%",
            'channelAvgConsumption' => sprintf("%.2f",$channelAvgConsumption)
        ];

        $header = array('channelAllMemberCount'=>'累计用户','channelTodayMemberCount'=>'今日新增','channelTodayActiveMemberCount'=>'今日活跃'.date("m.d"),'channelIosDeviceCount'=>'苹果设备数量','channelAndroidDeviceCount'=>'安卓设备数量','channelChargeCount'=>'充值数量','channelChargeAmount'=>'充值金额','channelChargeRate'=>'充值比例','channelAvgConsumption'=>'人均消费');

        exportExcelV2($data, $header, 'FirstPageStatistics','渠道统计报表');
    }


    public function getMoreOrder(Request $request) {
        $page = $request->input('page') ?: 1;
        $pageSize = $request->input('pageSize') ?: 10;
        $dataOptionValue = (int) $request->input('dataOptionValue');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $memberId = $request->input('member_id');

        $data = Member::getOrderLists($memberId,$dataOptionValue, $startDate, $endDate, $page, $pageSize);

        return $this->successJson($data);
    }

}
