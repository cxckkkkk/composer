<?php
namespace CxcCommon\services;
use common\models\Logs;

//ip处理
class GetIpServices extends Base{


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
