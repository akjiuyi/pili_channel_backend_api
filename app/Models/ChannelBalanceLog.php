<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelBalanceLog extends Model
{
    protected $table = 'mzfk_channel_balance_log';
    protected $dateFormat = 'U';
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    public static function getIncomeLists($nickname, $state, $productId, $paymentChannelId, $startDate, $endDate, $page = 1, $pageSize = 10) {
        $query = parent::query()
            ->leftJoin('mzfk_member_order as order', 'order.id', 'mzfk_channel_balance_log.refer_id')
            ->leftJoin('mzfk_member_payment_record as payment_record', 'order.id', 'payment_record.order_id')
            ->leftJoin('mzfk_member as member', 'order.member_id', 'member.id')
            ->where([['mzfk_channel_balance_log.type', 1]]);



        if ($state) {
            $query->where('order.state', $state);
        }
        if ($productId) {
            $query->where('order.product_id', $productId);
        }
        if ($paymentChannelId) {
            $query->where('payment_record.payment_channel_id', $paymentChannelId);
        }
        if ($nickname) {
            $query->where('member.nickname', 'like', '%' . $nickname . '%');
        }
        if ($startTime = strtotime($startDate)) {
            $query->where('mzfk_channel_balance_log.create_time', '>=', $startTime);
        }
        if ($endTime = strtotime($endDate)) {
            $query->where('mzfk_channel_balance_log.create_time', '<=', $endTime);
        }

        $lists = $query->selectRaw('mzfk_channel_balance_log.id,mzfk_channel_balance_log.change_balance,mzfk_channel_balance_log.create_time,
            member.nickname,payment_record.otn,payment_record.payment_channel_id,payment_record.amount,payment_record.order_id,order.product_id,order.state,order.member_id')
            ->limit($pageSize)->offset(($page - 1) * $pageSize)->get();



        $data = $products = $productIds = $paymentChannels = $paymentChannelIds = [];

        foreach($lists as $v) {
            $productIds[] = $v->product_id;
            $paymentChannelIds[] = $v->payment_channel_id;
        }

        $products = AppProduct::getSimpleListsByIds($productIds);
        $paymentChannels = PaymentChannel::getSimpleListsByIds($paymentChannelIds);

        foreach($lists as $info) {
            $productInfo = $products[$info->product_id] ?? null;
            $paymentChannelInfo = $paymentChannels[$info->payment_channel_id] ?? null;

            $data[] = [
                'orderId' => $info->order_id,
                'member_id' => $info->member_id,
                'otn' => $info->otn,
                'amount' => $info->amount,
                'income' => $info->change_balance,
                'createDate' => displayCreatedTime($info->create_time),
                'state' => $info->state,
                'stateDesc' => match($info->state) { //1 尚未支付 2 已经支付 3 支付失败 4已经发放
                    1    => '待支付',
                    2    => '已经支付',
                    3    => '支付失败',
                    4    => '已发放',
                    null => '未知'
                },
                'title' => isset($productInfo['title']) ? $productInfo['title'] : '',
                'paymentChannelName' => isset($paymentChannelInfo['title']) ? $paymentChannelInfo['title'] : '',
                'nickname' => $info->nickname
            ];
        }

        return [
            'total' => $query->count(),
            'items' => $data
        ];
    }

    public static function insertLog($channelId, $beforeBalance, $changeBalance, $afterBalance, $type, $referId, $desc) {
        $info = new self();
        $info->channel_id = $channelId;
        $info->before_balance = $beforeBalance;
        $info->change_balance = $changeBalance;
        $info->after_balance = $afterBalance;
        $info->type = $type;
        $info->refer_id = $referId;
        $info->desc = $desc;
        $info->refer_member_id = 0;
        return $info->save() ? $info : null;
    }
}
