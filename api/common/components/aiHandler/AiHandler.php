<?php 
namespace common\components\aiHandler;

use Yii;
use yii\base\Component;
use Exception;
use common\helpers\FormatHelper;


/**
 *
 * ai模型处理
 *
 */
class AiHandler extends Component
{
    
    
    public static function run($class, $action, $params)
    {
        $_logs = ['$class' => $class, '$action' => $action, '$params' => $params];
        
        $class = trim($class, '/\\.');
        if (strpos($class, '/'))
        {
            $class = str_replace('/', '\\', $class);
        }
        
        $classes = explode('\\', $class);
        $classes[count($classes) - 1] = ucfirst($classes[count($classes) - 1]);
        $class = implode('\\', $classes);
        
        $_logs['$class.new'] = $class;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $class '.json_encode($_logs));
        
        try
        {
            $result = call_user_func_array([__NAMESPACE__.'\\'.$class, $action], [$params]);
            
            $_logs['$result'] = $result;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' script complete '.json_encode($_logs));
        }
        catch(Exception $e)
        {
            $_logs['$e.file'] = $e->getFile();
            $_logs['$e.line'] = $e->getLine();
            $_logs['$e.code'] = $e->getCode();
            $_logs['$e.message'] = $e->getMessage();
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' script exception '.json_encode($_logs));
            
            $result = FormatHelper::result('', 'error', 'error');
        }

        return $result;
    }
    
}

