<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xls;


if (!function_exists('config_path')) {
    function config_path()
    {
        return app()->basePath('config');
    }
}

/** 调试日志记录
 * @param $msg
 * @param $fileName
 * @return mixed
 */
function logDebug($msg, $fileName)
{
    if (!is_string($msg)) {
        $msg = var_export($msg, true);
    }
    return app('Log')->debug($msg, $fileName);
}

/** 错误日志记录
 * @param Exception $exception
 * @return mixed
 */
function logError(Exception $exception)
{
    if ($exception instanceof PDOException) {
        $errorInfo = $exception->errorInfo;
        if ($errorInfo[1] == 1062) {
            return false;
        }
    }
    $info = pathinfo($exception->getFile());
    return app('Log')->error($exception->getMessage() . '; Trace info:' . $exception, $info['filename']);
}

/** 获取手机设备号
 * @return null|string
 */
function getAppDeviceId()
{
    return isset($_SERVER['HTTP_X_DEVICEID']) ? trim($_SERVER['HTTP_X_DEVICEID']) : null;
}


/** 生成一个订单编号
 * @param $userId
 * @return string
 */
function orderSN($userId)
{
    return (date('y') - 13) . sprintf('%010d', date('mdHis'))
        . sprintf('%04d', (float)microtime() * 10000)
        . sprintf('%04d', (int)$userId % 10000);
}


/** 获取url里的参数
 * @param $url
 * @param $key
 * @return string
 */
function getUrlParam($url, $key)
{
    $reg = "/(\\?|\\&)$key=([^\\&]+)/";
    preg_match($reg, $url, $result);
    if ($result && $result[2]) {
        $return = $result[2];
    } else {
        $reg = "/i(\d+)\\.htm/";
        preg_match($reg, $url, $result);
        $return = $result[1] ?? 0;
    }
    return $return;
}


/** 下载图片
 * @param $imgUrl //图片地址
 * @param $timeout
 * @return mixed|null
 */
function downloadImg($imgUrl, $timeout = 20)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_URL, $imgUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    $res = curl_exec($ch);
    curl_close($ch);
    if ($res === false) {
        return null;
    }
    return $res;
}

/** 获取上传路径
 * @return string
 */
function getUploadPath()
{
    return storage_path('app/upload');
}

/** 图片上传路径
 * @return string
 */
function getImgUploadPath()
{
    return storage_path('app/upload/img');
}

/** 是否是正确的URL
 * @param $url
 * @return bool
 */
function isUrl($url)
{
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        return true;
    } else {
        return false;
    }
}

/** 格式化显示创建时间
 * @param $created_at
 * @param $format
 * @return string
 */
function displayCreatedTime($created_at, $format = null)
{
    $time = is_numeric($created_at) ? $created_at : ($created_at instanceof DateTime ? $created_at->getTimestamp() : strtotime($created_at));
    //转日期格式
    if ($format) {
        return date($format, $time);
    }
    $t = time();
    if ($t < $time + 60) {
        $dateStr = '刚刚';
    } elseif ($t < $time + 60 * 30) {
        $dateStr = floor(($t - $time) / 60) . '分钟前';
    } elseif ($t < $time + 60 * 60) {
        $dateStr = '30分钟前';
    } else {
        $dateStr = date('Y-m-d H:i:s', $time);
    }
    return $dateStr;
}


function isMobile($phone)
{
    if (preg_match("/^1[3456789]{1}\d{9}$/", $phone)) {
        return true;
    }
    return false;
}


/** 格式化手机号
 * @param $mobile
 * @return string
 */
function formatMobile($mobile)
{
    if (!is_numeric($mobile)) {
        return $mobile;
    }
    return substr($mobile, 0, 3) . '****' . substr($mobile, -4);
}

function enableSqlQuery() {
    app('db')->connection()->enableQueryLog();
}

function printSqlQuery($query = 1) {
    $sql = app('db')->getQueryLog();
    if ($query == 1) {
        $sql = $sql[0]['query'];
    }
    print_r($sql);
}

function getRealIp()
{
    $ip = false;

    //客户端IP 或 NONE
    if(!empty($_SERVER["HTTP_CLIENT_IP"])){
        $ip = $_SERVER["HTTP_CLIENT_IP"];
    }

    //多重代理服务器下的客户端真实IP地址（可能伪造）,如果没有使用代理，此字段为空

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {

        $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);

        if ($ip) { array_unshift($ips, $ip); $ip = false; }

        for ($i = 0; $i < count($ips); $i++) {
            if (!preg_match('/^(10|172.16|192.168)./', $ips[$i])) {
                $ip = $ips[$i];
                break;
            }
        }
    }
    return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
}

function randString($length = 12)
{
    return substr(sha1(uniqid() . microtime()), 0, $length);
}


/**
 * exportExcel($data,$title,$filename);
 * 导出数据为excel表格
 *@param $data    一个二维数组,结构如同从数据库查出来的数组
 *@param $title   excel的第一行标题,一个数组,如果为空则没有标题
 *@param $filename 下载的文件名
 *@examlpe
exportExcel($arr,array('id'=>'id','account'=>''账户','pwd'=>'密码','nickname'=>'昵称'),'文件名!');
 */
function exportExcelV2($data=array(),$header=array(),$filename='report',$title="导出表")
{
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename=" ' . $filename . '.xls"'); //下载文件名字
    header('Cache-Control: max-age=0');

    $spreadsheet = new Spreadsheet();

    $sheet = $spreadsheet->getActiveSheet();

    $styleArray = [
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
        ],
    ];

    $sheet->getStyle('A:Z')->getFont()->setBold(true)->setName('宋体 (正文)')
        ->setSize(15);

    $sheet->getDefaultColumnDimension()->setWidth(20);
    $sheet->getDefaultRowDimension()->setRowHeight(30);

    $sheet->getStyle('A:Z')->applyFromArray($styleArray);

    //设置标题
    $sheet->setTitle($title);

    $table_header = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");

    $title_key = [];
    //导出xls开始
    if (!empty($header))
    {
        $i = 0;
        foreach ($header as $k => $v)
        {
            //$sheet->setCellValue($table_header[$i]."1", iconv("UTF-8", "gbk//ignore",$v));
            $sheet->setCellValue($table_header[$i]."1", $v);
            $title_key[] = $k;
            $i++;
        }
    }

    if (!empty($data))
    {
        $i = 0;
        foreach($data as $key=>$val)
        {
            $j = 0;
            foreach ($val as $ck => $cv)
            {
                if($j > (count($header)-1)){
                    break;
                }
                $title_key_ = $title_key[$j];

                //$sheet->setCellValue($table_header[$j].($i+2), iconv("UTF-8", "gbk//ignore", $val["{$title_key_}"]));
                $sheet->setCellValue($table_header[$j].($i+2), $val["{$title_key_}"]);
                $j++;
            }
            $i++;
        }
    }

    $writer = new Xls($spreadsheet);
    $writer->save('php://output');
    die;
}



