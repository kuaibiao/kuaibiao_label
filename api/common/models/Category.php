<?php

namespace common\models;

use Yii;

/**
 * category 表数据模型
 *
 */
class Category extends \yii\db\ActiveRecord
{
    const STATUS_ENABLE = 0;//正常状态
    const STATUS_DISABLE = 1;//删除状态
    
    const TYPE_LABEL = 0;//标注类
    const TYPE_COLLECTION = 1;//采集类
    const TYPE_EXTERNAL = 2;//以链接方式引入外部标注工具方式
    
    const FILE_TYPE_IMAGE = 0;//图片类
    const FILE_TYPE_AUDIO = 1;//语音类
    const FILE_TYPE_TEXT = 2;//文本类
    const FILE_TYPE_VIDEO = 3;//视频类
    const FILE_TYPE_3D = 4;//3d类
    
    const SHAPE_TYPE_POINT = 'point';
    const SHAPE_TYPE_LINE = 'line';
    const SHAPE_TYPE_RECT = 'rect';
    const SHAPE_TYPE_POLYGON = 'polygon';
    const SHAPE_TYPE_POLYLINE = 'unclosedpolygon';
    const SHAPE_TYPE_TRIANGLE = 'triangle';
    const SHAPE_TYPE_BONEPOINT = 'bonepoint';
    const SHAPE_TYPE_SPLINECURVE = 'splinecurve';
    const SHAPE_TYPE_CLOSEDCURVE = 'closedcurve';
    const SHAPE_TYPE_CUBOID = 'cuboid';//长方体
    const SHAPE_TYPE_TRAPEZOID = 'trapezoid';//梯形
    const SHAPE_TYPE_QUADRANGLE = 'quadrangle';//四边形
    const SHAPE_TYPE_PENCILLINE = 'pencilline';//线区域
    const SHAPE_TYPE_3DCUBE = 'd3d_cube';//3D立方体
    
    const FILE_EXTENSION_JPG = 'jpg';
    const FILE_EXTENSION_JPEG = 'jpeg';
    const FILE_EXTENSION_PNG = 'png';
    const FILE_EXTENSION_BMP = 'bmp';
    const FILE_EXTENSION_WAV = 'wav';
    const FILE_EXTENSION_MP3 = 'mp3';
    const FILE_EXTENSION_V3 = 'v3';
    const FILE_EXTENSION_M4A = 'm4a';
    const FILE_EXTENSION_MP4 = 'mp4';
    const FILE_EXTENSION_AVI = 'avi';
    const FILE_EXTENSION_WMA = 'wma';
    const FILE_EXTENSION_WMV = 'wmv';
    const FILE_EXTENSION_MKV = 'mkv';
    const FILE_EXTENSION_TXT = 'txt';
    const FILE_EXTENSION_PCD = 'pcd';
    
    const UPLOAD_FILE_EXTENSION_XLS = 'xls';
    const UPLOAD_FILE_EXTENSION_XLSX = 'xlsx';
    const UPLOAD_FILE_EXTENSION_CSV = 'csv';
    const UPLOAD_FILE_EXTENSION_ZIP = 'zip';
    const UPLOAD_FILE_EXTENSION_MP4 = 'mp4';
    const UPLOAD_FILE_EXTENSION_AVI = 'avi';
    const UPLOAD_FILE_EXTENSION_WMV = 'wmv';
    const UPLOAD_FILE_EXTENSION_MKV = 'mkv';
    const UPLOAD_FILE_EXTENSION_TXT = 'txt';
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'category';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type','file_type','sort','created_at', 'updated_at','status'], 'integer'],//'parent_id','template_id',
            [['type','file_type','sort', 'updated_at','status'], 'default', 'value' => '0'],
            [['icon', 'thumbnail'], 'string', 'max' => 254],
            ['draw_type', 'string', 'max' => 254],
            ['view', 'string', 'max' => 32],
            [['required_input_field', 'required_output_field'], 'string', 'max' => 254],//'instruction_url',
            [['description_input', 'description_output'], 'string', 'max' => 65535],
            ['status', 'in', 'range' => [self::STATUS_ENABLE, self::STATUS_DISABLE]],
            ['type', 'in', 'range' => array_keys(self::getTypes())],
            ['file_type', 'in', 'range' => array_keys(self::getFileTypes())],
            
            ['file_extensions', 'string', 'max' => 254],
            ['file_extensions', 'filter', 'filter' => function($value) {
                $value = trim($value, ',');
                if (strpos($value, ','))
                {
                    $values = explode(',', $value);
            
                }
                else
                {
                    $values = [$value];
                }
            
                $values = array_intersect($values, array_keys(self::getFileExtensions()));
                if ($values)
                {
                    $value = implode(',', $values);
                }
                else
                {
                    $value = '';
                }
            
                return $value;
            }],
            ['upload_file_extensions', 'string', 'max' => 254],
            ['upload_file_extensions', 'filter', 'filter' => function($value) {
                $value = trim($value, ',');
                if (strpos($value, ','))
                {
                    $values = explode(',', $value);
                    
                }
                else
                {
                    $values = [$value];
                }
                
                $values = array_intersect($values, array_keys(self::getUploadFileExtensions()));
                if ($values)
                {
                    $value = implode(',', $values);
                }
                else
                {
                    $value = '';
                }
                
                return $value;
            }],
            
            ['video_as_frame', 'integer'],
        ];
    }
    
    public static function getNameById($categoryId, $language = null)
    {
        if ($language === null)
        {
            $language = Yii::$app->language;
        }
        
        $info = Category::find()->select(['name', 'key'])->where(['id' => $categoryId])->asArray()->limit(1)->one();
    
        return yii::t('app',$info['key']);
    }

    public static function getNameByIds($categoryIds, $language = null)
    {
        if ($language === null)
        {
            if (Yii::$app->user)
            {
                $language = User::getLanguageKey(Yii::$app->user->identity->language);
            }
            else
            {
                $language = Yii::$app->language;
            }
        }
        
        $categoryList = Category::find()
            ->select(['id', 'name', 'key'])
            ->orderBy(['id' => SORT_ASC])->asArray()->all();
        
        $categoryNames = [];
        if ($categoryList)
        {
            foreach ($categoryList as $v)
            {
                $categoryNames[$v['id']] = yii::t('app',$v['key']);
            }
        }
        
        return $categoryNames;
    }
    
    public static function getStatus($status = null)
    {
        $arr = self::getStatuses();
    
        return isset($arr[$status])? $arr[$status] : '';
    }
    
    public static function getStatuses()
    {
        return [
            self::STATUS_ENABLE => Yii::t('app', 'category_status_enabled'),
            self::STATUS_DISABLE => Yii::t('app', 'category_status_disabled'),
        ];
    }

    public static function getType($type = null)
    {
    	$arr = self::getTypes();

    	return isset($arr[$type])? $arr[$type] : '';
    }

    public static function getTypes()
    {
        return [
            self::TYPE_LABEL => Yii::t('app', 'category_type_label'),
            self::TYPE_COLLECTION =>Yii::t('app', 'category_type_collection'),
            self::TYPE_EXTERNAL => Yii::t('app', 'category_type_external'),
        ];
    }
    
    public static function getFileType($type = null)
    {
        $arr = self::getFileTypes();
    
        return isset($arr[$type])? $arr[$type] : '';
    }
    
    public static function getFileTypes()
    {
        return [
            self::FILE_TYPE_IMAGE => Yii::t('app', 'category_filetype_image'),
            self::FILE_TYPE_AUDIO =>Yii::t('app', 'category_filetype_audio'),
            self::FILE_TYPE_TEXT => Yii::t('app', 'category_filetype_text'),
            self::FILE_TYPE_VIDEO => Yii::t('app', 'category_filetype_video'),
            self::FILE_TYPE_3D => Yii::t('app', 'category_filetype_3d'),
        ];
    }
    
    public static function getShapeTypes()
    {
        return [
            self::SHAPE_TYPE_POINT => Yii::t('app', 'category_shape_type_point'),
            self::SHAPE_TYPE_LINE =>  Yii::t('app', 'category_shape_type_line'),
            self::SHAPE_TYPE_RECT => Yii::t('app', 'category_shape_type_rect'),
            self::SHAPE_TYPE_POLYGON => Yii::t('app', 'category_shape_type_polygon'),
            self::SHAPE_TYPE_POLYLINE => Yii::t('app', 'category_shape_type_polyline'),
            self::SHAPE_TYPE_TRIANGLE => Yii::t('app', 'category_shape_type_triangle'),
            self::SHAPE_TYPE_BONEPOINT => Yii::t('app', 'category_shape_type_bonepoint'),
            self::SHAPE_TYPE_SPLINECURVE => Yii::t('app', 'category_shape_type_splinecurve'),
            self::SHAPE_TYPE_CLOSEDCURVE => Yii::t('app', 'category_shape_type_closedcurve'),
            self::SHAPE_TYPE_CUBOID => Yii::t('app', 'category_shape_type_cuboid'),
            self::SHAPE_TYPE_TRAPEZOID => Yii::t('app', 'category_shape_type_trapezoid'),
            self::SHAPE_TYPE_QUADRANGLE => Yii::t('app', 'category_shape_type_quadrangle'),
            self::SHAPE_TYPE_PENCILLINE => Yii::t('app', 'category_shape_type_pencilline'),
            self::SHAPE_TYPE_3DCUBE => Yii::t('app', 'category_shape_type_3dcube'),
        ];
    }
    
    public static function getFileExtension($var = null)
    {
        $vars = self::getFileExtensions();
        return isset($vars[$var])? $vars[$var] : null;
    }
    
    public static function getFileExtensions()
    {
        return [
            self::FILE_EXTENSION_JPG => Yii::t('app', 'category_file_extension_jpg'),
            self::FILE_EXTENSION_JPEG =>Yii::t('app', 'category_file_extension_jpeg'),
            self::FILE_EXTENSION_PNG => Yii::t('app', 'category_file_extension_png'),
            self::FILE_EXTENSION_BMP => Yii::t('app', 'category_file_extension_bmp'),
            self::FILE_EXTENSION_WAV => Yii::t('app', 'category_file_extension_wav'),
            self::FILE_EXTENSION_MP3 => Yii::t('app', 'category_file_extension_mp3'),
            self::FILE_EXTENSION_V3 => Yii::t('app', 'category_file_extension_v3'),
            //self::FILE_EXTENSION_WMA => Yii::t('app', 'category_file_extension_wma'),
            self::FILE_EXTENSION_M4A => Yii::t('app', 'category_file_extension_m4a'),
            self::FILE_EXTENSION_MP4 => Yii::t('app', 'category_file_extension_mp4'),
            self::FILE_EXTENSION_AVI => Yii::t('app', 'category_file_extension_avi'),
            self::FILE_EXTENSION_WMV => Yii::t('app', 'category_file_extension_wmv'),
            self::FILE_EXTENSION_MKV => Yii::t('app', 'category_file_extension_mkv'),
            self::FILE_EXTENSION_TXT => Yii::t('app', 'category_file_extension_txt'),
            self::FILE_EXTENSION_PCD => Yii::t('app', 'category_file_extension_pcd'),
        ];
    }
    
    public static function getUploadFIleExtension($var = null)
    {
        $vars = self::getUploadFIleExtensions();
        return isset($vars[$var])? $vars[$var] : null;
    }
    
    public static function getUploadFIleExtensions()
    {
        return [
            self::UPLOAD_FILE_EXTENSION_XLS => Yii::t('app', 'category_upload_file_extension_xls'),
            self::UPLOAD_FILE_EXTENSION_XLSX =>Yii::t('app', 'category_upload_file_extension_xlsx'),
            self::UPLOAD_FILE_EXTENSION_CSV => Yii::t('app', 'category_upload_file_extension_csv'),
            self::UPLOAD_FILE_EXTENSION_ZIP => Yii::t('app', 'category_upload_file_extension_zip'),
            self::UPLOAD_FILE_EXTENSION_MP4 => Yii::t('app', 'category_upload_file_extension_mp4'),
            self::UPLOAD_FILE_EXTENSION_AVI => Yii::t('app', 'category_upload_file_extension_avi'),
            self::UPLOAD_FILE_EXTENSION_WMV => Yii::t('app', 'category_upload_file_extension_wmv'),
            self::UPLOAD_FILE_EXTENSION_MKV => Yii::t('app', 'category_upload_file_extension_mkv'),
            self::UPLOAD_FILE_EXTENSION_TXT => Yii::t('app', 'category_upload_file_extension_txt'),
        ];
    }

    /*
     * @params $key 分类key,根据key获取类型描述、keywords;规则：$key.'_keywords'、$key.'_description'；
     * return array
     */
    public static function getCategoryDesc($key)
    {
        if(empty($key))
        {
            return [];
        }
        else
        {
            return [

                    'name' => yii::t('app',$key),
                    'keywords' => yii::t('app',$key.'_keywords'),
                    'description' => yii::t('app',$key.'_description'),
            ];
        }

    }
    
}
