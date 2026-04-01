<?php
namespace common\components\exportHandler\image;

use common\helpers\ArrayHelper;
use common\helpers\FileHelper;
use common\helpers\FormatHelper;
use common\helpers\JsonHelper;
use common\components\ImageProcessor;
use common\models\Setting;
use common\models\Data;
use common\models\DataResult;
use common\models\Project;
use Yii;
use yii\base\Component;
use common\models\Pack;
use common\helpers\ImageHelper;
use common\helpers\ZipHelper;
use common\models\User;
use common\helpers\StringHelper;

/**
 * 将图片标注结果导出成mark图有标签(jpg)格式
 */
class MarkJpgHasLabelAllPerson extends Component
{

    /**
     * 执行导出脚本，导出mark图有标签(jpg)格式
     * 检测和执行同时进行，只检测时输出检测结果不生成文件不打包，打包数据检测结果和打包数据一起
     *
     * @param int $projectId 项目ID
     * @param int $batchId 批次ID
     * @param array $labelAndColors 标签和颜色数组
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

        $batchDirname = Yii::t('app', 'pack_script_mark_image_with_tag').'_'.$projectId.'_'.implode(',', $batchIds).'_'.date('YmdHis');
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
                $_logs['$dataArr'] = $dataArr;

                $dataKey = key($dataArr);
                $dataVal = current($dataArr);

                //没有图片地址
                if (!isset($dataVal))
                {
                    $_logs['$v'] = $v;
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataArr image_url null '.json_encode($_logs));
                    $checkMessages['error'][$v['data_id']][]= "图片地址字段是空值";
                    continue;
                }

                //如果是http
                if (strpos($dataVal, '//') !== false)
                {
                    $imagepath = $dataVal;
                    $imagePath = parse_url($imagepath, PHP_URL_PATH);
                    $_logs['$imagePath'] = $imagePath;
                    
                    if ($dataInfo['data_key']) {
                        $imagePath = $userStr.'/'.trim($imagePath, '/');
                    }
                    $item_save_path = $batchDirPath . '/'.trim($imagePath, '/');
                }
                //如果是本地硬盘路径
                else
                {
                    $imagepath = $resourceRoot . '/'.ltrim($dataVal, '/');
                    $_logs['$imagepath'] = $imagepath;
                    if (!file_exists($imagepath))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file notexist '.json_encode($_logs));
                        $checkMessages['error'][$v['data_id']][]= "{$imagepath}的图片不存在";
                        continue;
                    }
                    else
                    {
                        $imageDirname = FileHelper::dirpath($imagepath);
                        $_logs['$imageDirname'] = $imageDirname;
                        $imageFolder = trim(str_replace($resourceRoot . '/' . $project['user_id'].'/'.$projectId, '', $imageDirname), '/');
                        //$dataInfo['data_key'] && ($imageFolder = $dataInfo['data_key'].'/'.trim($imageFolder, '/'));
                        
                        if ($dataInfo['data_key']) {
                            $imageFolder = $userStr.'/'.trim($imageFolder, '/');
                        }
                        
                        $item_save_path = $batchDirPath . '/' . $imageFolder;
                    }
                }

                //没有结果
                $resultData = [];
                if (isset($resultArr['data']))
                {
                    $resultData = $resultArr['data'];
                }
                elseif (isset($resultArr['result']) && isset($resultArr['result']['data']))
                {
                    $resultData = $resultArr['result']['data'];
                }
                if(isset($config_arr['RemoveEmpty']) && $configs['RemoveEmpty'] && (!is_array($resultData)||count($resultData) ==  0 || (isset($resultData[0]['points']) && is_null($resultData[0]['points'][0]['x'])))){
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'result empty '.json_encode($_logs));
                    $checkMessages['error'][$v['data_id']][]= "没有标注结果";
                    continue;
                }
                //生成mask图
                $imageName = FileHelper::filebasename($imagepath);
                $imageString = ImageHelper::image_get_content($imagepath);

                //生成mask图
                ImageProcessor::image_mark_from_string(
                    $imageName,
                    $imageString,
                    $resultData,
                    $item_save_path,
                    true);
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