<?php
namespace common\components\exportHandler\image;

use common\components\ImageProcessor;
use common\components\ImageHandler;
use common\helpers\ArrayHelper;
use common\models\Setting;
use common\models\DataResult;
use common\models\Pack;
use Yii;
use yii\base\Component;
use common\helpers\FileHelper;
use common\helpers\FormatHelper;
use common\helpers\ImageHelper;
use common\helpers\JsonHelper;
use common\helpers\ZipHelper;
use common\components\ProjectHandler;

/**
 * 将图片标注结果导出成mark图无标签Imagick版
 */
class MarkJpgNoLabelImagick extends Component
{

    /**
     * 执行导出脚本，导出mark图无标签(jpg)格式
     * 检测和执行同时进行，只检测时输出检测结果不生成文件不打包，打包数据检测结果和打包数据一起
     *
     * @param int $projectId 项目ID
     * @param int $batchId 批次ID
     * @param array $labelAndColors 标签和颜色数组
     * @return string[]|array[]|int[][]|string[][]|array[][]|boolean
     *
     */
    public static function run($args){
        $_logs = [];
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

        #$batchDirname = 'mark_no_label_'.$projectId.'_'.implode(',', $batchIds).'_'.date('YmdHis');
        if(count($batchIds) > 3){
            $batchDirname = Yii::t('app', 'pack_script_mark_jpg_no_label_imagick').'_'.$projectId.'_more_'.date('YmdHis');
        }else{
            $batchDirname = Yii::t('app', 'pack_script_mark_jpg_no_label_imagick').'_'.$projectId.'_'.implode(',', $batchIds).'_'.date('YmdHis');
        }
        $batchDirPath = $downloadfileRoot.'/'.$batchDirname;
        $batchFileName = $batchDirname.'.zip';
        $_logs['$batchFileName'] =  $batchFileName;
        $_logs['$batchDirname'] = $batchDirname;
        $_logs['$batchDirPath'] = $batchDirPath;

        //更新打包总数
        $attributes = [
            'pack_item_total' => count($dataIds),
            'pack_item_succ' => 0,
            'pack_item_fail' => 0,
            'updated_at' => time()
        ];
        Pack::updateAll($attributes, ['id' => $pack['id']]);

        $checkMessages = [];
        $checkMessages['$projectId'] = $projectId;
        $checkMessages['$batchIds'] = $batchIds;

        //分组
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

            $list = DataResult::find()->select(['data_id','data', 'result'])
                ->where(['project_id' => $projectId])
                ->andWhere(['in', 'data_id', $ids_])
                ->asArray()->all();
            $succ_num = 0;
            foreach ($list as $v)
            {
                if (empty($v['data']))
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data empty  '.json_encode($_logs));
                    $checkMessages['error'][$v['data_id']][]= Yii::t('app', 'pack_data_no_source');
                    continue;
                }

                if (empty($v['result']))
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' result empty  '.json_encode($_logs));
                    $checkMessages['error'][$v['data_id']][]= Yii::t('app', 'pack_data_no_result' );
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
                    $checkMessages['error'][$v['data_id']][]= Yii::t('app', 'pack_data_image_url_empty' );
                    continue;
                }
                //如果是http
                if (strpos($dataVal, '//') !== false)
                {
                    $imagepath = $dataVal;
                    $imagePath = parse_url($imagepath, PHP_URL_PATH);
                    $_logs['$imagePath'] = $imagePath;
                    $item_save_path = $batchDirPath . '/'.trim($imagePath, '/');
                }
                //如果是本地硬盘路径
                else
                {
                    $imagepath = $resourceRoot . '/'.ltrim($dataVal, '/');
                    if (!file_exists($imagepath))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file notexist '.json_encode($_logs));
                        $checkMessages['error'][$v['data_id']][]= Yii::t('app', 'pack_data_image_not_exist' ).$imagepath  ;
                        continue;
                    }
                    else
                    {
                        $imageDirname = FileHelper::dirpath($imagepath);
                        $_logs['$imageDirname'] = $imageDirname;
                        $imageFolder = trim(str_replace($resourceRoot , '', $imageDirname), '/');
                        $imageFolder = strstr($imageFolder,'/');
                        $item_save_path = $batchDirPath . $imageFolder;
                    }
                }

                //没有结果
                $result = ProjectHandler::fetchResult($resultArr);
                $resultData = empty($result['data']) ? [] : $result['data'];
                
                if(isset($configs['RemoveEmpty']) && $configs['RemoveEmpty'] && (!is_array($resultData)||count($resultData) ==  0)){
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'result empty '.json_encode($_logs));
                    $checkMessages['error'][$v['data_id']][]= Yii::t('app', 'pack_data_no_result' );
                    continue;
                }

                //生成mark图
                $imageName = FileHelper::filebasename($imagepath);
                $content = ImageHelper::image_get_content($imagepath);
                $imageHandler = new ImageHandler();
                $imageHandler->loadBlob($content);
                if ($imageHandler->getError())
                {
                    var_dump($imageHandler->getError());
                    exit();
                }
                //$imageHandler->setFormat('jpg');
                $imageHandler->mark($resultData);
                //生成mark图
                $imageHandler->save($item_save_path . '/' . $imageName);
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
                return FormatHelper::result('', 'error', 'error');
            }
        }

        //pack 文件
        $packfile = $downloadfileRoot . '/' . $batchFileName;
        $checkFilePath = $downloadfileRoot .'/'.$batchDirname.'.check.txt';

        ZipHelper::zip($batchDirPath, $packfile);
        FileHelper::rmdir($batchDirPath);
        if (!file_exists($packfile)) {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . '  file_exists error  ' . json_encode($_logs));
            return FormatHelper::result('', 'data_pack_nodata', 'data_pack_nodata');
        } else {
            Yii::info(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . '  downloadfile  ' . json_encode($_logs));
            $checkMessages['packFile'] = Yii::t('app', 'data_pack_download_success' ).$batchFileName ;
        }

        //生成检测文件
        FileHelper::file_write($checkFilePath, json_encode($checkMessages, JSON_UNESCAPED_UNICODE), 'w');
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
            'checkFile'=>str_replace($uploadfileRoot, '', $checkFilePath),
            'packFile'=>str_replace($uploadfileRoot, '', $packfile)
        ]);

    }

}