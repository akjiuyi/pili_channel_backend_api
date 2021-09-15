<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberOrder extends Model
{
    protected $table = 'mzfk_member_order';
    protected $dateFormat = 'U';
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';
}