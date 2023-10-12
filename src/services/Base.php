<?php
namespace CxcCommon\services;
//多个单列模式
class Base{
    protected static $instance = [];
    private function __construct(){}
    private function __clone(){}
    protected $phone = false;
    /****
     * @return static
     * 单列模式
     */
    public static function getSingleton(){
        $class = get_called_class();
        if(!array_key_exists($class,self::$instance)){
            self::$instance[$class] = new $class();
        }
        return self::$instance[$class];
    }

    //数组转json
    public  function arrayJson($array){
        return json_encode($array);
    }
    //错误码json格式
    public  function error($msg){
        return $this->arrayJson(['code'=>-1,'msg'=>$msg,'data'=>'']);
    }
    //正确json格式
    public  function success($data=false,$msg='操作成功'){
        return $this->arrayJson(['code'=>200,'msg'=>$msg,'data'=>$data]);
    }

    //手机验证
    public function Yzphone($phone){
        if(!(preg_match("/1[123456789]{1}\d{9}$/",$phone))){
            return false;
        }
        return true;
    }
    //手机中间隐藏
    public function set_Phone($phone){
        return substr_replace($phone,'****',3,4);
    }

    //获取cookie
    protected  function getCookie($name,$default_val=''){
        $cookies = \Yii::$app->request->cookies;
        return $cookies->getValue($name, $default_val);
    }
    //删除cookie
    protected function removeCookie($name){
        $cookies = \Yii::$app->response->cookies;
        $cookies->remove($name);
    }


}
