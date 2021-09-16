<?php
/**
 * Created by PhpStorm.
 * User: yy
 * Date: 2021/6/10
 * Time: 17:08
 */

namespace App\Models;


use App\Exceptions\ServiceException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MemberWithdrawRecord extends Model
{
    protected $table = 'mzfk_member_withdraw_record';
    protected $dateFormat = 'U';
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    const CHANNEL_WITHDRAW_LIMIT = 50; // 提现条件

    public static function getFreezeWithdrawByChannelId($channelId) {
        if ($channelId <= 0) return 0;
        return parent::query()->where('member_type', 2)->where('member_id', $channelId)->where('withdraw_state', 1)->sum('withdraw_amount');
    }

    public static function applyWithdraw($channelId, $amount, $loanAccountInfo) :self{
        $amount = round($amount, 2);
        //$fee_rate = SystemConfig::GetVal("system.channel_withdraw.fee_rate", true, 60);
        $fee_rate = SystemConfig::GetVal("system.channel_withdraw.fee_rate") ?: 0.07;
        try{
            DB::beginTransaction();
            $channelInfo = Channel::getInfoByChannelIdForLock($channelId);
            if ($channelInfo->balance < $amount) {
                throw new ServiceException('余额不足');
            }
            $balance = $channelInfo->balance;
            $channelInfo->balance = $channelInfo->balance - $amount;
            $channelRes = $channelInfo->save();
            if (!$channelRes) {
                throw new ServiceException('修改余额失败');
            }

            $recordInfo = self::insertLog($channelId, $amount, round($amount * $fee_rate, 2), $loanAccountInfo);
            ChannelBalanceLog::insertLog($channelId, $balance, -$amount, $channelInfo->balance, 2, $recordInfo->id, "申请提现{$amount},余额扣减");
            DB::commit();

            return $recordInfo;
        }catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException($e instanceof ServiceException ? $e->getMessage() : '操作失败');
        }
    }

    public static function insertLog($channelId, $withdrawAmount, $withdrawFee, $loanAccountInfo = '', $memberType =2 , $withdrawState = 1) {
        $loanAccountInfo = json_decode($loanAccountInfo,true);

        $info = new self();
        $info->member_id = $channelId;
        $info->withdraw_amount = $withdrawAmount;
        $info->withdraw_fee = $withdrawFee;
        $info->withdraw_state = $withdrawState;
        $info->mcl_id = 0;
        $info->type = $loanAccountInfo['type'];
        $info->bank = $loanAccountInfo['bankName'];
        $info->card_no = $loanAccountInfo['bankAccount'];
        $info->holder_name = $loanAccountInfo['contactName'];
        $info->member_type = $memberType;
        //$info->loan_account_info = $loanAccountInfo;
        return $info->save() ? $info : null;
    }
}
