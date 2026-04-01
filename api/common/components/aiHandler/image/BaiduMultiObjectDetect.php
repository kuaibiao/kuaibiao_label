<?php 
namespace common\components\aiHandler\image;

use Yii;
use yii\base\Component;
use common\models\Setting;
use common\helpers\ArrayHelper;
use common\helpers\FileHelper;
use common\helpers\FormatHelper;
use common\helpers\JsonHelper;
use common\helpers\StringHelper;
use common\components\BaiduAiHandler;
use common\components\ImageHandler;

/**
 * 图像多主体检测
 */
class BaiduMultiObjectDetect extends Component
{
    public static function run($args)
    {
        $_logs['$args'] = $args;
        Yii::info(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' start ' . json_encode($_logs));

        if (empty($args['item']))
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' args $imageUrlOrPath not found ' . json_encode($_logs));
            return FormatHelper::result('', 'error', '参数错误');
        }
        $item = $args['item'];
        $_logs['$item'] = $item;

        $work = empty($args['work']) ? [] : $args['work'];
        $_logs['$work'] = $work;

        $resourceRoot = Setting::getResourceRootPath();
        $dataArr = JsonHelper::json_decode_all($item['dataResult']['data']);

        //实例化百度ai
        $client = new BaiduAiHandler();

        $resultArr = [];
        if ($dataArr)
        {
            foreach ($dataArr as $resourceKey => $resourceUrl)
            {
                $_logs['$resourceUrl'] = $resourceUrl;

                //如果是http
                if (StringHelper::is_url($resourceUrl))
                {
                    Yii::info(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' ishttp ' . json_encode($_logs));

                    if (!FileHelper::netfile_exists($resourceUrl)) {
                        Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' $dataArr image_url not exist ' . json_encode($_logs));
                        return FormatHelper::result('', 'error', '远程文件不存在');
                    }

                }
                //如果是本地硬盘相对路径
                elseif (StringHelper::is_relativepath($resourceUrl))
                {
                    if (strpos($resourceUrl, $resourceRoot) === false)
                    {
                        $resourceUrl = $resourceRoot . '/' . $resourceUrl;
                    }
                    $_logs['$resourceUrl'] = $resourceUrl;

                    if (!file_exists($resourceUrl))
                    {
                        Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' task_sourcenotfound ' . json_encode($_logs));
                        return FormatHelper::result('', 'error', '文件不存在');
                    }

                }
                else
                {
                    Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' task_sourcenotfound ' . json_encode($_logs));
                    return FormatHelper::result('', 'error', '文件路径错误');
                }

                if (FileHelper::is_image_by_ext($resourceUrl))
                {
                    //获取图片的宽高
                    $imageHandler = new ImageHandler();
                    $imageHandler->loadFile($resourceUrl);
                    if ($imageHandler->getError())
                    {
                        $_logs['getError'] = $imageHandler->getError();
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $imageHandler error '. json_encode($_logs));
                    }

                    $imageWidth = $imageHandler->getWidth();
                    $imageHeight = $imageHandler->getHeight();

                    //图片大小
                    $fileSize = FileHelper::filesize($resourceUrl);
                    if($fileSize > 1024*1024*4 )
                    {
                        //计算文件质量比
                        $fileQuality = round($fileSize/1024/1024/4);
                        $quality = 10 - $fileQuality -1;
                        $imageHandler->setImageFormat('JPEG');
                        $compression = $imageHandler->setImageCompression(ImageHandler::COMPRESSION_JPEG);
                        if (!$compression)
                        {
                            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' image setImageCompression error ' . json_encode($_logs));
                            return FormatHelper::result('', 'error', '图片压缩类型设置失败');
                        }
                        $imageHandler->setImageCompressionQuality((int)$quality * 10);
                    }

                    $filecontent = $imageHandler->getBlob();
                    //识别本地文件
                    //{"result":[{"score":0.4041937,"name":"服装饰品","location":{"top":85,"left":50,"width":576,"height":882}}],"log_id":1476077617861905200}
                    $aiResult = $client->multiObjectDetect($filecontent);
                    //接口请求频率限制每秒2-10QPS
                    sleep(1);

                    $_logs['$resourceUrl'] = $resourceUrl;
                    $_logs['$aiResult'] = ArrayHelper::var_desc($aiResult);

                    $error = ArrayHelper::array_keys_value($aiResult, 'error_code');
                    $message = ArrayHelper::array_keys_value($aiResult, 'error_msg');
                    $data = ArrayHelper::array_keys_value($aiResult, 'result');

                    if ($error || $message)
                    {
                        Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' request error ' . json_encode($_logs));
                        return FormatHelper::result('', 'error', '文件不存在');
                    }
                    elseif (empty($data) || !is_array($data))
                    {
                        Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' $data empty ' . json_encode($_logs));
                        return FormatHelper::result('', 'error', '文件不存在');
                    }
                    else
                    {
                        $resultArr = [];

                        foreach ($data as $dataKey => $dataItem)
                        {
                            //宽高
                            $dataItemWidth = $dataItem['location']['width'];
                            $dataItemHeight = $dataItem['location']['height'];

                            //位置
                            $dataItemLeft = $dataItem['location']['left'];
                            $dataItemTop = $dataItem['location']['top'];
                            
                            $dataItemRight = $dataItemLeft + $dataItemWidth;
                            $dataItemBottom = $dataItemTop + $dataItemHeight;

                            //矩形印章
                            $points = [
                                ['x' => round((float)$dataItemLeft / (float)$imageWidth, 4), 'y' => round((float)$dataItemTop / (float)$imageHeight, 4)],
                                ['x' => round((float)$dataItemRight / (float)$imageWidth, 4), 'y' => round((float)$dataItemTop / (float)$imageHeight, 4)],
                                ['x' => round((float)$dataItemRight / (float)$imageWidth, 4), 'y' => round((float)$dataItemBottom / (float)$imageHeight, 4)],
                                ['x' => round((float)$dataItemLeft / (float)$imageWidth, 4), 'y' => round((float)$dataItemBottom / (float)$imageHeight, 4)],
                            ];
                            $coordinate = [
                                ['x' => $dataItemLeft, 'y' => $dataItemTop],
                                ['x' => $dataItemRight, 'y' => $dataItemTop],
                                ['x' => $dataItemRight, 'y' => $dataItemBottom],
                                ['x' => $dataItemLeft, 'y' => $dataItemBottom],
                            ];

                            //组装返回数据
                            $resultArr[] = [
                                'id' => StringHelper::uniqueid(),
                                'type' => 'rect',
                                'points' => $points,
                                'coordinate' => $coordinate,
                                'label' => [$dataItem['name']],
                                'category' => [],
                                'code' => [],
                                'text' => $dataItem['name'],
                                'cBy' => empty($work['user_id']) ? '0' : $work['user_id'],
                                'cTime' => time(),
                                'cStep' => empty($work['step_id']) ? '0' : $work['step_id'],
                                'score' => $dataItem['score'],
                                'is_ai' => '1',
                                //'userData' => $dataItem, //客户原数据
                                'iw' => $imageWidth,
                                'ih' => $imageHeight,
                            ];
                        }
                    }
                }
                else
                {
                    $_logs['$resultArr'] = $resultArr;
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' other type '.json_encode($_logs));
                    continue;
                }
            }

        }

        $_logs['$resultArr'] = $resultArr;

        Yii::info(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' succ ' . json_encode($_logs));
        return FormatHelper::result(['data' => $resultArr]);

    }
}