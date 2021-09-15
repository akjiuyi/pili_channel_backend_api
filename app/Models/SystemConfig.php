<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    protected $table = 'mzfk_system_config';
    protected $dateFormat = 'U';
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    /**
     * @param string $key
     * @param bool $cache
     * @param int $expire
     * @return mixed
     */
    public static function GetVal(string $key, bool $cache = false, int $expire = 3600):mixed{
        $rtn = self::query()->where('s_key',$key)->select(['id', 's_key', 's_value', 'type_handler', 'create_time', 'update_time'])->first();
        if (!$rtn) return $rtn;
        switch (strtolower($rtn->type_handler)) {
            case 'integer':
            case 'int':
                $val = intval($rtn->s_value);
                break;
            case 'float':
            case 'double':
                $val = floatval($rtn->s_value);
                break;
            case 'string':
            case 'str':
                $val = strval($rtn->s_value);
                break;
            case 'bool':
            case 'boolean':
                $val = $rtn->s_value ? true : false;
                break;
            case 'array':
                $val = json_decode($rtn->s_value, true);
                break;
            case 'json':
                $val = json_decode($rtn->s_value);
                break;
        }
        return $val;
    }
}