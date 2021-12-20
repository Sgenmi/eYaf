<?php

/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2021/12/13 下午4:37
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Command\Action;

use Sgenmi\eYaf\Command\Command;
use Sgenmi\eYaf\Config;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Create extends Command
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

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
    private $descInfo=[
        'create:controller'=>[
            'desc'=>'create a new controller class',
            'help'=>'555'
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
        $this->addArgument("name",InputArgument::REQUIRED,'create file name');
    }

    public function getHelp():string
    {
        $help= $this->descInfo[$this->getName()]['help']??'';
        return $help;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
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

        $str =<<<EOF

<?php

/**
 * Author: {$this->author}
 * Date: {$this->date}
 */

namespace Controller;

class {$this->fileName} extends \Web {

    public function init(){
        parent::init();
    }
    
    public function indexAction(){
    
    }

}

EOF;

        echo $str;

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

}
