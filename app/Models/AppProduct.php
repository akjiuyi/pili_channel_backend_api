<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppProduct extends Model
{
    protected $table = 'mzfk_app_product';
    protected $dateFormat = 'U';
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    public static function getSimpleListsByIds(array $ids) {
        $return = [];
        parent::query()->whereIn('id', $ids)->get()->each(function($info) use (&$return){
            $return[$info->id] = [
                'id' => $info->id,
                'title' => $info->title,
                'desc' => $info->descs,
                'originalPrice' => $info->original_price,
                'discountPrice' => $info->discount_price
            ];
        });
        return $return;
    }

    public static function appProductLists($handleName = 'MemberShipHandler') {
        parent::query()->where('product_handler', $handleName)
                       ->whereIn('type', [1,2])
                       ->get()->each(function($info) use (&$return){
                            $return[] = [
                                'id' => $info->id,
                                'title' => "{$info->title}",
                                'desc' => $info->descs,
                                'originalPrice' => $info->original_price,
                                'discountPrice' => $info->discount_price
                            ];
                        });

        return $return;
    }
}
