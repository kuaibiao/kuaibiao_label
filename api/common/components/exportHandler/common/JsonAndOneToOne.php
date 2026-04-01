<?php
namespace common\components\exportHandler\common;

use common\components\ProjectHandler;
use common\helpers\ArrayHelper;
use common\helpers\LabelHelper;
use common\models\Pack;
use Yii;
use yii\base\Component;
use common\models\Setting;
use common\models\DataResult;
use common\helpers\FileHelper;
use common\helpers\FormatHelper;
use common\helpers\JsonHelper;
use common\helpers\ZipHelper;
use common\helpers\StringHelper;
use common\models\Data;
use common\models\User;

/**
 *
 * 导出json格式的原始结果
 *
 *
 */
class JsonAndOneToOne extends Component
{

    /**
     * 执行导出脚本，导出json格式
     * 检测和执行同时进行，只检测时输出检测结果不生成文件不打包，打包数据检测结果和打包数据一起
     *
     * @param int $projectId 项目ID
     * @param int $batchId 批次ID
     * @param int $stepId 分步ID
     * @return string[]|array[]|int[][]|string[][]|array[][]|boolean
     *
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
        if(empty($args['category']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $params.pack.category empty '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $category = $args['category'];

        $resourceRoot = Setting::getResourceRootPath();
        $uploadfileRoot = Setting::getUploadRootPath();
        $downloadfileRoot = Setting::getDownloadPath($project['user_id'], $projectId);

        if(count($batchIds) > 3){
            $batchDirname = Yii::t('app', 'pack_script_common_JsonAndOneToOne').'_'.$projectId.'_more_'.date('YmdHis');
        }else{
            $batchDirname = Yii::t('app', 'pack_script_common_JsonAndOneToOne').'_'.$projectId.'_'.implode(',', $batchIds).'_'.date('YmdHis');
        }
        $batchDirPath = $downloadfileRoot .'/'.$batchDirname;
        $checkFilePath = $downloadfileRoot .'/'.$batchDirname.'.check.txt';
        $batchFileName = $batchDirname.'.zip';
        $_logs['$batchFileName'] =  $batchFileName;
        $_logs['$batchDirname'] = $batchDirname;
        $_logs['$batchDirPath'] = $batchDirPath;
        $_logs['$checkFilePath'] = $checkFilePath;

        //更新打包总数
        $attributes = [
            'pack_item_total' => count($dataIds),
            'pack_item_succ' => 0,
            'pack_item_fail' => 0,
            'updated_at' => time()
        ];
        Pack::updateAll($attributes, ['id' => $pack['id']]);

        $contents = [];
        $checkMessages = [];
        $checkMessages['$projectId'] = $projectId;
        //$checkMessages['$dataIds'] = $dataIds;
        $checkMessages['$batchIds'] = $batchIds;
        $checkMessages['errorTotal'] = 0;
        $checkMessages['rectNumbers'] = 0;
        $checkMessages['polygonNumbers'] = 0;
        $checkMessages['pointsNumbers'] = 0;
        $checkMessages['quadrangleNumbers'] = 0;
        $checkMessages['lineNumbers'] = 0;
        $checkMessages['unclosedpolygonNumbers'] = 0;
        $checkMessages['splinecurveNumbers'] = 0;
        $checkMessages['textNumbers'] = 0;
        $checkMessages['labelNumbers'] = [];

        $noDataSource = [];
        $noDataResult = [];
        $rectNumbers = 0;
        $polygonNumbers = 0;
        $pointsNumbers = 0;
        $quadrangleNumbers = 0;
        $lineNumbers = 0;
        $unclosedpolygonNumbers = 0;
        $splinecurveNumbers = 0;
        $labelNumbers = [];
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
            $_logs['$list.item'] = $count . ':' . $i . '/' . $group_count;
            Yii::info(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' loop  ' . json_encode($_logs));

            $list = DataResult::find()
                ->where(['project_id' => $projectId])
                ->andWhere(['in', 'data_id', $ids_])
                ->asArray()->all();

            $succ_num = 0;
            foreach ($list as $k => $v)
            {
                $dataInfo = Data::find()->where(['id' => $v['data_id']])->limit(1)->asArray()->one();
                if (empty($dataInfo)) {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data empty  '.json_encode($_logs));
                    $checkMessages['error'][$v['data_id']][]= '没有数据源';
                    $noDataSource[] = $v['data_id'];
                    continue;
                }
                
                //标注人
                $userInfo = [];
                if ($dataInfo['data_key']) {
                    $userInfo = User::find()->where(['id' => $dataInfo['data_key']])->limit(1)->asArray()->one();
                }
                $userStr = '';
                if ($userInfo) {
                    $userStr = $userInfo['nickname'].'-'.strstr(StringHelper::emailName($userInfo['email']), '-', true);
                }
                
                if (empty($v['data']))
                {
                    Yii::info(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' data empty  ' . json_encode($_logs));
                    $checkMessages['error'][$v['data_id']][] = [ 'pack_data_no_source' => $userStr  ];
                    $noDataSource[] = $v['data_id'];
                    continue;
                }

                if (empty($v['result']))
                {
                    Yii::info(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' result empty  ' . json_encode($_logs));
                    $checkMessages['error'][$v['data_id']][] = [ 'pack_result_no_result' => $userStr  ];
                    $noDataResult[] = $v['data_id'];
                    continue;
                }

                $dataArr = JsonHelper::json_decode_all($v['data']);
                $resultArr = JsonHelper::json_decode_all($v['result']);

                $jsonName = $v['data_id'].'.json';
                $savePath = $batchDirPath;
                if(!empty($category['required_input_field']))
                {
                    $categoryField = $category['required_input_field'];
                    if(!empty($dataArr[$categoryField]))
                    {
                        if(StringHelper::is_url($dataArr[$categoryField]))
                        {
                            $imagePath = parse_url($dataArr[$categoryField], PHP_URL_PATH);
                            $pathinfo = pathinfo($imagePath);
                            $_logs['$pathinfo'] = $pathinfo;
                            
                            $jsonName = $pathinfo['filename'].'.json';
                            //$dataInfo['data_key'] && ($jsonName = $dataInfo['data_key'].'/'.trim($jsonName, '/'));
                            
                            $imageDirname = $pathinfo['dirname'];
                            //$dataInfo['data_key'] && ($imageDirname = $dataInfo['data_key'].'/'.trim($imageDirname, '/'));
                            
                            if ($dataInfo['data_key']) {
                                $imageDirname = $userStr.'/'.trim($imageDirname, '/');
                            }
                            $savePath = $batchDirPath . '/' . trim($imageDirname, '/');
                        }
                        else if(StringHelper::is_relativepath($dataArr[$categoryField]))
                        {
                            $pathinfo = pathinfo($dataArr[$categoryField]);
                            $_logs['$pathinfo'] = $pathinfo;
                            
                            $jsonName = $pathinfo['filename'].'.json';
                            //$dataInfo['data_key'] && ($jsonName = $dataInfo['data_key'].'/'.trim($jsonName, '/'));
                            
                            $imageDirname = $pathinfo['dirname'];
                            $imageFolder = trim(str_replace($resourceRoot . '/' . $project['user_id'] . '/' . $projectId, '', $imageDirname), '/');
                            $imageFolder = trim(strstr($imageFolder, '/'), '/');
                            //$dataInfo['data_key'] && ($imageFolder = $dataInfo['data_key'].'/'.trim($imageFolder, '/'));
                            
                            if ($dataInfo['data_key']) {
                                $imageFolder = $userStr.'/'.trim($imageFolder, '/');
                            }
                            $savePath = $batchDirPath . '/' .$imageFolder;
                        }
                    }
                }
                $_logs['$jsonName'] = $jsonName;
                $_logs['$savePath'] = $savePath;

                //没有结果
                $result = ProjectHandler::fetchResult($resultArr);
                $resultData = empty($result['data']) ? [] : $result['data'];

                if(isset($configs['RemoveEmpty']) && $configs['RemoveEmpty'] && (!is_array($resultData)||count($resultData) ==  0 )){

                    $checkMessages['error'][$v['data_id']][]= [ 'pack_data_no_result' => $userStr  ];

                    $noDataResult[] = $v['data_id'];
                    continue;
                }

                if (is_array($resultData) && count($resultData)) {
                    //$resultArr['data'] = $resultData;
                    $text = [];
                    foreach ($resultData as $k_l => $label) {
                        if(!isset($label['type'])){
                            $checkMessages['no_type'][$jsonName][] = $label;
                            continue;
                        }
                        if(isset($label['label'])){
                            $labels = ProjectHandler::formatLabelValue($label['label']);
                            if(isset($checkMessages['labelNumbers'][$labels])){
                                $checkMessages['labelNumbers'][$labels] += 1;
                                $labelNumbers[$labels] += 1;
                            }else{
                                $checkMessages['labelNumbers'][$labels] = 1;
                                $labelNumbers[$labels] = 1;
                            }
                        }
                        if ($label['type'] == 'polygon') {
                            $checkMessages['polygonNumbers'] += 1;
                            $polygonNumbers += 1;
                        }elseif($label['type'] == 'rect'){
                            $checkMessages['rectNumbers'] += 1;
                            $rectNumbers += 1;
                        }elseif($label['type'] == 'point'){
                            $checkMessages['pointsNumbers'] += 1;
                            $pointsNumbers += 1;
                        }elseif($label['type'] == 'quadrangle'){
                            $checkMessages['quadrangleNumbers'] += 1;
                            $quadrangleNumbers += 1;
                        }elseif($label['type'] == 'line'){
                            $checkMessages['lineNumbers'] += 1;
                            $lineNumbers += 1;
                        }elseif($label['type'] == 'unclosedpolygon'){
                            $checkMessages['unclosedpolygonNumbers'] += 1;
                            $unclosedpolygonNumbers += 1;
                        }elseif($label['type'] == 'splinecurve'){
                            $checkMessages['splinecurveNumbers'] += 1;
                            $splinecurveNumbers += 1;
                        }

                        //框或多边形的转录内容相同时只统计一次
                        if(isset($label['text']) && !in_array($label['text'],$text))
                        {
                            $text[] = $label['text'];
                            $checkMessages['textNumbers'] += 1;
                        }
                    }
                    $result_data = $resultData;
                    if(isset($result_data[0]['index']) && isset($result_data[0]['result'])){
                        // 处理连续帧老格式数据
                        $result_list = [];
                        foreach($result_data as $r_v){
                            foreach($r_v['result'] as $re_v){
                                $re_v['frame'] = $r_v['index'];
                                $result_list[] = $re_v;
                            }
                        }
                        $resultArr['data'] = $result_list;
                    }
                    else{
                        $resultArr['data'] = $result_data;
                    }
                }
                
                // 去掉删除框的记录
                if(isset($resultArr['data_deleted'])){
                    unset($resultArr['data_deleted']);
                }
                if(isset($resultArr['workerstat'])){
                    unset($resultArr['workerstat']);
                }
                if(isset($resultArr['is_difficult'])){
                    unset($resultArr['is_difficult']);
                }
                if(isset($resultArr['verify'])){
                    unset($resultArr['verify']);
                }
                
                $contents = [
                    'data' => $dataArr,
                    'result' => $resultArr
                ];
                if(isset($configs['cnEscape']) && !$configs['cnEscape'])
                {
                    $fileContent = json_encode($contents,JSON_UNESCAPED_UNICODE);
                }
                else
                {
                    $fileContent = json_encode($contents);
                }

                $saveFile = $savePath . '/' . $jsonName;
                FileHelper::file_write($saveFile, $fileContent, 'w');
                $succ_num++;
            }

            if ($group_count != $i + 1)
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
                return FormatHelper::result('', 'error', 'pack_data_error');
            }
        }

        //pack 文件
        $packfile = $downloadfileRoot . '/' . $batchFileName;
        $checkFilePath = $downloadfileRoot . '/' . $batchDirname . '.check.txt';

        ZipHelper::zip($batchDirPath, $packfile);
        FileHelper::rmdir($batchDirPath);
        if (!file_exists($packfile))
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . '  file_exists error  ' . json_encode($_logs));
            //return FormatHelper::result('', 'data_pack_nodata', 'data_pack_nodata');
            return FormatHelper::result('', 'error', 'data_pack_nodata');
        }
        else
        {
            Yii::info(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . '  downloadfile  ' . json_encode($_logs));
            $checkMessages['packFile'] = [ 'data_pack_download_success' =>  $batchFileName];
        }

        //生成检测文件
        FileHelper::file_write($checkFilePath, json_encode($checkMessages, JSON_UNESCAPED_UNICODE), 'w');

        $checkMessagesNew = [
        		'content'=>'pack_data_success',
        		'trans_params'=>[
        				//数组的key值就是翻译对应的key
        				'pack_data_no_source'=>implode(',', $noDataSource),//无数据源
        				'pack_data_no_result'=>implode(',', $noDataResult),//无结果
        				'pack_data_rectNumbers'=>$rectNumbers,//
        				'pack_data_polygonNumbers'=>$polygonNumbers,//
        				'pack_data_pointsNumbers'=>$pointsNumbers,//
        				'pack_data_quadrangleNumbers'=>$quadrangleNumbers,//
        				'pack_data_lineNumbers'=>$lineNumbers,//
        				'pack_data_unclosedpolygonNumbers'=>$unclosedpolygonNumbers,//
        				'pack_data_splinecurveNumbers'=>$splinecurveNumbers,//
        				'pack_data_labelNumbers'=>json_encode($labelNumbers),//
        		]
        ];

        if ($is_last)
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
            'packFile' => str_replace($uploadfileRoot, '', $packfile),
        	'checkMessage' => $checkMessagesNew
        ]);
    }

}