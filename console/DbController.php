<?php
namespace devskyfly\yiiModuleTools\console;

use devskyfly\php56\types\Str;
use devskyfly\php56\types\Vrbl;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Migration;
use yii\helpers\BaseConsole;
use devskyfly\php56\libs\fileSystem\Dirs;

/**
 * 
 * @author devskyfly
 *
 */
class DbController extends Controller
{ 
    protected $db=null;
    protected $schema=null;
    protected $tables_prompt_msg="";

    /* public function options($actionID)
    {
        
    } */
    
    public function init()
    {
        $this->db=Yii::$app->db;
        $this->schema=$this->db->schema;
        $this->tables_prompt_msg="Input table name, prefix, names separeted by commas or leave it blank to match all tables:";
    }
    
    /**
     * Show list of matched tables.
     * 
     * @return number
     */
    public function actionIndex()
    {
        $prefix=BaseConsole::prompt($this->tables_prompt_msg);
        $tables=$this->getTables($prefix);
        $this->showTables($tables);
        return ExitCode::OK;
    }
    
    /**
     * Dump list of matched tables.
     * 
     * @param string $file_path
     * @return number
     */
    public function actionDump($file_path)
    {
        try{
            if(Vrbl::isEmpty($file_path)){
                throw new \InvalidArgumentException('Parameter $file_path is empty');
            }
            
            $dir=dirname($file_path); 
            $file=basename($file_path);
            if($dir[0]=='.'){
                $dir=realpath(Yii::getAlias('@app/'.$dir));
                if(Vrbl::isEmpty($dir)){
                    throw new \RuntimeException("Directory does not exist.");
                }
            }
            
            if(!Dirs::isDir($dir)){
                throw new \RuntimeException("Directory {$dir} is not dir.");
            }
            if(!Dirs::dirExists($dir)){
                throw new \RuntimeException("Directory {$dir} does not exist.");
            }
            
            $file_path=$dir.'/'.$file;
            
            $match=BaseConsole::prompt($this->tables_prompt_msg);
            $tables=$this->getTables($match);
            
            $i=0;
            foreach ($tables as $table){
                $i++;
                BaseConsole::stdOut($i.'. '.$table.PHP_EOL);
            }
            
            $host=$this->getHostName();
            $db=$this->getDbName();
            $pass=$this->db->password;
            $user=$this->db->username;
            
            $str_tables=implode(' ', $tables);
            
            BaseConsole::stdout($file_path.PHP_EOL);
            
            $out_put=[];
            $return_value=0;
            exec("mysqldump -u{$user} -p{$pass} -h{$host} {$db} {$str_tables} > {$file_path}",$out_put,$return_value);

            if($return_value!=ExitCode::OK){
                //BaseConsole::stdout($out_put);
            }else{
                throw new \RuntimeException("Dump failed. Execution of cmd 'exec' return :".
                    PHP_EOL.implode(PHP_EOL, $out_put).PHP_EOL);
            }
        }catch (\Exception $e){
            BaseConsole::stdout($e->getMessage());
            BaseConsole::stdout(PHP_EOL);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        catch (\Throwable $e){
            BaseConsole::stdout($e->getMessage());
            BaseConsole::stdout(PHP_EOL);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        
        return ExitCode::OK;
    }
    
    /**
     * Drop list of matched tables.
     * 
     * @return number
     */
    public function actionDrop()
    {
        $fnc=function($item){
            $mg=new Migration();
            $mg->dropTable($item);
        };
        $text="Drop tables";
        return $this->execute($fnc, $text);
    }
    
    /**
     * Clean list of matched tables.
     * @return number
     */
    public function actionClear()
    {
        $fnc=function($item){
            $mg=new Migration();
            $mg->truncateTable($item);
        };
        $text="Clear tables";
        return $this->execute($fnc, $text);
    }
    
    /**
     *
     * @param string $name
     * @throws \InvalidArgumentException
     * @return string|NULL
     */
    protected function getDsnValue($name)
    {
        if(!Str::isString($name)){
            throw new \InvalidArgumentException('Parameter $name is not string type.');
        }
        $match=[];
        
        if(preg_match('/' . $name . '=([^;]*)/', $this->db->dsn, $match)) {
            return $match[1];
        } else {
            return null;
        }
    }
    
    /**
     * @throws \InvalidArgumentException
     * @return string|NULL
     */
    protected function getDbName()
    {
        return $this->getDsnValue('dbname');
    }
    
    /**
     * @throws \InvalidArgumentException
     * @return string|NULL
     */
    protected function getHostName()
    {
        $host=$this->getDsnValue('host');
        /* if($host){
            $host=substr($host,1,(strlen($host)-1));
        } */
        return $host;
    }
    
    /**
     * 
     * @param callable $callback
     * @param string $console_text
     * @return number
     */
    protected function execute($callback,$console_text)
    {
        if(!Str::isString($console_text)){
            throw new \InvalidArgumentException('Parameter $console_text is not string type.');
        }
        
        if(!Vrbl::isCallable($callback)){
            throw new \InvalidArgumentException('Parameter $callback is not callable type.');
        }
        
        $migration=new Migration();
        $match=BaseConsole::prompt($this->tables_prompt_msg);
        $tables=$this->getTables($match);
        $this->showTables($tables);
        
        if(!BaseConsole::confirm("Do you realy want to execute this command on this tables?".PHP_EOL)){
            BaseConsole::stdout("Operation was terminated.".PHP_EOL);
            return ExitCode::OK;
        }
        
        if(!BaseConsole::confirm("Are you sure?")){
            BaseConsole::stdout("Operation was terminated.".PHP_EOL);
            return ExitCode::OK;
        }
        
        try {
            $trn=$this->db->beginTransaction();
            BaseConsole::stdout(PHP_EOL.$console_text.':'.PHP_EOL);
            $i=0;
            foreach ($tables as $table){
                $i++;
                BaseConsole::stdout($i.'. '.$table.PHP_EOL);
                $callback($table);
            } 
            $trn->commit();
        }catch (\Exception $e){
            BaseConsole::stdout($e->getMessage());
            BaseConsole::stdout(PHP_EOL);
            $trn->rollBack();
            return ExitCode::UNSPECIFIED_ERROR;
        }
        catch (\Throwable $e){
            BaseConsole::stdout($e->getMessage());
            BaseConsole::stdout(PHP_EOL);
            $trn->rollBack();
            return ExitCode::UNSPECIFIED_ERROR;
        }
        
        return ExitCode::OK;
    }
    
    /**
     * 
     * @param string[] $tables
     * @return void
     */
    protected function showTables($tables){
        BaseConsole::stdOut("Tables list:".PHP_EOL);
        $i=0;
        foreach ($tables as $table){
            $i++;
            BaseConsole::stdout($i.'. '.$table.PHP_EOL);
        }
    }
    
    /**
     * Return array of tables by matching with parameter $match.
     * 
     * If $match is empty, function returns all tables.
     * If $match contans '_' at the end, then function will return tables names begining from this match prefix.
     * Other case it returns strict match.
     * 
     * @param string $match
     * @throws \InvalidArgumentException
     * @return string[]
     */
    protected function getTables($match="")
    {
        $filtered_tables=[];
        
        if(!Str::isString($match)){
            throw new \InvalidArgumentException("Parameter");
        }      
        $tables=$this->schema->getTableNames();
        if(Vrbl::isEmpty($match)){
            return $tables;
        }  
        
        $fnc=function($value){
            print_r($value);
            return trim($value);
        };
        
        $exploded_tables=explode(",", $match);      
        $exploded_tables=array_map($fnc,$exploded_tables);
        
        if(!Vrbl::isEmpty($exploded_tables)){
            $arr=array_intersect($exploded_tables, $tables);
            foreach ($arr as $table){
                $filtered_tables[]=$table;
            }
            return $filtered_tables;
        }
        
        if(preg_match("/^.*_$/", $match)){
            foreach ($tables as $table){
                if(preg_match("/^{$match}.*/", $table)) $filtered_tables[]=$table;
            }
            return $filtered_tables;
        }else{
            foreach ($tables as $table){
                if($match==$table) {
                    $filtered_tables[]=$table;
                    break;
                }
            }
            return $filtered_tables;
        }
        
    }
}