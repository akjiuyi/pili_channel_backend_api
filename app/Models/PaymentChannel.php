<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentChannel extends Model
{
    protected $table = 'mzfk_payment_channel';
    protected $dateFormat = 'U';
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    public static function getSimpleListsByIds(array $ids) {
        $return = [];
        parent::query()->whereIn('id', $ids)->get()->each(function($info) use (&$return){
            $return[$info->id] = [
                'id' => $info->id,
                //'title' => $info->landslide_name,
                'title' => "id:{$info->id}-{$info->merchant_name}-{$info->payment_name}",
                'desc' => $info->descs,
                'originalPrice' => $info->original_price,
                'discountPrice' => $info->discount_price
            ];
        });
        return $return;
    }

    public static function appProductLists() {
        parent::query()->where('app_group','')->get()->each(function($info) use (&$return){
            $return[] = [
                'id' => $info->id,
                'landslideName' => "id:{$info->id}-{$info->merchant_name}-{$info->payment_name}",
            ];
        });
        return $return;
    }
}
