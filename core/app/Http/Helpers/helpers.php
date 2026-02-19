<?php

use App\Constants\Status;
use App\Lib\GoogleAuthenticator;
use App\Models\Extension;
use App\Models\Frontend;
use App\Models\GeneralSetting;
use Carbon\Carbon;
use App\Lib\Captcha;
use App\Lib\ClientInfo;
use App\Lib\CurlRequest;
use App\Lib\FileManager;
use App\Models\Counter;
use App\Notify\Notify;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Laramin\Utility\VugiChugi;

function systemDetails()
{
    $system['name'] = 'GV Florida';
    $system['version'] = '2.0';
    $system['build_version'] = '5.2.4';
    return $system;
}

function buildVer()
{
    return systemDetails()['build_version'];
}

if (!function_exists('timeDifferenceReadable')) {
    function timeDifferenceReadable($startTime, $endTime)
    {
        try {
            $start = new DateTime($startTime);
            $end = new DateTime($endTime);

            // If end is earlier, assume next day
            if ($end <= $start) {
                $end->modify('+1 day');
            }

            $diff = $start->diff($end);

            $hours = ($diff->days * 24) + $diff->h;
            $minutes = $diff->i;

            $parts = [];

            if ($hours > 0) {
                $parts[] = $hours . ' hrs';
            }

            if ($minutes > 0) {
                $parts[] = $minutes . ' mins';
            }

            return empty($parts) ? '0 mins' : implode(' ', $parts);

        } catch (Exception $e) {
            return null;
        }
    }
}


function generateTicketQR($pnr_number, $size = 150)
{
    return QrCode::size($size)->generate(route('admin.vehicle.ticket.search', ['scope' => 'list', 'search' => $pnr_number]));
}

function getPaynamicsPMethod($pchannel, $getname = false): string
{
    $pmethods = json_decode(file_get_contents('assets/admin/paynamics_pmethod.json'))->pmethod;
    $pmethod = '';
    foreach ($pmethods as $item) {
        foreach ($item->types as $type) {
            if ($pchannel == $type->value) {
                $pmethod = $getname ? $item->name : $item->value;
                break;
            }
        }
    }
    return $pmethod;
}

function getPaynamicsPChannel($pchannel, $getname = false): string
{
    $pmethods = json_decode(file_get_contents('assets/admin/paynamics_pmethod.json'))->pmethod;
    $pmethod = '';
    foreach ($pmethods as $item) {
        foreach ($item->types as $type) {
            if ($pchannel == $type->value) {
                $pmethod = $getname ? $type->name : $type->value;
                break;
            }
        }
    }
    return $pmethod;
}

function generateUID(int $number, string $locationCode, string $prefix = 'KSK', $zero_padding = 3): string
{
    $locationCode = strtoupper($locationCode);
    $numberPadded = str_pad($number, $zero_padding, '0', STR_PAD_LEFT);
    return "$prefix-{$locationCode}-{$numberPadded}";
}

function slug($string)
{
    return Str::slug($string);
}

function generateTripStatusHTML($status)
{
    $class = $status == Status::TRIP_ON_TIME ? 'success' : '';
    $class = $status == Status::TRIP_BOARDING ? 'primary' : $class;
    $class = $status == Status::TRIP_DELAYED ? 'warning' : $class;
    $class = $status == Status::TRIP_CANCELLED ? 'danger' : $class;
    echo '<span class="status-dot status-' . $status . '"></span>';
    echo '<span class="badge bg-' . $class . '-subtle text-' . $class . '">' . decodeSlug($status) . '</span>';
}

function verificationCode($length)
{
    if ($length == 0)
        return 0;
    $min = pow(10, $length - 1);
    $max = (int) ($min - 1) . '9';
    return random_int($min, $max);
}

function getNumber($length = 8)
{
    $characters = '1234567890';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}


function activeTemplate($asset = false)
{
    $template = session('template') ?? gs('active_template');
    if ($asset)
        return 'assets/templates/' . $template . '/';
    return 'templates.' . $template . '.';
}

function activeTemplateName()
{
    $template = session('template') ?? gs('active_template');
    return $template;
}

function siteLogo($type = null)
{
    $name = $type ? "/logo_$type.png" : '/logo.png';
    return getImage(getFilePath('logoIcon') . $name) . '?v=' . appVersion();
}
function siteFavicon()
{
    return getImage(getFilePath('logoIcon') . '/favicon.png') . '?v=' . appVersion();
}

function loadReCaptcha()
{
    return Captcha::reCaptcha();
}

function loadCustomCaptcha($width = '100%', $height = 46, $bgColor = '#003')
{
    return Captcha::customCaptcha($width, $height, $bgColor);
}

function verifyCaptcha()
{
    return Captcha::verify();
}

function loadExtension($key)
{
    $extension = Extension::where('act', $key)->where('status', Status::ENABLE)->first();
    return $extension ? $extension->generateScript() : '';
}

function getTrx($length = 12)
{
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function getAmount($amount, $length = 2)
{
    $amount = round($amount ?? 0, $length);
    return $amount + 0;
}

function generateReqID($prefix = 'GVF')
{
    $date = date('Ymd');
    $req_id = "$prefix-$date-" . substr(uniqid(), 0, 7);
    return $req_id;
}

function showAmount($amount, $decimal = 2, $separate = true, $exceptZeros = false, $currencyFormat = true)
{
    $separator = '';
    if ($separate) {
        $separator = ',';
    }
    $printAmount = number_format($amount, $decimal, '.', $separator);
    if ($exceptZeros) {
        $exp = explode('.', $printAmount);
        if ($exp[1] * 1 == 0) {
            $printAmount = $exp[0];
        } else {
            $printAmount = rtrim($printAmount, '0');
        }
    }
    if ($currencyFormat) {
        if (gs('currency_format') == Status::CUR_BOTH) {
            return gs('cur_sym') . $printAmount . ' ' . __(gs('cur_text'));
        } elseif (gs('currency_format') == Status::CUR_TEXT) {
            return $printAmount . ' ' . __(gs('cur_text'));
        } else {
            return gs('cur_sym') . $printAmount;
        }
    }
    return $printAmount;
}

function isExpired($date)
{
    return strtotime($date) < strtotime(date('Y-m-d H:i')) ? true : false;
}

function autoLink($text, $target = '_blank')
{
    // Regular expression to detect URLs starting with http or https
    $pattern = '/(https?:\/\/[^\s]+)/i';

    // Replace URLs with clickable links
    return preg_replace_callback($pattern, function ($matches) use ($target) {
        $url = $matches[0];
        return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="' . $target . '" rel="noopener noreferrer">' . $url . '</a>';
    }, $text);
}

function paymentStatus($status)
{
    if ($status == 1)
        echo '<span class="badge badge--success">Paid</span>';
    elseif ($status == 2)
        echo '<span class="badge badge--warning">Pending</span>';
    elseif ($status == 3)
        echo '<span class="badge badge--danger">Rejected</span>';
    else
        echo '<span class="badge badge--warning">Pending</span>';
}

function removeElement($array, $value)
{
    return array_diff($array, (is_array($value) ? $value : array($value)));
}

function cryptoQR($wallet)
{
    return "https://api.qrserver.com/v1/create-qr-code/?data=$wallet&size=300x300&ecc=m";
}

function keyToTitle($text)
{
    return ucfirst(preg_replace("/[^A-Za-z0-9 ]/", ' ', $text));
}


function titleToKey($text)
{
    return strtolower(str_replace(' ', '_', $text));
}


function strLimit($title = null, $length = 10)
{
    return Str::limit($title, $length);
}


function getIpInfo()
{
    $ipInfo = ClientInfo::ipInfo();
    return $ipInfo;
}


function osBrowser()
{
    $osBrowser = ClientInfo::osBrowser();
    return $osBrowser;
}


function getTemplates()
{
    $param['purchasecode'] = env("PURCHASECODE");
    $param['website'] = @$_SERVER['HTTP_HOST'] . @$_SERVER['REQUEST_URI'] . ' - ' . env("APP_URL");
    $url = VugiChugi::gttmp() . systemDetails()['name'];
    $response = CurlRequest::curlPostContent($url, $param);
    if ($response) {
        return $response;
    } else {
        return null;
    }
}


function getPageSections($arr = false)
{
    $jsonUrl = resource_path('views/') . str_replace('.', '/', activeTemplate()) . 'sections.json';
    $sections = json_decode(file_get_contents($jsonUrl));
    if ($arr) {
        $sections = json_decode(file_get_contents($jsonUrl), true);
        ksort($sections);
    }
    return $sections;
}


function getImage($image, $size = null)
{
    $clean = '';
    if (file_exists($image) && is_file($image)) {
        return asset($image) . $clean;
    }
    if ($size) {
        return route('placeholder.image', $size);
    }
    return asset('assets/images/default.png');
}


function notify($user, $templateName, $shortCodes = null, $sendVia = null, $createLog = true, $pushImage = null)
{
    $globalShortCodes = [
        'site_name' => gs('site_name'),
        'site_currency' => gs('cur_text'),
        'currency_symbol' => gs('cur_sym'),
    ];

    if (gettype($user) == 'array') {
        $user = (object) $user;
    }

    $shortCodes = array_merge($shortCodes ?? [], $globalShortCodes);

    $notify = new Notify($sendVia);
    $notify->templateName = $templateName;
    $notify->shortCodes = $shortCodes;
    $notify->user = $user;
    $notify->createLog = $createLog;
    $notify->pushImage = $pushImage;
    $notify->userColumn = isset($user->id) ? $user->getForeignKey() : 'user_id';
    $notify->send();
}


function decodeSlug($str, $delimiter = '_', $uppercase = false)
{
    if (!$str) {
        return;
    }
    $expd_str = explode($delimiter, $str);
    $output = '';
    foreach ($expd_str as $key => $v) {
        $v = $key == 0 ? ucfirst($v) : $v;
        $output .= $v . ' ';
    }

    $output = $uppercase ? strtoupper($output) : $output;

    return $output;
}

function readPaymentChannel($pcode)
{
    $res = decodeSlug($pcode);
    return strtoupper($res);
}

function getPaginate($paginate = null)
{
    if (!$paginate) {
        $paginate = gs('paginate_number');
    }
    return $paginate;
}

function paginateLinks($data)
{
    return $data->appends(request()->all())->links();
}


function menuActive($routeName, $type = null, $param = null)
{
    if ($type == 3)
        $class = 'side-menu--open';
    elseif ($type == 2)
        $class = 'sidebar-submenu__open';
    else
        $class = 'active';

    if (is_array($routeName)) {
        foreach ($routeName as $key => $value) {
            if (request()->routeIs($value))
                return $class;
        }
    } elseif (request()->routeIs($routeName)) {
        if ($param) {
            $routeParam = array_values(@request()->route()->parameters ?? []);
            if (strtolower(@$routeParam[0]) == strtolower($param))
                return $class;
            else
                return;
        }
        return $class;
    }
}


function fileUploader($file, $location, $size = null, $old = null, $thumb = null, $filename = null)
{
    $fileManager = new FileManager($file);
    $fileManager->path = $location;
    $fileManager->size = $size;
    $fileManager->old = $old;
    $fileManager->thumb = $thumb;
    $fileManager->filename = $filename;
    $fileManager->upload();
    return $fileManager->filename;
}

function fileManager()
{
    return new FileManager();
}

function getFilePath($key)
{
    return fileManager()->$key()->path;
}

function getFileSize($key)
{
    return fileManager()->$key()->size;
}

function getFileExt($key)
{
    return fileManager()->$key()->extensions;
}

function diffForHumans($date)
{
    $lang = session()->get('lang');
    Carbon::setlocale($lang);
    return Carbon::parse($date)->diffForHumans();
}

function showDateTime($date, $format = 'Y-m-d h:i A')
{
    if (!$date) {
        return '-';
    }

    $lang = session()->get('lang');
    Carbon::setlocale($lang ?? 'en');
    return Carbon::parse($date)->translatedFormat($format);
}

function getContent($dataKeys, $singleQuery = false, $limit = null, $orderById = false)
{

    $templateName = activeTemplateName();
    if ($singleQuery) {
        $content = Frontend::where('tempname', $templateName)->where('data_keys', $dataKeys)->orderBy('id', 'desc')->first();
    } else {
        $article = Frontend::where('tempname', $templateName);
        $article->when($limit != null, function ($q) use ($limit) {
            return $q->limit($limit);
        });
        if ($orderById) {
            $content = $article->where('data_keys', $dataKeys)->orderBy('id')->get();
        } else {
            $content = $article->where('data_keys', $dataKeys)->orderBy('id', 'desc')->get();
        }
    }
    return $content;
}

function verifyG2fa($user, $code, $secret = null)
{
    $authenticator = new GoogleAuthenticator();
    if (!$secret) {
        $secret = $user->tsc;
    }
    $oneCode = $authenticator->getCode($secret);
    $userCode = $code;
    if ($oneCode == $userCode) {
        $user->tv = Status::YES;
        $user->save();
        return true;
    } else {
        return false;
    }
}


function urlPath($routeName, $routeParam = null)
{
    if ($routeParam == null) {
        $url = route($routeName);
    } else {
        $url = route($routeName, $routeParam);
    }
    $basePath = route('home');
    $path = str_replace($basePath, '', $url);
    return $path;
}


function showMobileNumber($number)
{
    $length = strlen($number);
    return substr_replace($number, '***', 2, $length - 4);
}

function showEmailAddress($email)
{
    $endPosition = strpos($email, '@') - 1;
    return substr_replace($email, '***', 1, $endPosition);
}


function getRealIP()
{
    $ip = $_SERVER["REMOTE_ADDR"];
    //Deep detect ip
    if (filter_var(@$_SERVER['HTTP_FORWARDED'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_FORWARDED'];
    }
    if (filter_var(@$_SERVER['HTTP_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_FORWARDED_FOR'];
    }
    if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    if (filter_var(@$_SERVER['HTTP_X_REAL_IP'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    }
    if (filter_var(@$_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if ($ip == '::1') {
        $ip = '127.0.0.1';
    }

    return $ip;
}


function appendQuery($key, $value)
{
    return request()->fullUrlWithQuery([$key => $value]);
}

function dateSort($a, $b)
{
    return strtotime($a) - strtotime($b);
}

function dateSorting($arr)
{
    usort($arr, "dateSort");
    return $arr;
}

function appVersion()
{
    return buildVer() ?: '1.0.0';
}

function gs($key = null)
{
    $general = Cache::get('GeneralSetting');
    if (!$general) {
        $general = GeneralSetting::first();
        Cache::put('GeneralSetting', $general);
    }
    if ($key)
        return @$general->$key;
    return $general;
}
function isImage($string)
{
    $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif');
    $fileExtension = pathinfo($string, PATHINFO_EXTENSION);
    if (in_array($fileExtension, $allowedExtensions)) {
        return true;
    } else {
        return false;
    }
}

function isHtml($string)
{
    if (preg_match('/<.*?>/', $string)) {
        return true;
    } else {
        return false;
    }
}


function convertToReadableSize($size)
{
    preg_match('/^(\d+)([KMG])$/', $size, $matches);
    $size = (int) $matches[1];
    $unit = $matches[2];

    if ($unit == 'G') {
        return $size . 'GB';
    }

    if ($unit == 'M') {
        return $size . 'MB';
    }

    if ($unit == 'K') {
        return $size . 'KB';
    }

    return $size . $unit;
}

function getStoppageInfo($stoppages)
{
    $data = Counter::routeStoppages($stoppages);
    return $data;
}

function stoppageCombination($numbers, $arraySize, $level = 1, $i = 0, $addThis = [])
{
    // If this is the last layer, use a different method to pass the number.
    if ($level == $arraySize) {
        $result = [];
        for (; $i < count($numbers); $i++) {
            $result[] = array_merge($addThis, array($numbers[$i]));
        }
        return $result;
    }

    $result = [];
    $nextLevel = $level + 1;
    for (; $i < count($numbers); $i++) {
        // Add the data given from upper level to current iterated number and pass
        // the new data to a deeper level.
        $newAdd = array_merge($addThis, array($numbers[$i]));
        $temp = stoppageCombination($numbers, $arraySize, $nextLevel, $i, $newAdd);


        $result = array_merge($result, $temp);
    }

    return $result;
}

function showGender($val)
{
    switch ($val) {

        case $val == 0:
            $result = 'Others';
            break;
        case $val == 1:
            $result = 'Male';
            break;
        case $val == 2:
            $result = 'Female';
            break;
        default:
            $result = '';
            break;
    }
    return $result;
}

function showDayOff($val)
{
    $result = '';
    if (gettype($val) == 'array') {
        foreach ($val as $value) {
            $result .= getDay($value);
        }
    } else {
        $result = getDay($val);
    }
    return $result;
}

function getDay($val)
{
    switch ($val) {
        case $val == 6:
            $result = 'Saturday';
            break;
        case $val == 0:
            $result = 'Sunday';
            break;
        case $val == 1:
            $result = 'Monday';
            break;
        case $val == 2:
            $result = 'Tuesday';
            break;
        case $val == 3:
            $result = 'Wednesday';
            break;
        case $val == 4:
            $result = 'Thursday';
            break;
        case $val == 5:
            $result = 'Friday';
            break;
        default:
            $result = '';
            break;
    }
    return $result;
}


function frontendImage($sectionName, $image, $size = null, $seo = false)
{
    if ($seo) {
        return getImage('assets/images/frontend/' . $sectionName . '/seo/' . $image, $size);
    }
    return getImage('assets/images/frontend/' . $sectionName . '/' . $image, $size);
}

function getCRHeight($row_covered)
{
    $height = ($row_covered == 2) ? '95px' : '40px';
    $height = ($row_covered == 3) ? '130px' : $height;
    return $height;
}