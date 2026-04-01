<?php 
namespace common\components\importHandler;

use Yii;
use yii\base\Component;
use Exception;
use common\helpers\FormatHelper;
use common\helpers\ArrayHelper;

/**
 *
 * 导入文件处理
 *
 *
 */
class ImportHandler extends Component
{
    
    public static function run($class, $action, $params)
    {
        $_logs = ['$class' => $class, '$action' => $action, '$params' => ArrayHelper::desc($params)];
        
        $class = trim($class, '/\\.');
        if (strpos($class, '/'))
        {
            $class = str_replace('/', '\\', $class);
        }
        
        $classes = explode('\\', $class);
        $classes[count($classes) - 1] = ucfirst($classes[count($classes) - 1]);
        $class = implode('\\', $classes);
        
        $_logs['$class.new'] = $class;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $class '.serialize($_logs));
        
        try
        {
            $result = call_user_func_array([__NAMESPACE__.'\\'.$class, $action], [$params]);
            
            $_logs['$result'] = $result;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' script complete '.serialize($_logs));
        }
        catch(Exception $e)
        {
            $eMessage = $e->getMessage();
            $_logs['$e.file'] = $e->getFile();
            $_logs['$e.line'] = $e->getLine();
            $_logs['$e.code'] = $e->getCode();
            $_logs['$e.message'] = $eMessage;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' script exception '.serialize($_logs));
            
            if(stripos($eMessage, 'sql') !== false) //此处后期优化翻译
            {
                $message = 'unpack_sql_exception';
            }
            else
            {
                $message = 'unpack_script_exception';
            }
            $result = FormatHelper::result('', 'error', $message);
        }
        
        
        return $result;
    }
}

