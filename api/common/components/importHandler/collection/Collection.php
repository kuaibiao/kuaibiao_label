<?php 
namespace common\components\importHandler\collection;

use Yii;
use yii\base\Component;
use common\components\Functions;
use common\models\Batch;
use common\helpers\FormatHelper;

/**
 * 
 * 采集类
 * 
 *
 */

class Collection extends Component
{
    public static function run($args)
    {
        $_logs['$args'] = $args;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
        
        if (empty($args['filePath']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' filePath empty '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $filePath = $args['filePath'];
        $defaultField = isset($args['defaultField']) ? $args['defaultField'] : null;
        $extensions = isset($args['extensions']) ? $args['extensions'] : null;
        $batchConfigs = isset($args['batchConfigs']) ? $args['batchConfigs'] : null;
        
        //------------------------------------------
        
        //采集类, 只支持自动
        if ($batchConfigs['assign_type'])
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' batch exist '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        
        foreach ($batchConfigs['paths'] as $batchConfig)
        {
            $_logs['$batchConfig'] = $batchConfig;
            $batch = Batch::find()->where(['project_id' => $project['id'], 'path' => Functions::html_encode($batchConfig)])->limit(1)->one();
            if (!$batch)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' batch exist '.json_encode($_logs));
                continue;
            }
        
            //设置开始时间
            $batch->status = Batch::STATUS_ENABLE;
            $batch->updated_at = time();
            $batch->save();
        }
        
        return FormatHelper::result(1);
    }
}