<?php
namespace devskyfly\yiiModuleTools;

use Yii;

class Module extends \yii\base\Module
{
    const CSS_NAMESPACE='devskyfly-yii-tools';
    
    public function init()
    {
        parent::init();
        //$this->checkProperties();
        Yii::setAlias("@devskyfly/yiiModuleTools", __DIR__);
        if(Yii::$app instanceof \yii\console\Application){
            $this->controllerNamespace='devskyfly\yiiModuleTools\console';
        }
    }
}

