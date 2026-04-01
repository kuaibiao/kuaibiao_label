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
use common\helpers\ZipHelper;

/**
 * 将图片标注结果导出成pascal voc(只适用于图片标注)格式
 */
class PascalVoc extends Component
{

    /**
     * 执行导出脚本，导出pascal voc(只适用于图片标注)格式
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

        $batchDirname = Yii::t('app', 'pack_script_image_PascalVoc').'_'.$projectId.'_'.implode(',', $batchIds).'_'.date('YmdHis');
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

        //分组
        $groups = array_chunk($dataIds, 100);
        $group_count = count($groups);
        $is_last = false;
        foreach ($groups as $k => $ids_)
        {
            $count = count($ids_);

            $list = DataResult::find()->select(['data', 'result','data_id'])
                ->where(['project_id' => $projectId])
                ->andWhere(['in', 'data_id', $ids_])
                ->asArray()->all();
            $succ_num = 0;
            foreach ($list as $v)
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
                $dataVal = current($dataArr);

                //没有图片地址
                if (!isset($dataVal))
                {
                    $_logs['$v'] = $v;
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataArr image_url null '.json_encode($_logs));
                    $_messages[$v['data_id']]['nodata'][]= "图片地址为空";
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
                        $checkMessages['error'][$v['data_id']][]= "{$imagepath}的图片不存在";
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
                    $checkMessages['error'][$v['data_id']][] = "{$imagepath}图片信息获取失败";
                    continue;
                }

                //----------------------------------

                //没有结果
                $itemResult = [];

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

                if ($resultData && is_array($resultData) && count($resultData) )
                {
                    foreach ($resultData as  $label)
                    {
                        if (!isset($label['type']))
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'label type error '.json_encode($_logs));
                            $checkMessages['error'][$v['data_id']][]= "缺少标签类型";
                            continue;
                        }
                        $label['label'] = ProjectHandler::formatLabelValue($label['label']);

                        if ($label['type'] == 'point')
                        {
                            $itemResult[] = [
                                'name' => $label['label'],
                                'pose' => 'Unspecified',
                                'type' => 'point',
                                'truncated' => 0,
                                'difficult' => 0,
                                'point' => [
                                    'x' => $label['points'][0] * $imageWidth,
                                    'y' => $label['points'][1] * $imageHeight,
                                ]
                            ];
                        }
                        elseif ($label['type'] == 'rect')
                        {
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

                            $itemResult[] = [
                                'name' => $label['label'],
                                'pose' => 'Unspecified',
                                'type' => 'rect',
                                'truncated' => 0,
                                //'difficult' => 0,
                                'difficult' => strpos($label['label'], '-difficult') ? 1 : 0,
                                'bndbox' => [
                                    'xmin' => $left_top_point['x'] * $imageWidth,
                                    'ymin' => $left_top_point['y'] * $imageHeight,
                                    'xmax' => $right_bottom_point['x'] * $imageWidth,
                                    'ymax' => $right_bottom_point['y'] * $imageHeight,
                                ],
                                'width' => $width,
                                'height' => $height
                            ];
                        }
                        elseif ($label['type'] == 'polygon')
                        {
                            $labelItem = [];
                            $labelItem['name'] = $label['label'];
                            $labelItem['pose'] = 'Unspecified';
                            $labelItem['type'] = 'polygon';
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

                                $labelItem['points'][] = [
                                    'x' => $point_pair_x,
                                    'y' => $point_pair_y,
                                ];
                            }

                            $itemResult[] = $labelItem;
                        }
                    }
                }else{
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'not data'.json_encode($_logs));
                    $checkMessages['error'][$v['data_id']][]= "没有标注结果";
                    continue;
                }

                //--------------------------------

                $xmlPath = $item_save_path . '/' . $imageName . '.' . $imageType . '.xml';

                $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
                $xmlContent .= '<annotation>' . "\n";
                $xmlContent .= '<folder>' . $imageFolder . '</folder>' . "\n";
                $xmlContent .= '<filename>' . $imageName . '.' . $imageType . '</filename>' . "\n";
                $xmlContent .= '<path>' . $imageFolder . '</path>' . "\n";
                $xmlContent .= '<source><database>Unknown</database></source>' . "\n";
                $xmlContent .= '<amount>' . count($itemResult) . '</amount>' . "\n";
                $xmlContent .= '<size><width>' . $imageWidth . '</width><height>' . $imageHeight . '</height><depth>3</depth></size>' . "\n";
                $xmlContent .= '<segmented>0</segmented>' . "\n";
                if ($itemResult) {
                    //$xmlContent .= '<object>'."\n";
                    foreach ($itemResult as $label => $shape) {
                        $xmlContent .= '<object>' . "\n";
                        foreach ($shape as $k_ => $v_) {
                            //bndbox
                            $xmlContent .= '<' . $k_ . '>'. "\n";
                            if (is_array($v_)) {
                                //$xmlContent .= '<points>'."\n";
                                //xmin
                                foreach ($v_ as $k__ => $v__) {
                                    if(is_numeric($k__)){
                                        $l_k = 'points'.$k__;
                                    }else{
                                        $l_k = $k__;
                                    }
                                    $xmlContent .= '<' . $l_k . '>'. "\n";
                                    if (is_array($v__)) {
                                        foreach ($v__ as $k4 => $v4) {
                                            $xmlContent .= '<' . $k4 . '>' . $v4 . '</' . $k4 . '>' . "\n";
                                        }
                                    } else {
                                        $xmlContent .= $v__;
                                    }
                                    $xmlContent .= '</' . $l_k . '>' . "\n";
                                }
                                //$xmlContent .= '</points>'."\n";
                            } else {
                                $xmlContent .= $v_;
                            }
                            $xmlContent .= '</' . $k_ . '>' . "\n";
                        }
                        $xmlContent .= '</object>' . "\n";
                    }
                    //$xmlContent .= '</labels>'."\n";
                }
                $xmlContent .= '</annotation>' . "\n";

                FileHelper::file_write($xmlPath, FormatHelper::format_xml($xmlContent), 'w');

                //----------------------------------
                $succ_num++;
            }
            if($group_count != $k+1)
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
                return FormatHelper::result('', 'error', 'error');
            }
        }

        //pack 文件
        $packfile = $downloadfileRoot . '/' . $batchDirname . '.zip';
        $checkFilePath = $downloadfileRoot .'/'.$batchDirname.'.check.txt';

        ZipHelper::zip($batchDirPath, $packfile);
        FileHelper::rmdir($batchDirPath);
        if (!file_exists($packfile)) {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . '  file_exists error  ' . json_encode($_logs));
            return FormatHelper::result('', 'data_pack_nodata', '下载文件保存失败');
        } else {
            Yii::info(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . '  downloadfile  ' . json_encode($_logs));
            $checkMessages['packFile'] = '下载文件保存成功:' . $packfile;
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
            'packFile' => str_replace($uploadfileRoot, '', $packfile),
            'checkMessage' => $checkMessagesNew
        ]);

    }

}