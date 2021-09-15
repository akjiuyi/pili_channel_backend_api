<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $table = 'mzfk_member';
    protected $dateFormat = 'U';
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    public static function getChannelMemberCount($channelId) {
        if ($channelId <= 0) return 0;
        return self::query()->where('channel_id', $channelId)->count();
    }

}