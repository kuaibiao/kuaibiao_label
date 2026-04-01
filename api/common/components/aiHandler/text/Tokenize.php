<?php 
namespace common\components\aiHandler\text;

use Yii;
use yii\base\Component;
use common\models\Setting;
use common\helpers\HttpHelper;
use common\helpers\StringHelper;
use common\helpers\FormatHelper;
use common\helpers\JsonHelper;
use common\helpers\ArrayHelper;

/**
 * 对指定的文字进行相关词查询
 * 把查询的结果在文本上高亮
 * 
 *
 */

class Tokenize extends Component
{
    public static function run($args)
    {
        $_logs['$args'] = $args;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
        
        $resourceRoot = Setting::getResourceRootPath();
        
        //----------------------------------------------------------
        
        if (empty($args['aimodel']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' args $aimodel not found '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $aimodel = $args['aimodel'];
        
        if (empty($args['item']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' args $imageUrlOrPath not found '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $item = $args['item'];
        
        $work = empty($args['work']) ? [] : $args['work'];
        $_logs['$work'] = $work;
        
        $url = $aimodel['url'];
        
        $dicts = [];
        if (!empty($args['dicts']))
        {
            foreach ($args['dicts'] as $v)
            {
                if (empty($v['path']))
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no path '.json_encode($_logs));
                    continue;
                }
                
                //如果是http
                if (StringHelper::is_url($v['path']))
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' ishttp '.json_encode($_logs));
                }
                //如果是本地硬盘相对路径
                elseif (StringHelper::is_relativepath($v['path']))
                {
                    if (strpos($v['path'], $resourceRoot) === false)
                    {
                        $v['path'] = $resourceRoot . '/'.$v['path'];
                    }
                    $_logs['$path'] = $v['path'];
                }
                else
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no path '.json_encode($_logs));
                    continue;
                }
                
                
                $dicts[] = [
                    'id' => $v['id'] .'.0006.'. $v['updated_at'],
                    'url' => $v['path']
                ];
            }
        }
        $_logs['$dicts'] = $dicts;
        
        $dataArr = JsonHelper::json_decode_all($item['dataResult']['data']);
        
        $resultArr = [];
        if ($dataArr)
        {
            foreach ($dataArr as $resourceKey => $resourceUrl)
            {
                //如果是http
                if (StringHelper::is_url($resourceUrl))
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' ishttp '.json_encode($_logs));
                    
                    //获取内容
                    $resourceContent = file_get_contents($resourceUrl);
                    
                }
                //如果是本地硬盘相对路径
                elseif (StringHelper::is_relativepath($resourceUrl))
                {
                    if (strpos($resourceUrl, $resourceRoot) === false)
                    {
                        $resourceUrl = $resourceRoot . '/'.$resourceUrl;
                    }
                    $_logs['$resourceUrl'] = $resourceUrl;
                    
                    //获取内容
                    $resourceContent = file_get_contents($resourceUrl);
                }
                else
                {
                    $resourceContent = $resourceUrl;
                }
                $_logs['$resourceContent'] = ArrayHelper::desc($resourceContent);
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $resourceContent '.serialize($_logs));
                
                
                //$resourceContent = '中国的首都是北京，地铁站有望京西和立水桥等等，听说天通苑是亚洲最大的社区！';
                
                
                if (is_string($resourceContent))
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' is_text '.serialize($_logs));
                    
                    $resourceContent = StringHelper::toutf8($resourceContent);
                    //_str=_str.replace(/\n/g,""); // "\n 换行符" 保留
//                     _str=_str.replace(/\r/g,""); // "\r 回车符"
//                     _str=_str.replace(/\t/g,""); // "\t 制表符"
//                     _str=_str.replace(/\b/g,""); // "\b 退格符"
//                     _str=_str.replace(/\f/g,""); // "\f 换页符"

                    //注意此处必须是双引号
                    $resourceContent = str_replace(["\r", "\t", "\b", "\f"], '', $resourceContent);
                    $_logs['$resourceContent.new'] = ArrayHelper::desc($resourceContent);
                    
                    //$url = 'http://192.168.1.115:9991/tokenize';
                    $requestParamJson = json_encode([
                        'text' => $resourceContent,
                        'dict_list' => $dicts,
                        'use_default_dict' => !empty($dicts) ? false : true
                    ]);
                    
                    $result = HttpHelper::request($url, $requestParamJson, 'post');
                    $_logs['$result'] = ArrayHelper::desc($result);
                    if ($result['error'])
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' request error '.json_encode($_logs));
                        return FormatHelper::result('', $result['error'], $result['message']);
                    }
                    elseif (empty($result['data']))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' result empty '.json_encode($_logs));
                        continue;
                    }
                    $aiResult = $result['data'];
                    //$aiResult = '{"errormsg": "", "data": {"result": "\u641e\u9519\u73b0\u5728\u54ea\u91cc"}, "errcode": 0}';
                    if (StringHelper::is_json($aiResult))
                    {
                        $aiResult = JsonHelper::json_decode_all($aiResult);
                    }
                    $_logs['$aiResult'] = ArrayHelper::desc($aiResult);
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $aiResult '.serialize($_logs));
                    
                    //Array ( [code] => 0 [data] => 中国/的/首都/是/北京/，/地铁站/有/望京西/和/立水桥/等等/，/听说/天通苑/是/亚洲/最大/的/社区/！ [msg] => success )
                    if (!empty($aiResult['code']) && !empty($aiResult['msg']))
                    {
                        if (strpos($aiResult['msg'], 'is not available'))
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' request error '.json_encode($_logs));
                            return FormatHelper::result('', $aiResult['code'], '分词器正在初始化, 请稍后重试!');
                        }
                        
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' request error '.json_encode($_logs));
                        return FormatHelper::result('', $aiResult['code'], $aiResult['msg']);
                    }
                    
                    if (empty($aiResult['data']) || !is_array($aiResult['data']))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $data empty '.json_encode($_logs));
                        continue;
                    }
                    $data = $aiResult['data'];
                    $_logs['$data'] = ArrayHelper::desc($data);
                    
                    foreach ($data as $dataItem)
                    {
                        $_logs['$dataItem'] = ArrayHelper::desc($dataItem);
                        
                        /*
                        "id": "0409E0AC-4772-4C8E-A21D-FA8CE4BB72EA",
                        "tips": "A2.0",
                        "type": "keywords", //关键词标注结果
                        "positionstart": "0",
                        "positionend": "3",
                        "text": "雪鞋猫",
                        "tags": {
                        "color": "#722ED1",
                        "text": ["A2.0"],
                        "category": ["A2.0"],
                        "code": [""]
                        }
                        */
                        
                        if (!isset($dataItem['text']) || !isset($dataItem['start']) || !isset($dataItem['end']))
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataItem format error '.json_encode($_logs));
                            continue;
                        }
                        
                        $resultArr[] = [
                            'id' => StringHelper::uniqueid(),
                            'type' => 'text-keywords',
                            'positionstart' => $dataItem['start'],
                            'positionend' => $dataItem['end'],
                            'tips' => '',
                            'label' => [''],
                            'category' => [''],
                            'code' => [''],//元素数要和label相同
                            'text' => $dataItem['text'],
                            'tags' => [
                                "color" => '#ccc',
                                "text" => [''],
                                "category" => [''],
                                "code" => ['']
                            ],
                            'cBy' => empty($work['user_id']) ? '0' : $work['user_id'],
                            'cTime' => time(),
                            'cStep' => empty($work['step_id']) ? '0' : $work['step_id'],
                            'score' => 0,
                            'is_ai' => 1
                        ];
                        //$_logs['$resultArr'] = ArrayHelper::desc($resultArr);
                        //var_dump($_logs);
                        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test '.json_encode($_logs));
                    }
                }
                else
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' is_text other type '.json_encode($_logs));
                    continue;
                }
            }
        }
        
        return FormatHelper::result(['data' => $resultArr]);
    }
    
}