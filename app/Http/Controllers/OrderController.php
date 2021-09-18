<?php
namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\ChannelAccount;
use App\Models\ChannelBalanceLog;
use App\Models\Member;
use App\Models\MemberWithdrawRecord;
use App\Models\SystemConfig;
use Illuminate\Http\Request;


class OrderController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }


    public function incomeLists(Request $request) {
        $page = $request->input('page') ?: 1;
        $pageSize = $request->input('pageSize') ?: 10;
        $nickname = trim($request->input('nickname'));
        $state = (int) $request->input('state');
        $productId = (int) $request->input('productId');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $paymentChannelId = (int) $request->input('paymentChannelId');


        $channelInfo = $request->get('channelInfo');
        $lists = ChannelBalanceLog::getIncomeLists($channelInfo->id,$nickname, $state, $productId, $paymentChannelId, $startDate, $endDate, $page, $pageSize);


        return $this->successJson($lists);
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

    /**
     * 申请提现
     */
    public function applyWithdraw(Request $request) {
        $channelInfo = $request->get('channelInfo');
        $channelId = $channelInfo->id;
        $channelInfo = Channel::getInfoById($channelInfo->id);
        $money = $request->input('money');
        if ($money < MemberWithdrawRecord::CHANNEL_WITHDRAW_LIMIT) {
            return $this->errorJson("提现金额必须大于".MemberWithdrawRecord::CHANNEL_WITHDRAW_LIMIT);
        }
        //MemberWithdrawRecord::applyWithdraw($channelId, $money, $channelInfo->loan_account_info);
        $loan_account_info = ['type'=>$channelInfo?->withdraw_type,'phone'=>$channelInfo?->phone,'bankName'=>$channelInfo?->bank,'contactName'=>$channelInfo?->contact,'bankAccount'=>$channelInfo?->account];

        MemberWithdrawRecord::applyWithdraw($channelId, $money, json_encode($loan_account_info));

        return $this->successJson(['message' => '提现申请成功']);
    }


}
