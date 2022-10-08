<?php

/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2021/12/13 下午4:37
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Command\Action;

use Exception;
use Sgenmi\eYaf\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Yaf\Request_Abstract;
use Yaf\Response_Abstract;

class Create extends Command
{
    /**
     * @var string
     */
    private $fileName;

    /**
     * @var string
     */
    private $date;

    /**
     * @var string
     */
    private $author;

    private const TYPE_CONTROLLER = "controller";
    private const TYPE_MODEL = "model";
    private const TYPE_MODULE = "module";
    private const TYPE_COMMAND = "command";
    private const TYPE_SERVICE = "service";
    private const TYPE_REPOSITORY = "repository";
    private const TYPE_PLUGIN = "plugin";



    /**
     * @var array
     */
    protected $descInfo=[
        'create:controller'=>[
            'desc'=>'create a new controller class',
            'help'=>"Create a normal controller or module controller; \nValid format when creating a module controller such as :(module's name/controller's name)"
        ],
        'create:model'=>[
            'desc'=>'create a new model class',
            'help'=>''
        ],
        'create:module'=>[
            'desc'=>'create a new module',
            'help'=>''
        ],
        'create:plugin'=>[
            'desc'=>'create a new plugin class',
            'help'=>''
        ],
        'create:repository'=>[
            'desc'=>'create a new repository class',
            'help'=>''
        ],
        'create:service'=>[
            'desc'=>'create a new service class',
            'help'=>''
        ],
        'create:command'=>[
            'desc'=>'create a new command class',
            'help'=>''
        ],
    ];

    public function __construct($name)
    {
        parent::__construct($name);
        $this->setDescription($this->descInfo[$name]['desc']??'');
        $this->date = date("Y/m/d H:i");
        $this->author = $_SERVER['USER']?? $_SERVER['USERNAME']??'';
    }

    protected function configure()
    {
        parent::configure();
        $this->addArgument("name",InputArgument::REQUIRED,'create file name');
    }

    public function getHelp():string
    {
        $help= $this->descInfo[$this->getName()]['help']??'';
        return $help;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        if(!defined('APP_PATH')){
            throw new Exception("APP_PATH undefined");
        }
        $fileName = $this->input->getArgument('name');
        if(!$fileName){
            return 0;
        }
        $this->fileName = $fileName;
        $action = explode(':',$this->getName())[1];
        $this->$action();
        return 0;
    }

    /**
     * create controller
     * @return void
     */
    private function controller(){
        $fileArr = $this->_create($this->fileName,self::TYPE_CONTROLLER);
        if($fileArr['isHave']){
            $this->output->writeln($fileArr['file'].' is exist');
            return;
        }
        $strCode =<<<EOF
<?php

/**
 * Author: {$this->author}
 * Date: {$this->date}
 */

namespace Controller;

class {$fileArr['className']} extends \Web {

    public function init(){
        parent::init();
    }
    /**
     * queryPath: {$fileArr['urlPath']}
     */
   
    public function indexAction(){
        echo 'index';
    }
}

EOF;
        file_put_contents($fileArr['file'],$strCode);
        $this->output->writeln($fileArr['file']);
    }

    /**
     * create model
     * @return void
     */
    private function model(){
        $fileArr = $this->_create($this->fileName,self::TYPE_MODEL);
        if($fileArr['isHave']){
            $this->output->writeln($fileArr['file'].' is exist');
            return;
        }
        $str =<<<EOF
<?php

/**
 * Author: {$this->author}
 * Date: {$this->date}
 */

namespace {$fileArr['namespace']};
use Model\AbstractModel;

class {$fileArr['fileName']} extends AbstractModel {

    public \$table="";
   
}

EOF;
        file_put_contents($fileArr['file'],$str);
        $this->output->writeln($fileArr['file']);
    }


    /**
     * create module
     * @return void
     */
    private function module(){
        $fileName = strtolower($this->fileName);
        if($fileName=='index'){
            $this->output->writeln('index module do\'t create');
            return;
        }
        $modulesPath = APP_PATH.'/modules/'.ucfirst($fileName);
        if(is_dir($modulesPath)){
            $this->output->writeln($modulesPath.' is exist');
            return;
        }
        $this->output->writeln($modulesPath);
        $this->createDir($modulesPath);
        $this->fileName=sprintf('%s/%s',$fileName,'test');
        $this->controller();
        $this->service();
    }

    /**
     * create plugin
     * @return void
     */
    private function plugin(){
        $fileArr = $this->_create($this->fileName,self::TYPE_PLUGIN);
        if($fileArr['isHave']){
            $this->output->writeln($fileArr['file'].' is exist');
            return;
        }
        $strCode=<<<EOF
<?php

namespace {$fileArr['namespace']};
use Yaf\Plugin_Abstract;
use Yaf\Request_Abstract;
use Yaf\Response_Abstract;

class {$fileArr['fileName']} extends Plugin_Abstract
{
    public function dispatchLoopShutdown(Request_Abstract \$request , Response_Abstract \$response){
        //code...
    }

    public function dispatchLoopStartup(Request_Abstract \$request , Response_Abstract \$response){
        //code...
    }

    public function postDispatch(Request_Abstract \$request , Response_Abstract \$response){
        //code...
    }

    public function preDispatch(Request_Abstract \$request , Response_Abstract \$response){
        //code...
    }

    public function preResponse(Request_Abstract \$request , Response_Abstract \$response){
        //code...

    }

    public function routerShutdown(Request_Abstract \$request , Response_Abstract \$response){
        //code...
    }

    public function routerStartup(Request_Abstract \$request , Response_Abstract \$response){
        //code...
    }
}

EOF;
        file_put_contents($fileArr['file'],$strCode);
        $this->output->writeln($fileArr['file']);

        $des=<<<EOF

Bootstrap.php :

public function _initPlugins(\Yaf\Dispatcher \$dispatcher)
{
    \$dispatcher->registerPlugin(new \\{$fileArr['namespace']}\\{$fileArr['fileName']}());
}
EOF;
        $this->output->writeln($des);

    }

    /**
     * create repostiory
     * @param bool $isNeedRSerivce
     * @return void
     */
    private function repository(bool $isNeedRSerivce = true){

        $fileArr = $this->_create($this->fileName,self::TYPE_REPOSITORY);
        if($fileArr['isHave']){
            $this->output->writeln($fileArr['file'].' is exist');
            return;
        }
        $strCode=<<<EOF
<?php

/**
 * Author: {$this->author}
 * Date: {$this->date}
 */

namespace {$fileArr['namespace']};

class {$fileArr['fileName']}  {

    public function __construct() {
    
    }
}
EOF;
        file_put_contents($fileArr['file'],$strCode);
        $this->output->writeln($fileArr['file']);

        if($isNeedRSerivce){
            //是否需要创建service
            $serviceName = substr($fileArr['fileName'],0,strlen($fileArr['fileName'])-strlen(self::TYPE_REPOSITORY)).'Service';
            $question = new ConfirmationQuestion(sprintf('The %s needs to be created  default:Y; [Y|N]? ',$serviceName,true));
            $helper = $this->getHelper("question");
            if($helper->ask($this->input,$this->output,$question)){
                $this->service(false);
            }
        }

    }

    /**
     * create service
     * @param bool $isNeedRepo
     * @return void
     */
    private function service( bool $isNeedRepo = true){
        $fileArr = $this->_create($this->fileName,self::TYPE_SERVICE);
        if($fileArr['isHave']){
            $this->output->writeln($fileArr['file'].' is exist');
            return;
        }
        $strCode=<<<EOF
<?php

/**
 * Author: {$this->author}
 * Date: {$this->date}
 */

namespace {$fileArr['namespace']};

class {$fileArr['fileName']}  {

    public function __construct() {
    
    }
}
EOF;
        file_put_contents($fileArr['file'],$strCode);
        $this->output->writeln($fileArr['file']);

        if($isNeedRepo){
            //是否需要创建service
            $serviceName = substr($fileArr['fileName'],0,strlen($fileArr['fileName'])-strlen(self::TYPE_REPOSITORY)).'Repository';
            $question = new ConfirmationQuestion(sprintf('The %s needs to be created  default:Y; [Y|N]? ',$serviceName,true));
            $helper = $this->getHelper("question");
            if($helper->ask($this->input,$this->output,$question)){
                $this->repository(false);
            }
        }

    }

    /**
     * @return void
     */
    private function command(){
        $fileArr = $this->_create($this->fileName,self::TYPE_COMMAND);
        if($fileArr['isHave']){
            $this->output->writeln($fileArr['file'].' is exist');
        }

        $loFileName = strtolower($fileArr['fileName']);
        $mode = 'InputOption::VALUE_OPTIONAL';
        $str =<<<EOF
<?php

/**
 * Author: {$this->author}
 * Date: {$this->date}
 */

namespace {$fileArr['namespace']};
use Sgenmi\\eYaf\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class {$fileArr['fileName']} extends Command {

    protected \$descInfo = [
        '{$loFileName}:test'=>[
            'desc' => '测试 desc',
            'help' => '测试 help',
            'option' => [
                'start_time' => [
                    'mode' => {$mode},
                    'desc' => '开始时间',
                ],
                'end_time' => [
                    'mode' => {$mode},
                    'desc' => '结束时间',
                ]
            ],
        ]       
    ];
    
    protected function initialize(InputInterface \$input, OutputInterface \$output)
    {
        parent::initialize(\$input, \$output);
        //init your code
    }
    
    protected function test(){
        \$this->output->writeln('test');
    }
   
}

EOF;
        file_put_contents($fileArr['file'],$str);
        $this->output->writeln($fileArr['file']);

        //检查配置文件
        if($fileArr['modulesName']){
            $dirName = dirname($fileArr['file']);
            $configFile = APP_PATH.'/modules/'.$fileArr['modulesName'].'/commands/command.php';
        }else{
            $configFile = APP_PATH.'/conf/command.php';
        }
        if(!is_file($configFile)){
            $strCode=<<<EOF
<?php
return [
  new {$fileArr['namespace']}/{$fileArr['fileName']}('{$loFileName}:test'),
  
];
EOF;
            file_put_contents($configFile,$strCode);
        }


    }

    /**
     * @param string $name
     * @param bool $isAsk
     * @return bool
     */
    private function checkIsHaveModule(string $name,bool $isAsk=true):bool{
        if('index'==strtoupper($name)){
            return false;
        }
        $modulesPath = APP_PATH.'/modules/'.ucfirst(strtolower($name));
        if(is_dir($modulesPath)){
            if($isAsk){
                $question = new ConfirmationQuestion('Discover existing modules and create them in the module  default:Y; [Y|N]? ',true);
                $helper = $this->getHelper("question");
                if($helper->ask($this->input,$this->output,$question)){
                    return true;
                }
            }
        }
        return false;
    }

    private function createDir(string $dir):void{
        if(!is_dir($dir)){
            mkdir($dir,0755,true);
        }
    }

    private function _create($inputFileName,$type=self::TYPE_CONTROLLER):array {
        $urlPath = '';
        if(strpos($this->fileName,'/')===false){
            $this->fileName = 'index/'.$this->fileName;
        }
        $filePathArr = explode('/',$this->fileName);
        $filePathArr = array_map(function ($v){
            return ucfirst(strtolower($v));
        },$filePathArr);
        $modulesName = $filePathArr[0]?:"index";
        //如果是默认 $modulesName=index
        if(strtolower($modulesName)=='index'){
            unset($filePathArr[0]);
        }
        //只有当type为非plugin时 才验证modules
        $bool = false;
        if($type != self::TYPE_PLUGIN){
            $bool = $this->checkIsHaveModule($modulesName);
        }
        if($bool) unset($filePathArr[0]);
        $className = implode('_',$filePathArr);
        $fileName = array_pop($filePathArr).(
            in_array($type,[self::TYPE_MODEL, self::TYPE_REPOSITORY, self::TYPE_SERVICE,self::TYPE_PLUGIN])?ucfirst($type):''
            );

        if($bool){
            $dirPath = APP_PATH.sprintf('/modules/%s/%ss/%s', $modulesName,$type,
                    ($filePathArr?implode('/',$filePathArr):'')
                );
            $urlPath = sprintf('/%s/%s/index',$modulesName,$className);
        }else{
            $modulesName = '';
            $dirPath = APP_PATH.sprintf('/%ss/%s',$type, ($filePathArr?implode('/',$filePathArr):''));
            $urlPath = sprintf('/%s/index',$className);
        }
        $this->createDir($dirPath);
        $file = $dirPath.'/'.$fileName.'.php';

        $namespace = ($modulesName?$modulesName.'\\':'').ucfirst($type).($filePathArr?'\\'.implode('\\',$filePathArr):'');

        $file = str_replace('//','/',$file);
        $urlPath = strtolower($urlPath);
        return [
            'namespace'=> $type==self::TYPE_CONTROLLER?ucfirst($type):$namespace,
            'file'=>$file,
            'isHave'=>is_file($file),
            'modulesName'=>$modulesName,
            'className'=>$className,
            'urlPath'=>$urlPath,
            'fileName'=>$fileName
        ];
    }

}
