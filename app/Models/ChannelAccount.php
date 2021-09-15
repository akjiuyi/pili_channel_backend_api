<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelAccount extends Model
{
    protected $table = 'mzfk_channel_account';
    protected $dateFormat = 'U';
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';
    protected $primaryKey = 'channel_id';

    public static function getInfoByChannelId($channelId) :? self{
        return parent::where('channel_id', $channelId)->first();
    }

    public static function getInfoByChannelIdForLock($channelId) :? self{
        return parent::where('channel_id', $channelId)->lockForUpdate()->first();
    }
}