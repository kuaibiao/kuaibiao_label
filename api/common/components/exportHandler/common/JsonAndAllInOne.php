<?php
namespace common\components\exportHandler\common;

use common\helpers\ArrayHelper;
use Yii;
use yii\base\Component;
use common\helpers\FileHelper;
use common\helpers\FormatHelper;
use common\helpers\JsonHelper;
use common\models\Setting;
use common\models\DataResult;
use common\models\Pack;

/**
 *
 * 导出json格式的原始结果
 *
 */
class JsonAndAllInOne extends Component
{

    /**
     * 执行导出脚本，导出json格式
     * 检测和执行同时进行，只检测时输出检测结果不生成文件不打包，打包数据检测结果和打包数据一起
     *
     * @param array $args
     */
    public static function run($args)
    {
        $_logs['$args'] = ArrayHelper::var_desc($args);
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
        
        if(empty($args['data_ids']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $params.data_ids empty '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $dataIds = $args['data_ids'];
        if(empty($args['batch_ids']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $params.batch_ids empty '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $batchIds = $args['batch_ids'];
        if(empty($args['pack']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $params.pack empty '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $pack = $args['pack'];
        if (empty($pack['configs']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $params.pack.configs empty '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $configs = json_decode($pack['configs'], true);
        if(empty($args['project']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $params.project empty '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $project = $args['project'];
        $projectId = $project['id'];
        
        //$resourceRoot = Settings::getResourceRootPath();
        $uploadfileRoot = Setting::getUploadRootPath();
        $downloadfileRoot = Setting::getDownloadPath($project['user_id'], $projectId);

        $batchDirname = Yii::t('app', 'pack_script_common_JsonAndAllInOne').'_'.$projectId.'_'.implode(',', $batchIds).'_'.date('YmdHis');
        $batchDirPath = $downloadfileRoot .'/'.$batchDirname;
        $batchFilePath = $downloadfileRoot .'/'.$batchDirname.'.json';
        $checkFilePath = $downloadfileRoot .'/'.$batchDirname.'.check.txt';
        $_logs['$batchDirname'] = $batchDirname;
        $_logs['$batchDirPath'] = $batchDirPath;
        $_logs['$batchFilePath'] = $batchFilePath;
        $_logs['$checkFilePath'] = $checkFilePath;


        //更新打包总数
        $attributes = [
            'pack_item_total' => count($dataIds),
            'pack_item_succ' => 0,
            'pack_item_fail' => 0,
            'updated_at' => time()
        ];
        Pack::updateAll($attributes, ['id' => $pack['id']]);
        
        //---------------------------------------
        
        $contents = [];
        $checkMessages = [];
        $checkMessages['$projectId'] = $projectId;
        $checkMessages['$batchIds'] = $batchIds;

        $noDataSource = [];
        $noDataResult = [];
        $checkMessagesNew = '';//返回的错误信息，成功或者失败
        
        //分组处理
        $groups = array_chunk($dataIds, 100);
        $group_count = count($groups);
        $is_last = false;
        foreach ($groups as $i => $ids_)
        {
            //循环一次处理100条
            //进度, 方便查看日志了解进度
            $count = count($ids_);
            $_logs['$list.item'] = $count.':'.$i.'/'.$group_count;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' loop  '.json_encode($_logs));

            $list = DataResult::find()
            ->where(['project_id' => $projectId])
            ->andWhere(['in', 'data_id', $ids_])
            ->asArray()->all();

            $succ_num = 0;
            foreach ($list as $k => $v)
            {
                if (empty($v['data']))
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data empty  '.json_encode($_logs));
                    $checkMessages['error'][$v['data_id']][]= '没有数据源';
                    $noDataSource[] = $v['data_id'];
                    continue;
                }

                if (empty($v['result']))
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' result empty  '.json_encode($_logs));
                    $checkMessages['error'][$v['data_id']][]= '没有标注结果';
                    $noDataResult[] = $v['data_id'];
                    continue;
                }

                $dataArr = JsonHelper::json_decode_all($v['data']);
                $resultArr = JsonHelper::json_decode_all($v['result']);
                
                //------------------------------------

                $contents[] = [
                    'data' => $dataArr,
                    'result' => $resultArr
                ];
                $succ_num++;
            }

            if($group_count != $i+1)
            {
                //更新打包数量，累加
                $attributes = [
                    'pack_item_succ' => $succ_num,
                    'pack_item_fail' =>  $count - $succ_num,
                    'updated_at' => time() - $pack['updated_at']
                ];
                Pack::updateAllCounters($attributes, ['id' => $pack['id']]);
            }
            else
            {
                $last_num = $count;
                $last_succ_num = $succ_num;
                $is_last = true;
            }
            
            
            //检查打包记录是否异常
            if (!Pack::allowProcesses($pack['id']))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'  cleanUpProcesses  '.json_encode($_logs));
                Pack::cleanUpProcesses();
                //return FormatHelper::result('', 'error', 'error');
                return FormatHelper::result('', 'error', ['checkMessage' => ['content'=>'pack_data_error']]);
            }
            
        }


        if(!empty($configs['cnEscape']))
        {
            $fileContent = json_encode($contents,JSON_UNESCAPED_UNICODE);
        }
        else
        {
            $fileContent = json_encode($contents);
        }

        FileHelper::file_write($batchFilePath, $fileContent, 'w');

        if (!file_exists($batchFilePath))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'  downloadfile error  '.json_encode($_logs));
            //return FormatHelper::result('', 'data_pack_nodata', '下载文件保存失败');
            return FormatHelper::result('', 'error', ['checkMessage' => ['content'=>'data_pack_nodata']]);
        }
        else
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'  downloadfile  '.json_encode($_logs));
            $checkMessages['packFile'] = '下载文件保存成功:'.$batchFilePath;
        }

        //生成检测文件
        FileHelper::file_write($checkFilePath, json_encode($checkMessages, JSON_UNESCAPED_UNICODE), 'w');

        $checkMessagesNew = [
        		'content'=>'pack_data_success',
        		'trans_params'=>[
        			//数组的key值就是翻译对应的key
					'pack_data_no_source'=>implode(',', $noDataSource),//无数据源
					'pack_data_no_result'=>implode(',', $noDataResult)//无结果
				]
        ];
        
        if($is_last)
        {
            //更新打包数量，累加 
            $attributes = [
                'pack_item_succ' => $last_succ_num,
                'pack_item_fail' =>  $last_num - $last_succ_num,
                'updated_at' => time() - $pack['updated_at']
            ];
            Pack::updateAllCounters($attributes, ['id' => $pack['id']]);
        }
        
        return FormatHelper::result([
            'checkFile' => str_replace($uploadfileRoot, '', $checkFilePath), 
            'packFile' => str_replace($uploadfileRoot, '', $batchFilePath),
        	'checkMessage' => $checkMessagesNew
        ]);
    }

}