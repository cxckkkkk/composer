<?php
namespace CxcCommon\services;
use common\models\Logs;
use common\services\AliossService;

//oss 图片处理
class ImgOssServices extends Base{

    //图片下载 外网图片下载，英文与繁体内容时，使用
    public function imgOss($title,$data){

        $weburl = 'https://';
        $pathtime =  date("Y").'/'.date("Ymd").'/'; //oss 需要的上传路径拼接
        $imgtype = ['jpg','jpeg','png','gif'];

        //提取分解图片路径
        preg_match_all("/<img.*?src\s*=\s*['\"](.*?)['\"][^>]*?>/",$data,$imgarray);
        if(empty($imgarray[1])){
            return $data;
        }
        $data =  preg_replace("/<img.*?src=\"(.+?)\".*?>/si","<img src=\"$1\" alt=\"$title\">",$data);

        //循环处理Img
        foreach($imgarray[1] as $imgurl){
            try{
                //图片如果在阿里云或替换的空图片，直接跳出
                if(strpos($imgurl,'shuziqushi') === false){
                    //不存在
                }else{
                    break; //跳出
                }
                //保存图片格式，有些网站img结尾没有格式，默认jpeg
                $typejpg = explode(".",$imgurl);
                $typejpg = end($typejpg);
                $typejpg = explode("?",$typejpg);
                $typejpg = $typejpg[0];
                $typejpg = in_array($typejpg,$imgtype)?$typejpg:'jpeg';

                //生成图片名字
                $fileimgname = time()."-".mt_rand(1000000,9999999).".".$typejpg;
                //拼接完整图片路径 /20211108//xx.jpeg
                $filecachs = $pathtime.$fileimgname;
                //保存图片路径，准本替换内容
                $saveimgfile = "https://img.shuziqushi.com/uploadwww/".$pathtime.$fileimgname;

                //本地图片，不在使用，为了安全，需要上传到阿里oss临时文件夹中
                //网络图片网络图片网络图片网络图片网络图片网络图片

                //gif图片跳出
                if($typejpg == 'gif'){
                    $data = str_replace($imgurl,\Yii::$app->params['url']['wcss'].'/images/null.jpg',$data);
                    continue;
                }

                //图片没有http的，补全，采集的内容，如果图片没有，在采集的时候进行补全
                if(substr($imgurl, 0,4) == 'http'){
                    $imgurlxin = $imgurl;
                }else{
                    $imgurlxin = $weburl.$imgurl;
                }

                //要下载的网络图片路径
                $imgurlxin = str_replace('///','/',$imgurlxin);
                // 下载网络图片，存入变量
                // $imgurlxin 网络图片路径
                // $filecachs 新的图片路径

                $imgs = $this->ChromeService->getCurlDaili($imgurlxin,['pac'=>false]);

                if($imgs['code'] != 200){
                    Logs::setLogs($imgs['msg'],10,__METHOD__);
                    //没有下载成功的图片，用本地的默认空白图片来代替
                    $data = str_replace($imgurl,\Yii::$app->params['url']['wcss'].'/images/null.jpg',$data);
                    continue;
                }else{
                    //oss 上传
                    $updataImgStr =  AliossService::getSingleton()->updataImgStr($filecachs,$imgs['data']);
                    if($updataImgStr != 200){
                        //没有下载成功的图片，用本地的默认空白图片来代替
                        $data = str_replace($imgurl,\Yii::$app->params['url']['wcss'].'/images/null.jpg',$data);
                        continue;
                    }
                }

                //替换图片url
                $data = str_replace($imgurl,$saveimgfile,$data);
            }catch (\Exception $e){
                Logs::setLogs($e->getMessage(),10,__METHOD__);
                continue;
            }
        }

        //如果图片是空，删除空图片
        $data =  preg_replace("/<img src=\"https:\/\/www.shuziqushi.com\/images\/null.jpg\" alt=\"$title\">/si","",$data);
        return $data;

    }


}
