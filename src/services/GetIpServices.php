<?php
namespace CxcCommon\services;
use common\models\Logs;

//ip处理
class GetIpServices extends Base{

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

    //api 获取代理 四叶天
    //https://www.siyetian.com/apis.html
    public function apiSiyecao($url){
        try{
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
