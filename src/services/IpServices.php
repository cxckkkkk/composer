<?php
namespace CxcCommon\services;
use common\models\Logs;

//ip处理 两个库需要同步
class IpServices extends Base{

    //curl 请求 给请求代理Ip用
    public function curl(string $url){
        $curl = curl_init();
        //get
        curl_setopt($curl, CURLOPT_POST, false);
        //访问
        curl_setopt($curl, CURLOPT_URL, $url);
        //2秒超时
        curl_setopt($curl, CURLOPT_TIMEOUT, 2);
        //是否返回带头部信息
        curl_setopt($curl, CURLOPT_HEADER, false);
        //是否不需要响应的正文body
        curl_setopt($curl,CURLOPT_NOBODY,false);
        //不输出内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if(substr($url, 0,5) == 'https') {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $data = curl_exec($curl); //返回的内容
        $cuowu = curl_errno($curl); //返回的错误，如果有
        curl_close($curl);
        if($cuowu != 0){
            throw new \Exception('错误'.$cuowu);
        }
        if($data == false){
            throw new \Exception('data false');
        }
        return $data;

    }

    //ip转地址
    public function getIp(string  $ip){

        try{
            $host = "https://zjip.market.alicloudapi.com";
            $path = "/lifeservice/QueryIpAddr/query";
            $method = "GET";
            $appcode = \Yii::$app->params['ipGuishuKey']['AppCode'];
            $headers = array();
            array_push($headers, "Authorization:APPCODE " . $appcode);
            $querys = "ip=".$ip;
            $bodys = "";
            $url = $host . $path . "?" . $querys;

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_FAILONERROR, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            if (1 == strpos("$".$host, "https://"))
            {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            }
            $data = curl_exec($curl);
            curl_close($curl);
            if($data == false){
                throw new \Exception('空data');
            }
            $data = json_decode($data,true);

            if(isset($data['result']['city']) == false || empty($data['result']['city']) == true){
                throw new \Exception('没有ip地址');
            }
            return $data['result']['city'];

        }catch (\Exception $e){
            Logs::setLogs('IpServices getIp'.$e->getMessage(),13);
            return false;

        }

    }

    //代理ip验证 是否有效 返回真假
    public function ipTrue(string  $proxy,$url = 'https://www.baidu.com/robots.txt'){
        $rand = mt_rand(22, 500);
        $header[] = "Content-type: application/x-www-form-urlencoded";
        $user_agent = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/$rand.36 (KHTML, like Gecko) Chrome/96.0.$rand.110 Safari/$rand.36";
        $curl = curl_init();
        //get
        curl_setopt($curl, CURLOPT_POST, false);
        //头部head
        curl_setopt($curl, CURLOPT_HTTPHEADER,$header);
        //ua
        curl_setopt($curl, CURLOPT_USERAGENT,$user_agent);
        //访问
        curl_setopt($curl, CURLOPT_URL, $url);
        //是否返回带头部信息
        curl_setopt($curl, CURLOPT_HEADER, false);
        //2秒超时
        curl_setopt($curl, CURLOPT_TIMEOUT, 2);
        //是否不需要响应的正文body
        curl_setopt($curl,CURLOPT_NOBODY,true);
        //不输出内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //使用代理
        curl_setopt($curl, CURLOPT_PROXY, $proxy);
        //https
        if(substr($url, 0,5) == 'https') {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        curl_exec($curl); //返回的内容
        $cuowu = curl_errno($curl); //返回的错误，如果有
        $http_code = curl_getinfo($curl,CURLINFO_HTTP_CODE);
        curl_close($curl);
        if($cuowu != 0){
            return false;
        }
        if($http_code != 200){
            return false;
        }
        return true;
    }


    //获取redis中代理ip
    public function getRedisIp(){
        //默认第一个值 注意，这个在news库中，写的是'DIRECT'
//        $ips[] = ['ip'=>'DIRECT'];
        //获取代理key名
        $keys = \Yii::$app->params['redisIps'];
        foreach ($keys as $k=>$v){
            $strIp =  \Yii::$app->redis->get($v['key']);
            if($strIp == null){
                //获取新代理ip api ['ip+端口','过期时间','']
                $strIpArray = $this->apiSiyecao();
                if($strIpArray == false){
                    throw new \Exception('没有获取到代理ip');
                }
                //保存到redis,ip与过期时间
                \Yii::$app->redis->set($v['key'],json_encode($strIpArray,JSON_UNESCAPED_UNICODE));
                //如果没有过期时间的话，是永久存在，但定时任务会检查这个ip,是否可以使用，几次不能使用就删除
                if($strIpArray['timeOut'] != 'nul'){
                    \Yii::$app->redis->expire($v['key'],$strIpArray['timeOut']);
                }
                $strIp = $strIpArray['ip'];

            }else{
                $strIp = json_decode($strIp,true);
            }
            $ips[] = $strIp;
        }
        //当代理ip数组只有一条的时候，终止程序，不再向下执行
        if(count($ips) == 0){
            Logs::setLogs('没有代理ip，系统终止exit',7,__METHOD__);
            exit;
        }
        //返回数组
        return $ips;
    }


    //curl 代理ip chrome库用 服务器new不用
    //chrome 在服务器中生成pac，这里不使用
    public function curlIp(){
        //没有开启代理 curl用自己的网络
        if(RedisJishuService::getSingleton()->getIpProxy('get',\Yii::$app->params['IpProxyKey']) == false){
            return false;
        }
        $ips = $this->getRedisIp();
        $ip = $ips[array_rand($ips)];

        //curl中认 false , pac中DIRECT
        if($ip['ip'] == 'DIRECT'){
            $ip['ip'] = false;
        }
        return $ip['ip'];
    }



    //api 获取代理 四叶天
    //https://www.siyetian.com/apis.html
    public function apiSiyecao(){
        try{
            $url = \Yii::$app->params['apiSiyecao'];
            //json
            $data = $this->curl($url);
            $data = json_decode($data,true);
            if($data['code'] != 1){

                throw new \Exception('错误码:'.$data['code'].',信息:'.$data['info']);
            }
            $ip = $data['data'][0]['ip'].':'.$data['data'][0]['port'];
            Logs::setLogs('获取ip'.$ip,13,__METHOD__);
            //先检测ip是否有效
            $ipTrue = $this->ipTrue($ip);
            if($ipTrue == true){
                //这个代理的ip 没有过期时间，只能定时检测并删除
                return ['ip'=>$ip,'timeOut'=>'nul'];
            }else{
                return false;
            }
        }catch (\Exception $e){
            Logs::setLogs('代理ip获取失败,'.$e->getMessage(),13,__METHOD__);
            return false;
        }

    }


}
