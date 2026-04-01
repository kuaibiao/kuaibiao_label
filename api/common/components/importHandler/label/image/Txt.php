<?php 
namespace common\components\importHandler\label\image;

use Yii;
use yii\base\Component;
use common\models\Setting;
use common\helpers\FormatHelper;
use common\helpers\FileHelper;
use common\helpers\ArrayHelper;
/**
 * 
 * 文本文件处理
 * 
 *
 */

class Txt extends Component
{
    public static function run($args)
    {
        $_logs['$args'] = ArrayHelper::desc($args);
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
        
        if (empty($args['filePath']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' filePath empty '.json_encode($_logs));
            // return FormatHelper::result('', 'error', 'error');
            $checkMessages = ['label_unpack_error_filePath_empty' => []];
            return FormatHelper::result('', 'error', json_encode($checkMessages));
        }
        $filePath = $args['filePath'];
        $defaultField = isset($args['defaultField']) ? $args['defaultField'] : null;
        $extensions = isset($args['extensions']) ? $args['extensions'] : null;
        $batchConfigs = isset($args['batchConfigs']) ? $args['batchConfigs'] : null;
        
        //------------------------------------------
        
        //文件的相对路径
        $filePathRelative = ltrim(str_replace(Setting::getUploadRootPath(), '', $filePath), '/');
        $_logs['$filePathRelative'] = $filePathRelative;
        
        //------------------------------------------
        
        //读取文件夹的文件列表
        $fileList = FileHelper::file_readcontent($filePath);
        if (!$fileList)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file list empty '.json_encode($_logs));
            // return FormatHelper::result('', 'error', 'error');
            $checkMessages = ['label_unpack_error_extractpath_exist' => []];
            return FormatHelper::result('', 'error', json_encode($checkMessages));
        }
        
        $fileCount = count($fileList);
        $fileData = [];
        foreach ($fileList as $source_val)
        {
            $fileData[] = [$defaultField => $source_val];
        }
        $_logs['$fileData.count'] = count($fileData);
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return FormatHelper::result($fileData);
    }
}