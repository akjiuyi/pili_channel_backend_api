<?php
/**
 * User: Admin
 * Date: 2020/3/30
 */

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Game\ChannelList;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    public function info(Request $request)
    {
        $version = $request->input('version');
        if (!$version) {
            return $this->successJson(['hasUpdate' => false]);
        }
        $newestVersion = ChannelList::getNewestVersion() ?: '1.0.0';
        $hasUpdate = version_compare($version, $newestVersion) < 0 ? true : false;
        return $this->successJson(['hasUpdate' => $hasUpdate]);
    }

}