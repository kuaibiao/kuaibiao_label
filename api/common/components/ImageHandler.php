<?php

namespace common\components;

use Yii;
use yii\base\Component;
use common\helpers\LabelHelper;
use common\helpers\JsonHelper;

/**
 * 图片处理类
 * 基于imagick
 * 支持生成mark图,mask图,mask并填充等方式
 *
 */
class ImageHandler extends Component
{
    //图片名称
    var $image = null;//image实例对象
    var $borderWidth = 6;//像素值ps//['<100' => '0.05', '<200' => '0.03','<300' => '0.02','<400' => '0.015','<500' => '0.01','<600' => '0.01','<700' => '0.01','<800' => '0.008','<900' => '0.007','<1000' => '0.005'];
    var $isShowLabel = true;//mark图是否显示标签值
    var $isFilled = false;//mark图是否填充
    var $memoryMaxSize = 1000000000;//1G
    var $outputPath = '';
    var $error = '';

    public function __construct()
    {
        $this->image = new \Imagick();
    }

    public function setImagePath($var)
    {
        $this->imagePath = $var;
    }

    public function getImagePath($var)
    {
        return $this->imagePath;
    }

    public function setOutputPath($var)
    {
        $this->outputPath = $var;

        if (!empty($this->outputPath) && !file_exists($this->outputPath))
        {
            @mkdir($this->outputPath, 0777, true);
        }
    }


    public function setBorderWidth($var)
    {
        $this->borderWidth = $var;
    }

    public function getBorderWidth($var)
    {
        return $this->borderWidth;
    }

    public function setIsShowLabel($var)
    {
        $this->isShowLabel = $var;
    }

    public function getIsShowLabel($var)
    {
        return $this->isShowLabel;
    }

    public function getSize()
    {

        return true;
    }

    //获取图片二进制流
    public function getBlob()
    {
        return $this->image->getImageBlob();
    }

    //获取图片扩展
    public function getExtention()
    {
        return $this->image->getImageFormat();
    }

    //获取图片宽度
    public function getWidth()
    {
        return $this->image->getImageWidth();
    }

    //获取图片高度
    public function getHeight()
    {
        return $this->image->getImageHeight();
    }

    public function setFormat($type = 'jpg')
    {
        return $this->image->setimageformat($type);
    }

    public function setType($type = \Imagick::IMGTYPE_GRAYSCALE)
    {
        return $this->image->setimagetype($type);
    }

    public function setCompressionQuality($var = 100)
    {
        return $this->image->setimagecompressionquality($var);
    }

    //返回图像分辨率,X和Y轴信息
    public function getResolution()
    {
        return $this->image->getImageResolution();
    }

    //设置分辨率
    public function setResolution($x, $y)
    {
        $this->image->setResolution($x, $y);
    }

    //返回图像分辨率单位
    public function getUnits()
    {
        return $this->image->getImageUnits();
    }

    //设置分辨率单位
    public function setUnits()
    {
        return $this->image->setImageUnits();
    }

    //获取mime
    public function getMimeType()
    {
        return $this->image->getImageMimeType();
    }

    public function getUsedMemory()
    {
        return $this->getWidth() * $this->getHeight() * 5;//内存占用公式:宽*高*5, 单位byte
    }

    // 以期望的分辨率重新采样
    public function resampleImage()
    {
        return $this->image->resampleImage();
    }

    //获取宽高的比率
    public function getRatio($height, $width, $maxwidth = 400, $maxheight = 400)
    {
        $resizeWidth = $resizeHeight = false;
        if (($maxwidth && $width > $maxwidth) || ($maxheight && $height > $maxheight)) {
            if ($maxwidth && $width > $maxwidth) {
                $widthratio = $maxwidth / $width;
                $resizeWidth = true;
            }
            if ($maxheight && $height > $maxheight) {
                $heightratio = $maxheight / $height;
                $resizeHeight = true;
            }
            if ($resizeWidth && $resizeHeight) {
                if ($widthratio < $heightratio) {
                    $ratio = $widthratio;
                }
                else {
                    $ratio = $heightratio;
                }
            }
            elseif ($resizeWidth) {
                $ratio = $widthratio;
            }
            elseif ($resizeHeight) {
                $ratio = $heightratio;
            }
        }
        else {
            $ratio = 1;
        }
        return $ratio;
    }

    //加载一张图片地址
    public function loadFile($imagePath)
    {
        $this->error = null;
        
        try
        {
            $this->image->readImage($imagePath);
        }
        catch (\Exception $e)
        {
            $this->error = $e->getMessage();
            
            $_logs['error'] = $this->error;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' error '. json_encode($_logs));
            return false;
        }
    }

    //加载一张图片二进制流
    public function loadBlob($imageBlob)
    {
        $this->error = null;
        
        try
        {
            $this->image->readImageBlob($imageBlob);
        }
        catch (\Exception $e)
        {
            $this->error = $e->getMessage();
            
            $_logs['error'] = $this->error;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' error '. json_encode($_logs));
            return false;
        }
    }

    /**
     *
     * 创建一张纯色图片画布
     *
     * $backgroundColor e.g.
     * $colorNames = ['white', 'black', 'yellow', 'red', 'blue', 'green', 'gray', 'transparent'];
     * #0000ff
     * rgb(0,0,255)
     * rgba(0,0,255,75)
     * cmyk(100,100,100,10)
     *
     * @param int $width
     * @param int $height
     * @param string $backgroundColor
     */
    public function create($width, $height, $backgroundColor)
    {
        try
        {
            $this->image = new \Imagick();
            $this->image->newImage($width, $height, new \ImagickPixel($backgroundColor));
        }
        catch (\Exception $e)
        {

        }
    }

    //改变图片的大小
    public function resize($new_width, $new_height)
    {
        $this->image->thumbnailImage($new_width, $new_height);
    }

    /**
     * 裁剪
     * $imageHandler->crop($startX, $startY, $width, $height);
     * $imageHandler->getBlob();
     *
     * @param int $startX
     * @param int $startY
     * @param int $width
     * @param int $height
     */
    public function crop($startX, $startY, $width, $height)
    {
        $this->image->cropImage($width, $height, $startX, $startY);
    }

    //写一个图像或图像序列
    public function save($outputFile)
    {
        //处理目录
        $this->setOutputPath(pathinfo($outputFile, PATHINFO_DIRNAME));
        $this->setFormat(pathinfo($outputFile, PATHINFO_EXTENSION));

        $this->image->writeImage($outputFile);
    }

    public function getBase64Data()
    {
        return 'data:' . $this->getMimeType() . ';base64,' . base64_encode($this->getBlob());
    }

    public function destroy()
    {
        $this->image->destroy();//销毁图片
    }


    /**
     * 生成mask图
     *
     * $values = [
     *  ["type"=>"polygon","id"=>"3a4562b3-2868-4601-92e3-4d638adbf17a","points"=>[
     *  ["x"=>0.057548,"y"=>0.60719],["x"=>0.06058,"y"=>0.60719],["x"=>0.060626,"y"=>0.608623]
     *  ],"strokeWidth"=>1.5,"label"=>["中"],"category"=>["中"],"code"=>[""],"color"=>"#0000FF","cBy"=>"110111","cTime"=>1571041045,"mBy"=>"110111","mTime"=>1571041047,"step"=>"9052","editable"=>true,"userData"=>null,"iw"=>2048,"ih"=>600]
     * ];
     *  $image = new ImageHandler();
     *  $image->create(2000, 600, 'black');
     *  $image->mask($values);
     *  $image->save('/tmp/aaa.jpg'); 或 $image->getBase64Data();
     *
     * @param array $graphData
     * @param array $options
     *
     * 消除锯齿功能: 0不消除,1消除; 默认消除锯齿; 要平滑的传1; 要在模型训练使用的话, 需要有锯齿, 传0
     * $options = ['antialias' => 1, 'strokeWidth' => 2];
     *
     * @return boolean
     */
    public function mask($graphData, $options = [])
    {
        $_logs = [];

        //默认选项
        $optionsDefault = [
            'antialias' => 1, //0不消除,1消除;默认消除锯齿; 即视觉平滑的; 要在模型训练使用的话, 需要有锯齿, 传0
            'strokeWidth' => 2 //线宽
        ];

        //合并默认选项
        $options = array_merge($optionsDefault, $options);

        //判断使用内存
        if ($this->memoryMaxSize && $this->getUsedMemory() > $this->memoryMaxSize)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' usedmemory >1G '.json_encode($_logs));
            return false;
        }

        //$imageBlob = imagecreatetruecolor($this->imageWidth, $this->imageHeight);

        //---------------------------------------

        if (JsonHelper::is_json($graphData))
        {
            $graphData = json_decode($graphData, true);
        }

        if (!empty($graphData['data']))
        {
            $graphData = $graphData['data'];
        }

        if (!empty($graphData) && is_array($graphData))
        {
            //对图形按照面积大小排序
            $graphDataSort = [];
            foreach ($graphData as $sk => $sv)
            {
                if (isset($sv['type']) && in_array($sv['type'], ['polygon', 'rect', 'triangle']))
                {
                    $sort = LabelHelper::polygon_area($sv['points']);
                }
                else
                {
                    $sort = - count($graphDataSort);
                }

                $graphDataSort[] = $sort;
            }
            //$_logs['$graphDataSort'] = $graphDataSort;
            $graphData = array_combine($graphDataSort, $graphData);
            krsort($graphData);
            //$_logs['$graphData'] = $graphData;
            foreach ($graphData as $k => $v)
            {
                $_logs['$v'] = $v;
                //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' label type '.json_encode($_logs));
                if (empty($v['type']))
                {
                    $_logs['$v'] = $v;
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' label no type '.json_encode($_logs));
                    continue;
                }

                if (!isset($v['label']))
                {
                    $v['label'] = '';
                }
                if (!isset($v['code']))
                {
                    $v['code'] = '';
                }
                if (!isset($v['category']))
                {
                    $v['category'] = '';
                }

                //处理颜色
                if (isset($v['color']))
                {
                    $color = new \ImagickPixel($v['color']);
                }
                else
                {
                    $color = new \ImagickPixel('red');
                }
                $_logs['$color'] = $color;
                //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $color '.json_encode($_logs));


                //type\":\"point\",\"points\":[0.7624671916010499,0.0752405949256343],\"radius\":3,\"fill\":\"green\",\"top\":40,\"left\":578,\"label\":\"\"
                if ($v['type'] == 'point')
                {
                    if(isset($v['points']['x'])){
                        $point_x = $v['points']['x'];
                        $point_y = $v['points']['y'];
                    }elseif(isset($v['points'][0]) && isset($v['points'][1])){
                        $point_x = $v['points'][0];
                        $point_y = $v['points'][1];
                    }else{
                        $point = $v['points'][0];
                        $point_x = $point['x'];
                        $point_y = $point['y'];
                    }
                    $point_x = $point_x * $this->getWidth();
                    $point_y =  $point_y * $this->getHeight();

                    $draw = new \ImagickDraw();
                    $draw->setFillColor($color);
                    $draw->setStrokeWidth($options['strokeWidth']);
                    $draw->setStrokeAntialias(empty($options['antialias']) ? false : true);
                    $draw->point($point_x, $point_y);

                    $this->image->drawImage($draw);

                }
                //{\"type\":\"line\",\"points\":[{\"x\":0.2664041994750656,\"y\":0.0804899387576553},{\"x\":0.4120734908136483,\"y\":0.29571303587051617}],\"label\":\"\",\"stroke\":\"green\",\"strokeWidth\":4}
                elseif ($v['type'] == 'line')
                {
                    $start_point = $v['points'][0];
                    $end_point = $v['points'][1];


                    $draw = new \ImagickDraw();
                    $draw->setStrokeColor($color);
                    $draw->setFillColor($color);

                    $draw->setStrokeWidth($options['strokeWidth']);
                    $draw->setStrokeAntialias(empty($options['antialias']) ? false : true);
                    //$draw->setFontSize(72);
                    $start_point['x'] = $start_point['x'] * $this->getWidth();
                    $start_point['y'] =  $start_point['y'] * $this->getHeight();
                    $end_point['x'] = $end_point['x'] * $this->getWidth();
                    $end_point['y'] =  $end_point['y'] * $this->getHeight();

                    $draw->line($start_point['x'], $start_point['y'],$end_point['x'],$end_point['y']);

                    $this->image->drawImage($draw);
                }
                elseif($v['type'] == 'splinecurve' )
                {
                    $real_points = array();
                    foreach ($v['points'] as $k_ => $point_pair)
                    {
                        $real_points[] = [
                            'x' => $this->getWidth() * $point_pair['x'],
                            'y' => $this->getHeight() * $point_pair['y']
                        ];
                    }
                    $all_points = LabelHelper::spline($real_points);

                    //处理线宽
                    if($this->getWidth() > $this->getHeight()){
                        $proportion = $this->getWidth() / $this->getHeight();
                        $widthbase = $this->getWidth();
                    }else{
                        $proportion = $this->getHeight() / $this->getWidth();
                        $widthbase = $this->getHeight();
                    }
                    if($proportion > 4){
                        $lineproportion = $proportion / 2;
                    }elseif($proportion > 2  &&  $proportion < 4){
                        $lineproportion = $proportion / 2.2;
                    }else{
                        $lineproportion = $proportion;
                    }
                    $lineWidthPoly = floor($widthbase * $lineproportion / 1500);
                    if($lineWidthPoly < 1){
                        $lineWidthPoly = 1;
                    }
                    $lineWidthPoly *= 2;
                    // Set the line thickness
                    //imagesetthickness ( $this->imageBlob , $lineWidthPoly );
                    foreach ($all_points as $k_ => $point_pair)
                    {
                        $x = $point_pair['x'];
                        $y = $point_pair['y'];

                        if (!isset($all_points[$k_+1]))
                        {
                            continue;
                        }
                        $next_x = $all_points[$k_+1]['x'];
                        $next_y = $all_points[$k_+1]['y'];

                        $draw = new \ImagickDraw();
                        $draw->setStrokeColor($color);
                        $draw->setFillColor($color);
                        $draw->setStrokeWidth($options['strokeWidth']);
                        $draw->setStrokeAntialias(empty($options['antialias']) ? false : true);
                        //$draw->setFontSize(72);
                        $draw->line($x, $y, $next_x, $next_y);

                        $this->image->drawImage($draw);

                    }
                }
                elseif ($v['type'] == 'closedcurve')
                {
                    $real_points = array();
                    foreach ($v['points'] as $k_ => $point_pair)
                    {
                        $real_points[] = [
                            'x' => $this->getWidth() * $point_pair['x'],
                            'y' => $this->getHeight() * $point_pair['y']
                        ];
                    }

                    $all_points = LabelHelper::spline($real_points);
                    //$_logs['$all_points'] = $all_points;
                    //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' spline '.json_encode($_logs));
                    if (!$all_points)
                    {
                        continue;
                    }

                    //处理点
                    $real_points = array();
                    foreach ($all_points as $k_ => $point_pair)
                    {
                        $real_points[] = ['x' => $point_pair['x'], 'y' => $point_pair['y']];
                    }

                    if (count($real_points) < 3)
                    {
                        $_logs['$real_points'] = $real_points;
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $real_points < 3 '.json_encode($_logs));
                        continue;
                    }

                    $draw = new \ImagickDraw();
                    $draw->setStrokeOpacity(1);
                    $draw->setStrokeColor($color);
                    $draw->setStrokeWidth($options['strokeWidth']);
                    $draw->setStrokeAntialias(empty($options['antialias']) ? false : true);
                    $draw->setFillColor($color);
                    $draw->polygon($real_points);

                    $this->image->drawImage($draw);
                }
                //{\"type\":\"polygon\",\"points\":[{\"x\":0.14041994750656167,\"y\":0.10148731408573929},{\"x\":0.07480314960629922,\"y\":0.35345581802274717}
                elseif (in_array($v['type'], ['polygon', 'rect', 'triangle', 'quadrangle', 'trapezoid']))
                {
                    //处理点
                    $real_points = array();
                    foreach ($v['points'] as $k_ => $point_pair)
                    {
                        if (!isset($point_pair['x']) || !isset($point_pair['y']))
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' point error '.json_encode($_logs));
                            continue;
                        }

                        $real_points[] = [
                            'x' => $this->getWidth() * $point_pair['x'],
                            'y' => $this->getHeight() * $point_pair['y'],
                        ];
                    }

                    if (count($real_points) < 3)
                    {
                        $_logs['$real_points'] = $real_points;
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $real_points < 3 '.json_encode($_logs));
                        continue;
                    }

                    $draw = new \ImagickDraw();
                    $draw->setStrokeOpacity(1);
                    $draw->setStrokeColor($color);
                    $draw->setStrokeWidth($options['strokeWidth']);
                    $draw->setStrokeAntialias(empty($options['antialias']) ? false : true);
                    $draw->setFillColor($color);
                    $draw->polygon($real_points);

                    $this->image->drawImage($draw);
                }
                //长方体
                elseif (in_array($v['type'], ['cuboid']))
                {

                }
                elseif ($v['type'] == 'pencilline')
                {
                    $lineWidth = $v['strokeWidth'];
                    //imagesetthickness($this->imageBlob, $lineWidth);

                    $real_points = array();
                    foreach ($v['points'] as $k_ => $point_pair)
                    {
                        $real_points[] = [
                            'x' => $this->getWidth() * $point_pair['x'],
                            'y' => $this->getHeight() * $point_pair['y']
                        ];
                    }

                    $all_points = $real_points;
                    //$_logs['$all_points'] = $all_points;
                    //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' spline '.json_encode($_logs));

                    foreach ($all_points as $k_ => $point_pair)
                    {
                        $x = $point_pair['x'];
                        $y = $point_pair['y'];

                        if (isset($all_points[$k_+1]))
                        {
                            $next_x = $all_points[$k_+1]['x'];
                            $next_y = $all_points[$k_+1]['y'];

                            //imageline($this->imageBlob, $x, $y, $next_x, $next_y, $color);
                            $draw = new \ImagickDraw();
                            $draw->setStrokeColor($color);
                            $draw->setFillColor($color);
                            $draw->setStrokeWidth($options['strokeWidth']);
                            $draw->setFontSize(72);
                            $draw->line($x, $y, $next_x, $next_y);

                            $this->image->drawImage($draw);
                            $this->image->drawImage($draw);
                        }
                        //imagefilledellipse($this->imageBlob,$x,$y,$lineWidth,$lineWidth, $color);

                        $draw = new \ImagickDraw();
                        $draw->setStrokeColor($color);
                        $draw->setFillColor($color);
                        $draw->setStrokeWidth($options['strokeWidth']);
                        $draw->setStrokeAntialias(empty($options['clean_sawtooth']) ? true : false);
                        $draw->setFontSize(72);

                        $draw->ellipse($x,$y,$lineWidth,$lineWidth, 0, 360);
                        $this->image->drawImage($draw);

                    }
                }
                else
                {

                }
            }
        }

        //----------------------------------------

        return true;
    }

    /**
     * 生成mark图
     *
     *
     *
     * @param unknown $graphData
     * @param unknown $options $options = ['strokeWidth' => 2];
     * @return boolean
     */
    public function mark($graphData, $options = [])
    {
        $_logs = [];

        //判断使用内存
        if ($this->memoryMaxSize && $this->getUsedMemory() > $this->memoryMaxSize)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' usedmemory >1G '.json_encode($_logs));
            return false;
        }
        
        //默认选项
        $optionsDefault = [
            'strokeWidth' => 2 //线宽
        ];
        
        //合并默认选项
        $options = array_merge($optionsDefault, $options);

        //---------------------------------------

        if (JsonHelper::is_json($graphData))
        {
            $graphData = json_decode($graphData, true);
        }

        if (!empty($graphData['data']))
        {
            $graphData = $graphData['data'];
        }

        if (!empty($graphData) && is_array($graphData))
        {
            //$_logs['$graphData'] = $graphData;
            foreach ($graphData as $k => $v)
            {
                $_logs['$v'] = $v;
                //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' label type '.json_encode($_logs));
                if (empty($v['type']))
                {
                    $_logs['$v'] = $v;
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' label no type '.json_encode($_logs));
                    continue;
                }

                if (!isset($v['label']))
                {
                    $v['label'] = '';
                }
                if (!isset($v['code']))
                {
                    $v['code'] = '';
                }
                if (!isset($v['category']))
                {
                    $v['category'] = '';
                }

                //处理颜色
                if (isset($v['color']))
                {
                    $color = new \ImagickPixel($v['color']);
                }
                else
                {
                    $color = new \ImagickPixel('red');
                }
                $_logs['$color'] = $color;
                //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $color '.json_encode($_logs));


                //type\":\"point\",\"points\":[0.7624671916010499,0.0752405949256343],\"radius\":3,\"fill\":\"green\",\"top\":40,\"left\":578,\"label\":\"\"
                if ($v['type'] == 'point')
                {
                    if(isset($v['points']['x'])){
                        $point_x = $v['points']['x'];
                        $point_y = $v['points']['y'];
                    }elseif(isset($v['points'][0]) && isset($v['points'][1])){
                        $point_x = $v['points'][0];
                        $point_y = $v['points'][1];
                    }else{
                        $point = $v['points'][0];
                        $point_x = $point['x'];
                        $point_y = $point['y'];
                    }
                    $point_x = $point_x * $this->getWidth();
                    $point_y =  $point_y * $this->getHeight();

                    $draw = new \ImagickDraw();
                    $draw->setFillColor($color);
                    $draw->point($point_x, $point_y);

                    $this->image->drawImage($draw);

                }
                //{\"type\":\"line\",\"points\":[{\"x\":0.2664041994750656,\"y\":0.0804899387576553},{\"x\":0.4120734908136483,\"y\":0.29571303587051617}],\"label\":\"\",\"stroke\":\"green\",\"strokeWidth\":4}
                elseif ($v['type'] == 'line')
                {
                    $start_point = $v['points'][0];
                    $end_point = $v['points'][1];


                    $draw = new \ImagickDraw();
                    $draw->setStrokeColor($color);
                    $draw->setFillColor($color);

                    $draw->setStrokeWidth($options['strokeWidth']);
                    $draw->setFontSize(72);

                    $draw->line($start_point['x'], $start_point['y'],$end_point['x'],$end_point['y']);

                    $this->image->drawImage($draw);
                }
                elseif($v['type'] == 'splinecurve' )
                {
                    $real_points = array();
                    foreach ($v['points'] as $k_ => $point_pair)
                    {
                        $real_points[] = [
                            'x' => $this->getWidth() * $point_pair['x'],
                            'y' => $this->getHeight() * $point_pair['y']
                        ];
                    }
                    $all_points = LabelHelper::spline($real_points);

                    //处理线宽
                    if($this->getWidth() > $this->getHeight()){
                        $proportion = $this->getWidth() / $this->getHeight();
                        $widthbase = $this->getWidth();
                    }else{
                        $proportion = $this->getHeight() / $this->getWidth();
                        $widthbase = $this->getHeight();
                    }
                    if($proportion > 4){
                        $lineproportion = $proportion / 2;
                    }elseif($proportion > 2  &&  $proportion < 4){
                        $lineproportion = $proportion / 2.2;
                    }else{
                        $lineproportion = $proportion;
                    }
                    $lineWidthPoly = floor($widthbase * $lineproportion / 1500);
                    if($lineWidthPoly < 1){
                        $lineWidthPoly = 1;
                    }
                    $lineWidthPoly *= 2;
                    // Set the line thickness
                    //imagesetthickness ( $this->imageBlob , $lineWidthPoly );
                    foreach ($all_points as $k_ => $point_pair)
                    {
                        $x = $point_pair['x'];
                        $y = $point_pair['y'];

                        if (!isset($all_points[$k_+1]))
                        {
                            continue;
                        }
                        $next_x = $all_points[$k_+1]['x'];
                        $next_y = $all_points[$k_+1]['y'];

                        $draw = new \ImagickDraw();
                        $draw->setStrokeColor($color);
                        $draw->setFillColor($color);
                        $draw->setStrokeWidth($options['strokeWidth']);
                        $draw->setFontSize(72);
                        $draw->line($x, $y, $next_x, $next_y);

                        $this->image->drawImage($draw);

                    }
                }
                elseif ($v['type'] == 'closedcurve')
                {
                    $real_points = array();
                    foreach ($v['points'] as $k_ => $point_pair)
                    {
                        $real_points[] = [
                            'x' => $this->getWidth() * $point_pair['x'],
                            'y' => $this->getHeight() * $point_pair['y']
                        ];
                    }

                    $all_points = LabelHelper::spline($real_points);
                    //$_logs['$all_points'] = $all_points;
                    //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' spline '.json_encode($_logs));
                    if (!$all_points)
                    {
                        continue;
                    }

                    //处理点
                    $real_points = array();
                    foreach ($all_points as $k_ => $point_pair)
                    {
                        $real_points[] = ['x' => $point_pair['x'], 'y' => $point_pair['y']];
                    }

                    if (count($real_points) < 3)
                    {
                        $_logs['$real_points'] = $real_points;
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $real_points < 3 '.json_encode($_logs));
                        continue;
                    }

                    $draw = new \ImagickDraw();
                    $draw->setStrokeOpacity(1);
                    $draw->setStrokeColor($color);
                    $draw->setStrokeWidth($options['strokeWidth']);
                    $draw->setFillColor($color);
                    $draw->polygon($real_points);

                    $this->image->drawImage($draw);
                }
                elseif (in_array($v['type'], ['rect_']))
                {
                    //处理点
                    $real_points = array();
                    foreach ($v['points'] as $k_ => $point_pair)
                    {
                        if (!isset($point_pair['x']) || !isset($point_pair['y']))
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' point error '.json_encode($_logs));
                            continue;
                        }

                        $real_points[] = [
                            'x' => $this->getWidth() * $point_pair['x'],
                            'y' => $this->getHeight() * $point_pair['y']
                        ];
                    }

                    $draw = new \ImagickDraw();
                    $draw->setStrokeOpacity(1);
                    $draw->setStrokeColor($color);
                    $draw->setStrokeWidth($options['strokeWidth']);

                    $draw->setfillopacity(0);
                    //$draw->setFillColor(null);
                    $draw->rectangle($real_points['0']['x'], $real_points['0']['y'], $real_points['1']['x'], $real_points['1']['y']);

                    $this->image->drawImage($draw);
                }
                //{\"type\":\"polygon\",\"points\":[{\"x\":0.14041994750656167,\"y\":0.10148731408573929},{\"x\":0.07480314960629922,\"y\":0.35345581802274717}
                elseif (in_array($v['type'], ['rect', 'polygon', 'triangle', 'quadrangle']))
                {
                    //处理点
                    $real_points = array();
                    foreach ($v['points'] as $k_ => $point_pair)
                    {
                        if (!isset($point_pair['x']) || !isset($point_pair['y']))
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' point error '.json_encode($_logs));
                            continue;
                        }

                        $real_points[] = [
                            'x' => $this->getWidth() * $point_pair['x'],
                            'y' => $this->getHeight() * $point_pair['y']
                        ];
                    }

                    if (count($real_points) < 3)
                    {
                        $_logs['$real_points'] = $real_points;
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $real_points < 3 '.json_encode($_logs));
                        continue;
                    }

                    $draw = new \ImagickDraw();
                    $draw->setStrokeOpacity(0);
                    $draw->setStrokeColor($color);
                    $draw->setStrokeWidth($options['strokeWidth']);
                    $draw->setfillopacity(0);
                    #$draw->setFillColor($color);
                    $draw->polygon($real_points);

                    $this->image->drawImage($draw);
                }
                //长方体,梯形
                elseif (in_array($v['type'], ['cuboid', 'trapezoid']))
                {

                }
                elseif ($v['type'] == 'pencilline')
                {
                    $lineWidth = $v['strokeWidth'];
                    //imagesetthickness($this->imageBlob, $lineWidth);

                    $real_points = array();
                    foreach ($v['points'] as $k_ => $point_pair)
                    {
                        $real_points[] = [
                            'x' => $this->getWidth() * $point_pair['x'],
                            'y' => $this->getHeight() * $point_pair['y']
                        ];
                    }

                    $all_points = $real_points;
                    //$_logs['$all_points'] = $all_points;
                    //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' spline '.json_encode($_logs));

                    foreach ($all_points as $k_ => $point_pair)
                    {
                        $x = $point_pair['x'];
                        $y = $point_pair['y'];

                        if (isset($all_points[$k_+1]))
                        {
                            $next_x = $all_points[$k_+1]['x'];
                            $next_y = $all_points[$k_+1]['y'];

                            //imageline($this->imageBlob, $x, $y, $next_x, $next_y, $color);
                            $draw = new \ImagickDraw();
                            $draw->setStrokeColor($color);
                            $draw->setFillColor($color);
                            $draw->setStrokeWidth($options['strokeWidth']);
                            $draw->setFontSize(72);
                            $draw->line($x, $y, $next_x, $next_y);

                            $this->image->drawImage($draw);
                            $this->image->drawImage($draw);
                        }
                        //imagefilledellipse($this->imageBlob,$x,$y,$lineWidth,$lineWidth, $color);

                        $draw = new \ImagickDraw();
                        $draw->setStrokeColor($color);
                        $draw->setFillColor($color);
                        $draw->setStrokeWidth($options['strokeWidth']);
                        $draw->setFontSize(72);

                        $draw->ellipse($x,$y,$lineWidth,$lineWidth, 0, 360);
                        $this->image->drawImage($draw);

                    }
                }
                else
                {

                }
            }
        }

        //----------------------------------------

        return true;
    }


    public function drawPoint()
    {

    }


    public function getError()
    {
        return $this->error;
    }

    /**
     * @name 获取旋转角度
     * @return int
     */
    public function getOrientation()
    {
        return $this->image->getImageOrientation();
    }

    /**
     * @name 设置旋转角度
     */
    public function setOrientation($degree)
    {
        return $this->image->setImageOrientation($degree);
    }
    
    /**
     * @name 旋转
     */
    public function rotate($degree)
    {
        $this->image->rotateimage(new \ImagickPixel('none'), $degree);//旋转指定角度
    }

    /**
     * @name 自动旋转(复原)
     */
    public function rotateAuto()
    {
        $orientation = $this->getOrientation();
        switch ($orientation) {
            /*
            case '0':
                $imagick_orientation_evaluated = "Undefined";
                break;
                
            case '1':
                $imagick_orientation_evaluated = "Top-Left";
                break;
                
            case '2':
                $imagick_orientation_evaluated = "Top-Right";
                break;
                
            case '3':
                $imagick_orientation_evaluated = "Bottom-Right";
                break;
                
            case '4':
                $imagick_orientation_evaluated = "Bottom-Left";
                break;
                
            case '5':
                $imagick_orientation_evaluated = "Left-Top";
                break;
                
            case '6':
                $imagick_orientation_evaluated = "Right-Top";
                break;
                
            case '7':
                $imagick_orientation_evaluated = "Right-Bottom";
                break;
                
            case '8':
                $imagick_orientation_evaluated = "Left-Bottom";
                break;
                */
            case \Imagick::ORIENTATION_BOTTOMRIGHT: //3
                $this->rotate(180);
                break;
            case \Imagick::ORIENTATION_RIGHTTOP: //6
                $this->rotate(90);
                break;
            case \Imagick::ORIENTATION_LEFTBOTTOM: //8
                $this->rotate(-90);
                break;
        }
        $this->setOrientation(0);
    }
}