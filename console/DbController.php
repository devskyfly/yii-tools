<?php
namespace devskyfly\yiiModuleTools\console;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Migration;
use yii\helpers\BaseConsole;
use yii\base\BaseObject;

class DbController extends Controller
{
    public function actionDropAll()
    {
        
    }
    
    public function actionDropByPrefix()
    {
        
    }
    
    public function actionClearAll()
    {
        try {
            $db=Yii::$app->db;
            $schema=$db->schema;
            $tables=$schema->getTableNames();
            if(BaseConsole)
                $migration=new Migration();
                foreach ($tables as $table){
                    if(preg_match("/^iit_uc.*/", $table)){
                        $migration->truncateTable($table);
                    }
                }
        }catch (\Exception $e){
            BaseConsole::stdout($e->getMessage());
            BaseConsole::stdout(PHP_EOL);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        catch (\Throwable $e){
            BaseConsole::stdout($e->getMessage());
            return ExitCode::UNSPECIFIED_ERROR;
        }
        return ExitCode::OK;
    }
    
    public function actionClearByPrefix()
    {
        try {
            $db=Yii::$app->db;
            $schema=$db->schema;
            $tables=$schema->getTableNames();
            
            BaseConsole::prompt($text)
            
            $migration=new Migration();
            
            BaseConsole::stdout(PHP_EOL.'Clear tables by prefix:'.PHP_EOL);
            foreach ($tables as $table){
                if(preg_match("/^iit_uc.*/", $table)){
                    BaseConsole::stdout(-$string)
                    $migration->truncateTable($table);
                }
            }
        }catch (\Exception $e){
            BaseConsole::stdout($e->getMessage());
            BaseConsole::stdout(PHP_EOL);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        catch (\Throwable $e){
            BaseConsole::stdout($e->getMessage());
            return ExitCode::UNSPECIFIED_ERROR;
        }
        return ExitCode::OK;
    }
    
}