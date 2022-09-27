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
        if(defined('APP_PATH')){
            throw new Exception("BASE_PATH 未定义");
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

    private function controller(){
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
        $bool = $this->checkIsHaveModule($modulesName);
        if($bool){
            unset($filePathArr[0]);
            $className = implode('_',$filePathArr);
            $fileName = array_pop($filePathArr);
            $urlPath = sprintf('/%s/%s/index',$modulesName,$className);
            $dirPath = APP_PATH.sprintf('/modules/%s/controllers/%s', $modulesName,
                    ($filePathArr?implode('/',$filePathArr):'')
                );
            $this->createDir($dirPath);
            $file = $dirPath.'/'.$fileName.'.php';
            if(is_file($file)){
                $this->output->writeln($file.' is exist');
                return;
            }
        }else{
            $className = implode('_',$filePathArr);
            $fileName = array_pop($filePathArr);

            $urlPath = sprintf('/%s/index',$className);
            $dirPath = APP_PATH.sprintf('/controllers/%s', ($filePathArr?implode('/',$filePathArr):''));
            $this->createDir($dirPath);
            $file = $dirPath.'/'.$fileName.'.php';
        }

        $file = str_replace('//','/',$file);
        if(is_file($file)){
            $this->output->writeln($file.' is exist');
            return;
        }
        $urlPath = strtolower($urlPath);

        $str =<<<EOF
<?php

/**
 * Author: {$this->author}
 * Date: {$this->date}
 */

namespace Controller;

class {$className} extends \Web {

    public function init(){
        parent::init();
    }
    /**
     * queryPath: {$urlPath}
     */
   
    public function indexAction(){
        echo 'index';
    }
}

EOF;
        file_put_contents($file,$str);
        $this->output->writeln($file);

    }
    
    private function model(){
       $heler =  $this->getHelper('question');
       $question = new ConfirmationQuestion('Continue create service and repostiory  default:no; [no|yes] ?',false);
       if(!$heler->ask($this->input,$this->output,$question)){
           return;
       }
       //Continue create service,repostiory

    }

    private function module(){

    }

    private function plugin(){

    }

    private function repostiory(){

    }

    private function service(){

    }

    private function checkIsHaveModule($name):bool{
        if('index'==strtoupper($name)){
            return false;
        }
        $modulesPath = APP_PATH.'/modules/'.ucfirst(strtoupper($name));
        if(is_dir($modulesPath)){
            $question = new ConfirmationQuestion('Discover existing modules and create them in the module  default:Y; [Y|N]? ',true);
            $helper = $this->getHelper("question");
            if($helper->ask($this->input,$this->output,$question)){
                return true;
            }
        }
        return false;
    }

    private function createDir(string $dir):void{
        if(!is_dir($dir)){
            mkdir($dir,0755,true);
        }
    }

}
