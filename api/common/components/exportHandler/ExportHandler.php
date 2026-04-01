<?php
namespace common\components\exportHandler;

use Yii;
use yii\base\Component;
use common\helpers\FormatHelper;
use common\models\Work;
use common\models\DataResult;
use common\models\Data;
use common\models\Project;
use common\models\Category;
use Exception;

/**
 *
 * 导出文件处理
 *
 *
 */
class ExportHandler extends Component
{
    public static function run($class, $action, $params)
    {
        $_logs = ['$class' => $class, '$action' => $action, '$params' => $params];
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));

        try
        {
            if (empty($params['pack']))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $params empty '.json_encode($_logs));
                return FormatHelper::result('', 'error', 'error');
            }
            $pack = $params['pack'];

            if (empty($pack['project_id']))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $params.projectId empty '.json_encode($_logs));
                return FormatHelper::result('', 'error', 'error');
            }
            $projectId = $pack['project_id'];

            if (empty($pack['configs']))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $params.configs empty '.json_encode($_logs));
                return FormatHelper::result('', 'error', 'error');
            }
            $configs = json_decode($pack['configs'], true);

            if (empty($pack['batch_id']))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $params.batch_id empty '.json_encode($_logs));
                return FormatHelper::result('', 'error', 'error');
            }
            $batchIds = strpos($pack['batch_id'], ',') ? explode(',', $pack['batch_id']) : [$pack['batch_id']];

            //         if (empty($pack['step_id']))
            //         {
            //             Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $params.step_id empty '.json_encode($_logs));
            //             return FormatHelper::result('', 'error', 'error');
            //         }
            $stepId = empty($pack['step_id']) ? 0 : $pack['step_id'];

            //------------------------------------------

            $project = Project::findOne($projectId);
            if (!$project)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '. json_encode($_logs));
                return FormatHelper::result('', 'project_not_found', 'project_not_found');
            }

            $category = Category::find()->where(['id' => $project->category_id])->asArray()->limit(1)->one();
            if(empty($category))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' category_not_found '. json_encode($_logs));
                return FormatHelper::result('', 'category_not_found', 'category_not_found');
            }

            //设置分表
            Work::setTable($project['table_suffix']);
            Data::setTable($project['table_suffix']);
            DataResult::setTable($project['table_suffix']);

            //查询作业id
            $dataIds = [];
            if($stepId) //数据集可能没有step_id
            {
                $sql = Work::find();
                $sql->where(['project_id' => $projectId]);
                $sql->andWhere(['in', 'batch_id', $batchIds]);
                $sql->andWhere(['step_id' => $stepId]);
                $sql->andWhere(['in','status',[Work::STATUS_SUBMITED,Work::STATUS_FINISH]]);

                if(isset($configs['startTime']) || isset($configs['endTime']))
                {
                    //有过滤时间
                    $startTime = 0;
                    $endTime = '';
                    if(isset($configs['startTime']))
                    {
                        $configs['startTime'] = preg_replace('/\s+(\d{2})-(\d{2})-(\d{2})/', ' $1:$2:$3', $configs['startTime']);
                        $startTime = strtotime($configs['startTime']) ? strtotime($configs['startTime']) : 0;
                    }
                    if(isset($configs['endTime']))
                    {
                        $configs['endTime'] = preg_replace('/\s+(\d{2})-(\d{2})-(\d{2})/', ' $1:$2:$3', $configs['endTime']);
                        $endTime = strtotime($configs['endTime']) ? strtotime($configs['endTime']) : 0;
                    }
                    if($endTime)
                    {
                        $sql->andFilterWhere(['>=','updated_at',$startTime]);
                        $sql->andFilterWhere(['<=','updated_at',$endTime]);
                    }
                    else
                    {
                        $sql->andFilterWhere(['>=','updated_at',$startTime]);
                    }
                }
                $dataIds = $sql->select(['data_id']) ->asArray()->column();
                if(empty($dataIds))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data_pack_empty '. json_encode($_logs));
                    //return FormatHelper::result(['checkMessage'=>'data_pack_empty']);
                    return FormatHelper::result('', 'data_pack_empty', 'data_pack_empty');
                }
            }

            //------------------------------------------

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

            $param = [
                'data_ids' => $dataIds,
                'pack' => $pack,
                'batch_ids' => $batchIds,
                'project' => $project,
                'category' => $category
            ];
            $result = call_user_func_array([__NAMESPACE__.'\\'.$class, $action], [$param]);

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

