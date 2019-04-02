<?php
/**
 * Created by PhpStorm.
 * User: 13838
 * Date: 2019/1/11
 * Time: 10:25
 */
namespace Home\Controller;

use Common\Core\Fun;
use Think\Controller;

class MediasController extends Controller{
    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Medias/audio",
     *   tags={"Medias"},
     *   summary="Mp3播放url",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="fileName",
     *     in="必传",
     *     description="文件名称",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="successful"
     *   ),
     *   @SWG\Response(response=400, description="Invalid username/password supplied")
     * )
     */
    public function audio(){
        is_cookie();//验证是否登录

        $fileName = Fun::request('fileName');
        if (empty($fileName)) return_err("文件名为空");

        $basePath = 'D:/webSite/Orders/PC/API/REC/manREC/';

        if (strlen($fileName) < 8) return_err("无法找到目录");
        $dateDir = substr($fileName, 0, 8);

        $allPath = $basePath.$dateDir.'/'.$fileName;
        if (!file_exists($allPath))  return_err("文件不存在");

        $this->download($allPath);

        return_data('success');
    }

    //服务器端：
    function download($path){
        $file_size = filesize($path);

        header("Content-type:audio/mp3");
        header("Accept-Ranges:bytes");
        header("Accept-Length:$file_size");
        header("Content-Disposition:attachment;filename=demo.mp3");
        readfile($path);
        exit();
    }
}