<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberOrder extends Model
{
    protected $table = 'mzfk_member_order as order';
    protected $dateFormat = 'U';
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';


    public static function getIncomeLists($channel_id, $nickname, $state, $productId, $paymentChannelId, $startDate, $endDate, $page = 1, $pageSize = 10) {
        $query = parent::query()
            //->leftJoin('mzfk_member_order as order', 'order.id', 'mzfk_channel_balance_log.refer_id')
            //->leftJoin('mzfk_member_payment_record as payment_record', 'order.id', 'payment_record.order_id')
            ->leftJoin('mzfk_member as member', 'order.member_id', 'member.id')
            ->leftJoin('mzfk_app_product as product', 'order.product_id', 'product.id')
            //->where([['member.channel_id', $channel_id],['order.type','in', [1,2]]]);
            ->where([['member.channel_id', $channel_id]]);


        if ($state) {
            $query->where('order.pay_state', $state);
        }

        if ($productId) {
            $query->where('order.product_id', $productId);
        }

        if ($paymentChannelId) {
            //$query->where('payment_record.payment_channel_id', $paymentChannelId);
            $query->where('order.channel_id', $paymentChannelId);
        }

        if ($nickname) {
            $query->where('member.nickname', 'like', '%'.$nickname.'%');
        }

        if ($startDate) {
            $startTime = strtotime($startDate." 00:00:00");
            $query->where('order.update_time', '>=', $startTime);
        }

        if($endDate){
            $endTime = strtotime($endDate." 23:59:59");
            $query->where('order.update_time', '<=', $endTime);
        }

        $count = $query->count();

        $lists = $query->selectRaw('order.id,order.order_no,order.member_id,order.product_id,order.commission,member.nickname,product.discount_price,order.channel_id,order.pay_state,order.create_time,order.update_time')
            ->orderBy('order.update_time','desc')
            ->limit($pageSize)
            ->offset(($page - 1) * $pageSize)
            ->get();

        $data = $products = $productIds = $paymentChannels = $paymentChannelIds = [];

        foreach($lists as $v) {
            $productIds[] = $v->product_id;
            $paymentChannelIds[] = $v->channel_id;
        }

        $products = AppProduct::getSimpleListsByIds($productIds);
        $paymentChannels = PaymentChannel::getSimpleListsByIds($paymentChannelIds);
        //print_r($paymentChannels);die;

        foreach($lists as $info) {
            $productInfo = $products[$info->product_id] ?? null;
            $paymentChannelInfo = $paymentChannels[$info->channel_id] ?? null;

            try{
                $commission = json_decode($info->commission);
                $income = $commission->channel;
            }catch (\Exception $e) {
                $income = 0;
            }

            if($info->pay_state != 2){
                $income = 0;
            }

            $data[] = [
                'orderId' => $info->id,
                'member_id' => $info->member_id,
                'otn' => $info->order_no,
                //'amount' => $info->real_amount,
                'amount' => $info->discount_price,
                'income' => $income,
                //'createDate' => displayCreatedTime($info->create_time),
                'createDate' => displayCreatedTime($info->update_time,'Y-m-d H:i:s'),
                'state' => $info->pay_state,
                'stateDesc' => match($info->pay_state) { //1 尚未支付 2 已经支付 3 支付失败 4已经发放
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
            'total' => $count,
            'items' => $data
        ];
    }
}
