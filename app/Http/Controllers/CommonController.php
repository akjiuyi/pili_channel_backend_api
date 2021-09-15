<?php
namespace App\Http\Controllers;

use App\Models\AppProduct;
use App\Models\PaymentChannel;

class CommonController extends Controller
{

    /**
     * app商品列表
     */
    public function appProducts() {
        $products = AppProduct::appProductLists();
        return $this->successJson(['items' => $products]);
    }

    /**
     *  支付渠道
     */
    public function paymentChannels() {
        $channelLists = PaymentChannel::appProductLists();
        return $this->successJson(['items' => $channelLists]);
    }

}