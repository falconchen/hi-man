<?php

/**
 * 监控疫苗接种点更新并发送通知
 */

$ch = curl_init();

// set url
curl_setopt($ch, CURLOPT_URL, 'https://xgsz.szcdc.net/crmobile/outpatient/nearby');

// set method
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

// return the transfer as a string
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// set headers
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'Appid: app569d18f5',
  'Connection: keep-alive',
  'Accept: application/json, text/plain, */*',
  'Accept-Language: zh-cn',
  'Token: -t-Rc0_xuLMFfVIJpwqF6BUKKlaNeo2ZYcLXywQ4QSrVIoRda3M_8S3BQBWqQYZd2vW',
  'Content-Type: application/x-www-form-urlencoded',
  'Origin: https://xgsz.szcdc.net',
  'Otn: vWyqLArbJg0+VtZNfBK+Gt/GWYAV6b1zkL5r8ISC2M1oEyRs5uW0CeOwBcBkLOwijpEvZ+Aw8CrkiO/F1inIv4igNGqGr2lq87+9a9UXhyA5E3ZQe25S4Sdoic33uoc9',
  'Ybm: DYxJKkUc8VXANgLAsnPNDCC5HfP43R0JySKTdnE4vhc=',
  'Selfappid: wx5402a9708b90332e',
  'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 MicroMessenger/8.0.7(0x18000730) NetType/WIFI Language/zh_CN',
  'Referer: https://xgsz.szcdc.net/crmobile/?params=hYAPOVzzgsPD8XXKeSRfCA5D4ew18ECibvx8KQWaN4VH/mqwOIj5PCcLlU6NOXLc/CK7PjsP8WK4bqG+mQB9B1rzZdXcL0WVh0GBmQhWpXrg9ItV6zOHfDIqmxjZfwnHp4mzgQJbf6eRAUZ6gmJ/dJ9EhIkRgkNoVkbJwL0m7fzSBaZCQP3Yo8ep46KscSet5M9vohbfl9KAszgPi7y0ySLDZIsml2Q2GpUYZ31tgS8yKeKkUNdVegZM0c8LclBj1bQbpmr6zh7xUOs+ZHoFx3gZw/lHUW/4k/H3QCYzm1gER5p4Ikpv05kK3kHVsesP&selfAppId=wx5402a9708b90332e&timeStamp=8605194082566250496',
  'Reservationtoken: 507ac1d19d6342c4949d386779bed64f',
  'Content-Length: 191',
]);

// form body
$body = [
  'params' => 'BEvehIahEMZGIT3yW02gFJXpAp9xDtUd9FG4ykCnJ3S/915C56Zmprjq0EQPd7m61vYVwkA53kRpLfDxzyrUD8gOlrGxSJ10m7O28hyD0qwsI7+hmpbmbbkHvV8Nk9kGwj4/kMWjqIB+NZCVBzw/zaEFr9295KIsFEQiUG1fdn8=',
];
// $body = [
//   'params' => 'params=BEvehIahEMZGIT3yW02gFJXpAp9xDtUd9FG4ykCnJ3S%2F915C56Zmprjq0EQPd7m6DO8DGyx1EWWExCIXXmTYNcdNKcclhkkRnEMPaq0mMkSZOGh0QgJvoLKD8HWf2M9na9tOhkZXszHWonFZ46BzOGgo8hQFgpUEoNxw6hOwqlU%3D',
// ];
$body = http_build_query($body);

// set body
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

// send the request and save response to $response
$response = curl_exec($ch);

// stop if fails
if (!$response) {
  die('Error: "' . curl_error($ch) . '" - Code: ' . curl_errno($ch));
}

//echo 'HTTP Status Code: ' . curl_getinfo($ch, CURLINFO_HTTP_CODE) . PHP_EOL;
//echo 'Response Body: ' . $response . PHP_EOL;

$data_arr = json_decode($response ,true);


// close curl resource to free up system resources 
curl_close($ch);

if(isset($_GET['debug'])){
  exit ($response);
}

$keywords = ['福海街道','怀德社康','福永预防保健所','福永街道会堂',];

if($data_arr['ecode'] == 1000 && isset($data_arr['data']['list']) && !empty($data_arr['data']['list'])) {
  $items = $data_arr['data']['list'];
  $valid_items = array_filter($items,function($item) use ($keywords){    
    if($item['nums'] == 1 && $item['status'] == 1){      
      foreach($keywords as $kw){        
        if(strpos($item['outpName'] ,$kw) !== false && !is_sent($item['outpName'],$item['outpUpdatedTime'])){
          return true;
        }
        continue;
      }
    }

  });

  foreach($valid_items as $valid) {
    $title = $valid['outpName'] ;
    $content = '最后更新时间:'. substr($valid['outpUpdatedTime'],0,19);
    bark($title,$content);
    sleep(3);
  }
  
}

//是否已发送过
function is_sent($outpName,$outpUpdatedTime,$file=null){
  return false;
  $file = is_null($file) ? __DIR__ .'/outpUpdatedTime.json' :$file;
  if( !is_file($file) ){   
    $outArr = [$outpName=>$outpUpdatedTime]; 
    $content = json_encode($outArr,JSON_UNESCAPED_UNICODE);
    file_put_contents($file,$content);
    return false;
  }

  $outArr = json_decode($file,true);
  if( isset($outArr[$outpName]) && $outArr[$outpName] == $outpUpdatedTime) {
    return false;
  }

  $outArr[$outpName] = $outpUpdatedTime;
  $content = json_encode($outArr ,JSON_UNESCAPED_UNICODE);
  file_put_contents($file,$content);
  return true;

}

function bark($title,$content,$prefix_url='https://api.day.app/RZG4QjJ3hZ772kzDDPpRxZ'){
  $url = sprintf($prefix_url.'/%s/%s',$title,$content);
  $result = file_get_contents($url);
  $result_arr = json_decode($result,true);
  return (!is_null($result_arr) && $result_arr['message']=='success');
}