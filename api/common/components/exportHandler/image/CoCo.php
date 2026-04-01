<?php
namespace common\components\exportHandler\image;

use common\helpers\ArrayHelper;
use common\helpers\FileHelper;
use common\helpers\FormatHelper;
use common\helpers\JsonHelper;
use common\components\ProjectHandler;
use common\models\Setting;
use common\models\Data;
use common\models\DataResult;
use common\models\Project;
use Yii;
use yii\base\Component;
use common\models\Pack;

/**
 * 将图片标注结果导出成CoCo(只适用于图片标注)格式
 */
class CoCo extends Component
{

    /**
     * 执行导出脚本，导出CoCo(只适用于图片标注)格式
     * 检测和执行同时进行，只检测时输出检测结果不生成文件不打包，打包数据检测结果和打包数据一起
     *
     * @param int $projectId 项目ID
     * @param int $batchId 批次ID
     * @param int $stepId 分步ID
     * @return string[]|array[]|int[][]|string[][]|array[][]|boolean
     *
     */
    public static function run($args){
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
        
        $resourceRoot = Setting::getResourceRootPath();
        $uploadfileRoot = Setting::getUploadRootPath();
        $downloadfileRoot = Setting::getDownloadPath($project['user_id'], $projectId);

        $batchDirname = Yii::t('app', 'pack_script_image_CoCo').'_'.$projectId.'_'.implode(',', $batchIds).'_'.date('YmdHis');
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


        $contents = [];
        $checkMessages = [];
        $checkMessages['$projectId'] = $projectId;
        $checkMessages['$batchIds'] = $batchIds;

        $noDataSource = [];
        $noDataResult = [];
        $checkMessagesNew = '';//返回的错误信息，成功或者失败

        //分组
        $groups = array_chunk($dataIds, 100);
        $group_count = count($groups);
        $is_last = false;
        foreach ($groups as $i => $ids_)
        {
            //循环一次处理100条
            //进度, 方便查看日志了解进度
            $ids_count = count($ids_);
            $_logs['$list.item'] = $ids_count.':'.$i.'/'.$group_count;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' loop  '.json_encode($_logs));

            $list = DataResult::find()->select(['data_id','data', 'result'])
                ->where(['project_id' => $projectId])
                ->andWhere(['in', 'data_id', $ids_])
                ->asArray()->all();
            $count = count($list);
            $succ_num = 0;
            foreach ($list as $k =>  $v)
            {
                //进度, 方便查看日志了解进度
                $_logs['$list.item'] = $k.'/'.$count.':'.$i.'/'.$group_count;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' loop  '.json_encode($_logs));
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
                $dataVal = current($dataArr);

                //没有图片地址
                if (!isset($dataVal))
                {
                    $_logs['$v'] = $v;
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataArr image_url null '.json_encode($_logs));
                    $checkMessages['error'][$v['data_id']][]= '图片地址为空';
                    continue;
                }
                //如果是http
                if (strpos($dataVal, '//') !== false)
                {
                    $imagepath = $dataVal;
                    $imagePath = parse_url($imagepath, PHP_URL_PATH);
                    $_logs['$imagePath'] = $imagePath;
                    $imageFolder = '';
                    $item_save_path = $batchDirPath . '/'.trim($imagePath, '/');
                }
                //如果是本地硬盘路径
                else
                {
                    $imagepath = $resourceRoot . '/'.ltrim($dataVal, '/');
                    if (!file_exists($imagepath))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file notexist '.json_encode($_logs));
                        $checkMessages['error'][$v['data_id']][]= '{$imagepath}的图片不存在';
                        continue;
                    }
                    else
                    {
                        $imageDirname = FileHelper::dirpath($imagepath);
                        $_logs['$imagePath'] = $imageDirname;
                        $imageFolder = trim(str_replace($resourceRoot . '/' . $project['user_id'].'/'.$projectId, '', $imageDirname), '/');
                        $imageFolder = strstr($imageFolder,'/');
                        $item_save_path = $batchDirPath . $imageFolder;
                    }
                }


                $fileInfo = pathinfo($imagepath);
                $imageType = $fileInfo['extension'];
                $imageName = $fileInfo['filename'];


                list($imageWidth, $imageHeight) = @getimagesize($imagepath);
                if (!$imageWidth || !$imageHeight)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'image info error '.json_encode($_logs));
                    $checkMessages['error'][$v['data_id']][]= '{$imagepath}图片信息获取失败';
                    continue;
                }

                //----------------------------------
                $imageId = md5_file($imagepath);

                //没有结果
                $itemResult = [];
                $itemResult['segmentation'] = [];
                $itemResult['num_keypoints'] = 0;
                $itemResult['keypoints'] = [];
                $itemResult['label'] = [];
                $itemResult['type'] = [];
                $itemResult['category'] = [];
                $itemResult['iscrowd'] = 0;
                $itemResult['image_id'] = $v['data_id'];
                //图片转录
                $textItemResult = [];
                $textItemResult['label'] = [];
                $textItemResult['type'] = [];
                $textItemResult['caption'] = [];
                $textItemResult['image_id'] = $v['data_id'];

                if (isset($resultArr['result']) && isset($resultArr['result']['data']))
                {
                    $resultData = $resultArr['result']['data'];
                }
                elseif (isset($resultArr['data']))
                {
                    $resultData = $resultArr['data'];
                }
                else
                {
                    $resultData = $resultArr;
                }
                $isText = false;
                $isKeyPoint = false;
                if ($resultData)
                {
                    foreach ($resultData as  $k_l => $label)
                    {

                        if(isset($label[0]['header'])){
                            $isText = true;
                            foreach($label as $labelVal){
                                $textItemResult['label'][] = $labelVal['header'];
                                $textItemResult['type'][] = $labelVal['type'];
                                $textItemResult['caption'][] = $labelVal['value'];
                            }
                        }
                        else{
                            if (!isset($label['type']))
                            {
                                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'label type error '.json_encode($_logs));
                                $checkMessages['error'][$v['data_id']][]= "缺少标签类型";
                                continue;
                            }
                            //点，矩形，多边形，其他
                            if(isset($label['nodes'])){
                                $isKeyPoint = true;
                                $itemResult['num_keypoints'] = count($label['nodes']);
                                $labelItem = [];
                                $labelItem['points'] = [];
                                foreach ($label['nodes'] as $pointVal)
                                {
                                    $labelItem['points'][] = $pointVal['points'][0] * $imageWidth;
                                    $labelItem['points'][] = $pointVal['points'][1] * $imageHeight;
                                    $itemResult['keypoints'][] = $pointVal['points'][0] * $imageWidth;
                                    $itemResult['keypoints'][] = $pointVal['points'][1] * $imageHeight;
                                    $itemResult['keypoints'][] = $pointVal['visibility']?2:1;
                                    $itemResult['label'][] = ProjectHandler::formatLabelValue($pointVal['label']);
                                    $itemResult['type'][] = $pointVal['type'];
                                    $pointVal['category'] = ProjectHandler::formatLabelValue($pointVal['category']);
                                    $itemResult['category'][] = $pointVal['category'];
                                }
                                $itemResult['segmentation'][$v['data_id']][$k_l] = $labelItem['points'];
                            }elseif ($label['type'] == 'point') {
                                $labelItem = [];
                                $labelItem['points'][] = $label['points'][0] * $imageWidth;
                                $labelItem['points'][] = $label['points'][1] * $imageHeight;
                                $itemResult['label'][] = ProjectHandler::formatLabelValue($label['label']);
                                $itemResult['type'][] = $label['type'];
                                $itemResult['segmentation'][$v['data_id']][$k_l] = $labelItem['points'];
                                $label['category'] = ProjectHandler::formatLabelValue($label['category']);
                                $itemResult['category'][] = $label['category'];
                            }
                            elseif ($label['type'] == 'rect')
                            {
                                $labelItem = [];
                                $labelItem['points'] = [];
                                //检查最小坐标点,最大坐标点
                                $left_top_point = [];
                                $right_bottom_point = [];
                                foreach ($label['points'] as $pointVal)
                                {
                                    if (!isset($left_top_point['x']) || $pointVal['x'] < $left_top_point['x'])
                                    {
                                        $left_top_point['x'] = $pointVal['x'];
                                    }
                                    if (!isset($left_top_point['y']) || $pointVal['y'] < $left_top_point['y'])
                                    {
                                        $left_top_point['y'] = $pointVal['y'];
                                    }

                                    if (!isset($right_bottom_point['x']) || $pointVal['x'] > $right_bottom_point['x'])
                                    {
                                        $right_bottom_point['x'] = $pointVal['x'];
                                    }
                                    if (!isset($right_bottom_point['y']) || $pointVal['y'] > $right_bottom_point['y'])
                                    {
                                        $right_bottom_point['y'] = $pointVal['y'];
                                    }
                                    $labelItem['points'][] = $pointVal['x'] * $imageWidth;
                                    $labelItem['points'][] = $pointVal['y'] * $imageHeight;
                                }

                                if ($left_top_point['x'] < 0)
                                {
                                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'point x  < zero '.json_encode($_logs));
                                    $checkMessages['error'][$v['data_id']][]= "left_top_point_x 小于零 : {$left_top_point['x']} ";
                                    $left_top_point['x'] = 0;
                                }

                                if ($left_top_point['y'] < 0)
                                {
                                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'point y  < zero '.json_encode($_logs));
                                    $checkMessages['error'][$v['data_id']][]= "left_top_point_y 小于零 : {$left_top_point['y']} ";
                                    $left_top_point['y'] = 0;
                                }

                                if ($right_bottom_point['x'] < 0)
                                {
                                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'point x  < zero '.json_encode($_logs));
                                    $checkMessages['error'][$v['data_id']][]= "right_bottom_point_x 小于零 : {$right_bottom_point['x']}";
                                    $right_bottom_point['x'] = 0;
                                }

                                if ($right_bottom_point['y'] < 0)
                                {
                                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'point y  < zero '.json_encode($_logs));
                                    $checkMessages['error'][$v['data_id']][]= "right_bottom_point_y 小于零 : {$right_bottom_point['y']} ";
                                    $right_bottom_point['y'] = 0;
                                }

                                if ($right_bottom_point['x'] - $left_top_point['x'] < 0 || $right_bottom_point['y'] - $left_top_point['y'] < 0)
                                {
                                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'wrong point '.json_encode($_logs));
                                    $checkMessages['error'][$v['data_id']][]= "wrong point";
                                    continue;
                                }

                                $width = abs($right_bottom_point['x'] - $left_top_point['x']) * $imageWidth;
                                $height = abs($right_bottom_point['y'] - $left_top_point['y']) * $imageHeight;

                                if ($width < 3)
                                {
                                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'rect width < 3  '.json_encode($_logs));
                                    $checkMessages['error'][$v['data_id']][]= "rect width < 3 ";
                                    continue;
                                }

                                if ($height < 3)
                                {
                                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'rect height < 3'.json_encode($_logs));
                                    $checkMessages['error'][$v['data_id']][]= "rect height < 3";
                                    continue;
                                }
                                $itemResult['label'][] = ProjectHandler::formatLabelValue($label['label']);
                                $itemResult['type'][] = $label['type'];
                                $itemResult['segmentation'][$v['data_id']][$k_l] = $labelItem['points'];
                                $label['category'] = ProjectHandler::formatLabelValue($label['category']);
                                $itemResult['category'][] = $label['category'];
                            }
                            elseif ($label['type'] == 'polygon')
                            {
                                $labelItem = [];
                                $labelItem['points'] = [];
                                foreach ($label['points'] as $point_pair)
                                {
                                    $point_pair_x = $point_pair['x'] * $imageWidth;
                                    if ($point_pair_x < 0)
                                    {
                                        $point_pair_x = 0;
                                    }
                                    if ($point_pair_x > $imageWidth)
                                    {
                                        $point_pair_x = $imageWidth;
                                    }
                                    $point_pair_y = $point_pair['y'] * $imageHeight;
                                    if ($point_pair_y < 0)
                                    {
                                        $point_pair_y = 0;
                                    }
                                    if ($point_pair_y > $imageHeight)
                                    {
                                        $point_pair_y = $imageHeight;
                                    }
                                    $labelItem['points'][] = $point_pair_x;
                                    $labelItem['points'][] = $point_pair_y;
                                }
                                $itemResult['label'][] = ProjectHandler::formatLabelValue($label['label']);
                                $itemResult['type'][] = $label['type'];
                                $itemResult['segmentation'][$v['data_id']][$k_l] = $labelItem['points'];
                                $label['category'] = ProjectHandler::formatLabelValue($label['category']);
                                $itemResult['category'][] = $label['category'];
                                $checkMessages['polygonNumbers'] += 1;
                            }
                            else{
                                $labelItem = [];
                                $labelItem['points'] = [];
                                foreach ($label['points'] as $pointVal)
                                {
                                    $labelItem['points'][] = $pointVal['x'] * $imageWidth;
                                    $labelItem['points'][] = $pointVal['y'] * $imageHeight;
                                }
                                $itemResult['label'][] = ProjectHandler::formatLabelValue($label['label']);
                                $itemResult['type'][] = $label['type'];
                                $itemResult['segmentation'][$v['data_id']][$k_l] = $labelItem['points'];
                                $label['category'] = ProjectHandler::formatLabelValue($label['category']);
                                $itemResult['category'][] = $label['category'];
                            }
                        }
                    }
                    isset($itemResult['segmentation'][$v['data_id']])&&$itemResult['segmentation'][$v['data_id']] = array_values($itemResult['segmentation'][$v['data_id']]);
                }
                if(!$isKeyPoint){
                    unset($itemResult['num_keypoints']);
                    unset($itemResult['keypoints']);
                }
                if($isText&&count($textItemResult['caption'])){
                    $batchResult['images'][] = [
                        'id' => $v['data_id'],
                        'width' => $imageWidth,
                        'height' => $imageHeight,
                        'file_name'=> $fileInfo['basename'],
                        'folder' => $imageFolder,
                        'date_captured'=> date('Y-m-d H:i:s')
                    ];
                    $batchResult['annotations'][] = $textItemResult;
                }elseif(!$isText&&count($itemResult['segmentation'])){
                    $batchResult['images'][] = [
                        'id' => $v['data_id'],
                        'width' => $imageWidth,
                        'height' => $imageHeight,
                        'file_name'=> $fileInfo['basename'],
                        'folder' => $imageFolder,
                        'date_captured'=> date('Y-m-d H:i:s')
                    ];
                    $itemResult['segmentation'] = array_values($itemResult['segmentation']);
                    $batchResult['annotations'][] = $itemResult;
                }
                $batchResult['categories'] = [];
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
                $last_num = $ids_count;
                $last_succ_num = $succ_num;
                $is_last = true;
            }
            
            //检查打包记录是否异常
            if (!Pack::allowProcesses($pack['id']))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'  cleanUpProcesses  '.json_encode($_logs));
                Pack::cleanUpProcesses();
                return FormatHelper::result('', 'error', 'error');
            }
        }

        if(isset($config_arr['cnEscape']) && !$configs['cnEscape']){
            $fileContent = json_encode($batchResult,JSON_UNESCAPED_UNICODE);
        }else{
            $fileContent = json_encode($batchResult);
        }
        FileHelper::file_write($batchFilePath, $fileContent, 'w');
        var_dump($batchFilePath);
        if (!file_exists($batchFilePath))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'  downloadfile error  '.json_encode($_logs));
            return FormatHelper::result('', 'data_pack_nodata', '下载文件保存失败');
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