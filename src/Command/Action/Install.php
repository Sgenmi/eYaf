<?php

/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2020/3/23 下午5:53
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Command\Action;

class Install
{
    public function init(){
        $appPath = APP_ROOT."/application";
        if(is_dir($appPath)){
            echo '项目已存在，请忽重新安装'.PHP_EOL;
            echo 'The project already exists, please don\'t reinstall it'.PHP_EOL;
            return;
        }
        $this->createProjectDir();
        echo '初始化成功'.PHP_EOL;
        echo 'init success'.PHP_EOL;
    }

    public function rbac(){
        echo "Waiting for development".PHP_EOL;
    }

    private function createProjectDir(){
        $dirs=[
            'application'=>['Bootstrap.yafphp','sbin.yafphp'],
            'application/conf'=>'config.ini',
            'application/controllers'=>['IndexController.yafphp','ErrorController.yafphp'],
            'application/library'=>['Funs.yafphp','Admin.yafphp','Web.yafphp','Singleton.yafphp','Config.yafphp'],
            'application/models'=>'UserModel.yafphp',
            'application/modules'=>'.gitkeep',
            'application/plugins'=>'.gitkeep',
            'application/repositorys'=>'IndexRepository.yafphp',
            'application/services'=>'IndexService.yafphp',
            'application/task'=>['Base.yafphp','Test.yafphp'],
            'public'=>'index.yafphp'
        ];
        $resPath = __DIR__."/../Res";
        foreach ($dirs as $k=>$v){
            $dir = APP_ROOT.DIRECTORY_SEPARATOR.$k;
            echo $dir,"\n";
            if(is_dir($dir)){
                continue;
            }
            if(mkdir($dir,0755,true)){
                $v=(array) $v;
                foreach ($v as $vv){
                    if($vv=='.gitkeep'){
                        $getContent='';
                    }else{
                        $getContent = file_get_contents($resPath.DIRECTORY_SEPARATOR.$vv);
                    }
                    file_put_contents($dir.DIRECTORY_SEPARATOR.str_replace(['Controller','Model'],'',str_replace('.yafphp','.php',$vv)),$getContent);
                    if($vv=='config.ini'){
                        file_put_contents($dir.DIRECTORY_SEPARATOR.$vv.'.bak',$getContent);
                    }
                    if($vv=='index.yafphp'){
                        file_put_contents($dir.DIRECTORY_SEPARATOR.str_replace('.yafphp','.php',$vv).'.bak',$getContent);
                    }
                }
            }
        }
        $gitignore=<<<EOF
.idea
.DS_Store
.project
application/conf/config.ini
.buildpath
upload
application/log/*.log
composer.lock
vendor
public/index.php
EOF;
        file_put_contents(APP_ROOT.DIRECTORY_SEPARATOR.'.gitignore',$gitignore);

    }


}
