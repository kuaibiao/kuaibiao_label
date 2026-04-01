<?php
namespace common\components;

use Yii;
use yii\base\Component;
use common\helpers\FileHelper;
use common\helpers\LabelHelper;
use common\helpers\JsonHelper;
use common\helpers\StringHelper;
use common\helpers\ImageHelper;
class ImageProcessor extends Component
{

    public static function image_mask_from_string($imageName, $imageString, $graphData, $storePath = null, $outputType = 'png', $config = array())
    {
        $_logs = [];

        $image_info = @getimagesizefromstring($imageString);
        $_logs['$image_info'] = $image_info;

        if (!$image_info)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getimagesizefromstring fail '.json_encode($_logs));
            return false;
        }

        $imageWidth = $image_info[0];
        $imageHeight = $image_info[1];
        $usedMemory = $imageWidth * $imageHeight * 5;//内存占用公式:宽*高*5, 单位byte
        $_logs['$usedMemory'] = $usedMemory;

        //不支持内存使用大于1G, 或分辨率为10000*10000以上的
        if (!empty(Yii::$app->params['memory_max_size']) && $usedMemory > Yii::$app->params['memory_max_size'])
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' usedmemory >1G '.json_encode($_logs));
            return false;
        }

        $imageResource = ImageHelper::imagecreatefromstring($imageString);
        if(!$imageResource)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $createfunc err '.json_encode($_logs));
            return false;
        }

        $imageWidth = ImageSX($imageResource);
        $imageHeight = ImageSY($imageResource);

        $imageType = FileHelper::fileextension($imageName);

        $imageResource = imagecreatetruecolor($imageWidth, $imageHeight);
        if(!$imageResource)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $createfunc err '.json_encode($_logs));
            return false;
        }

        if (!empty($storePath) && !file_exists($storePath))
        {
            @mkdir($storePath, 0777, true);
        }

        switch(strtolower($outputType)){
            case 'png':
                $func = 'imagepng';
                $filePath = $storePath .'/'. $imageName . '.png';
                $mime = 'image/png';
                break;
            case 'jpg':
            case 'jpeg':
                $func = 'imagejpeg';
                $filePath = $storePath .'/'. $imageName . '.jpg';
                $mime = 'image/jpeg';
                break;
            case 'gif':
                $func = 'imagegif';
                $filePath = $storePath .'/'. $imageName . '.gif';
                $mime = 'image/gif';
                break;
            default:
                $func = 'imagejpeg';
                $filePath = $storePath .'/'. $imageName . '.jpg';
                $mime = 'image/jpeg';
        }

        $white_alpha = imagecolorallocatealpha($imageResource,255,255,255, 75);
        $black_alpha = imagecolorallocatealpha($imageResource,0,0,0, 75);
        $yellow_alpha = imagecolorallocatealpha($imageResource, 255, 255, 0, 75);
        $red_alpha    = imagecolorallocatealpha($imageResource, 255, 0, 0, 75);
        $blue_alpha   = imagecolorallocatealpha($imageResource, 0, 0, 255, 75);
        $green_alpha = imagecolorallocatealpha($imageResource,0,128,0, 75);
        $gray_alpha = imagecolorallocatealpha($imageResource,117,121,117, 75);
        $transparent = imagecolorallocatealpha($imageResource,255,255,255, 127);

        $white = imagecolorallocate($imageResource,255,255,255);
        $black = imagecolorallocate($imageResource,0,0,0);
        $yellow = imagecolorallocate($imageResource, 255, 255, 0);
        $red    = imagecolorallocate($imageResource, 255, 0, 0);
        $blue   = imagecolorallocate($imageResource, 0, 0, 255);
        $green = imagecolorallocate($imageResource,0,128,0);
        $gray = imagecolorallocate($imageResource,117,121,117);


        if(isset($config['bg'])){
            //设置背景
            $bg = imagecolorallocate($imageResource,$config['bg']['r'],$config['bg']['g'],$config['bg']['b']);
            imagefill($imageResource, 0, 0, $bg);
        }else{
            //设置背景
            imagefill($imageResource, 0, 0, $black);
        }

        if (!empty($graphData) && is_array($graphData))
        {
            //$_logs['$graphData'] = $graphData;
            
            //对图形按照面积大小排序
            $graphDataSort = [];
            foreach ($graphData as $k => $v)
            {
                if (isset($v['type']) && in_array($v['type'], ['polygon', 'rect', 'triangle']))
                {
                    $sort = LabelHelper::polygon_area($v['points']);
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
            //$_logs['$graphData.1'] = $graphData;

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
                $color = '';
                if (isset($v['color']))
                {
                    if(is_array($v['color'])){
                        $color = imagecolorallocate($imageResource, $v['color']['r'], $v['color']['g'], $v['color']['b']);
                    }elseif (strpos($v['color'], '#') !== false)
                    {
                        $rgb = LabelHelper::hex_to_rgb($v['color']);
                        $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' color set '.json_encode($_logs));
                    }
                    else
                    {
                        $colorName = $v['color'];
                        if (isset($$colorName))
                        {
                            $color = $$colorName;
                        }
                        else
                        {
                            $_logs['$v'] = $v;
                            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' color notset '.json_encode($_logs));
                            $color = $red;
                        }
                    }
                }
                else
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' default '.json_encode($_logs));
                    $color = $red;
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
                    $point_x = $point_x * $imageWidth;
                    $point_y =  $point_y * $imageHeight;
                    imageellipse($imageResource,$point_x ,$point_y,3,3, $color);

                }
                //{\"type\":\"line\",\"points\":[{\"x\":0.2664041994750656,\"y\":0.0804899387576553},{\"x\":0.4120734908136483,\"y\":0.29571303587051617}],\"label\":\"\",\"stroke\":\"green\",\"strokeWidth\":4}
                elseif ($v['type'] == 'line')
                {
                    $start_point = $v['points'][0];
                    $end_point = $v['points'][1];

                    $start_point['x'] = $start_point['x'] * $imageWidth;
                    $start_point['y'] =  $start_point['y'] * $imageHeight;
                    $end_point['x'] = $end_point['x'] * $imageWidth;
                    $end_point['y'] =  $end_point['y'] * $imageHeight;

                    imageline($imageResource,$start_point['x'],$start_point['y'],$end_point['x'],$end_point['y'], $color);
                }
                //{\"type\":\"rect\",\"points\":[{\"x\":0.19291338582677164,\"y\":0.1994750656167979},{\"x\":0.2736220472440945,\"y\":0.1994750656167979},{\"x\":0.3543307086614173,\"y\":0.1994750656167979}
                elseif($v['type'] == 'rect_')
                {
                    //检查最小坐标点,最大坐标点
                    $left_top_point = [];
                    $right_bottom_point = [];
                    foreach ($v['points'] as $pointVal)
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

                    imagerectangle($imageResource,$left_top_point['x'],$left_top_point['y'],$right_bottom_point['x'],$right_bottom_point['y'], $color);
                    //imagefilledrectangle($imageResource,$left_top_point['x'],$left_top_point['y'],$right_bottom_point['x'],$right_bottom_point['y'], $color);
                }
                elseif($v['type'] == 'splinecurve' ){
                    $real_points = array();
                    foreach ($v['points'] as $k_ => $point_pair)
                    {
                        $real_points[] = [
                            'x' => $imageWidth * $point_pair['x'],
                            'y' => $imageHeight * $point_pair['y']
                        ];
                    }
                    $all_points = LabelHelper::spline($real_points);

                    //处理线宽
                    if($imageWidth > $imageHeight){
                        $proportion = $imageWidth / $imageHeight;
                        $widthbase = $imageWidth;
                    }else{
                        $proportion = $imageHeight / $imageWidth;
                        $widthbase = $imageHeight;
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
                    imagesetthickness ( $imageResource , $lineWidthPoly );
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

                        imageline($imageResource,
                            $x,
                            $y,
                            $next_x,
                            $next_y,
                            $color);
                    }
                }
                elseif ($v['type'] == 'closedcurve')
                {
                    $real_points = array();
                    foreach ($v['points'] as $k_ => $point_pair)
                    {
                        $real_points[] = [
                            'x' => $imageWidth * $point_pair['x'],
                            'y' => $imageHeight * $point_pair['y']
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
                        $real_points[] = $point_pair['x'];
                        $real_points[] = $point_pair['y'];
                    }

                    if (count($real_points) < 3)
                    {
                        $_logs['$real_points'] = $real_points;
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $real_points < 3 '.json_encode($_logs));
                        continue;
                    }

                    //imagepolygon($imageResource,$real_points,count($real_points)/2, $color);
                    imagefilledpolygon($imageResource,$real_points,count($real_points)/2, $color);
                }
                //{\"type\":\"polygon\",\"points\":[{\"x\":0.14041994750656167,\"y\":0.10148731408573929},{\"x\":0.07480314960629922,\"y\":0.35345581802274717}
                elseif (in_array($v['type'], ['polygon', 'rect', 'triangle', 'quadrangle', 'trapezoid']))
                {
                    //处理点
                    $real_points = array();
                    foreach ($v['points'] as $k_ => $point_pair)
                    {
                        if (is_array($point_pair))
                        {
                            $real_points[] = $imageWidth * $point_pair['x'];
                            $real_points[] = $imageHeight * $point_pair['y'];
                        }
                        else
                        {
                            //偶数是x坐标, 奇数是y坐标
                            if ($k_ % 2 == 0)
                            {
                                $real_points[] = $imageWidth * $point_pair;
                            }
                            else
                            {
                                $real_points[] = $imageHeight * $point_pair;
                            }
                        }
                    }

                    if (count($real_points) < 3)
                    {
                        $_logs['$real_points'] = $real_points;
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $real_points < 3 '.json_encode($_logs));
                        continue;
                    }

                    //imagepolygon($imageResource,$real_points,count($real_points)/2, $color);
                    imagefilledpolygon($imageResource,$real_points,count($real_points)/2, $color);
                }
                //长方体
                elseif (in_array($v['type'], ['cuboid']))
                {

                }
                elseif ($v['type'] == 'pencilline')
                {
                    $lineWidth = $v['strokeWidth'];
                    imagesetthickness($imageResource, $lineWidth);
                    
                    $real_points = array();
                    foreach ($v['points'] as $k_ => $point_pair)
                    {
                        $real_points[] = [
                            'x' => $imageWidth * $point_pair['x'],
                            'y' => $imageHeight * $point_pair['y']
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
                            
                            imageline($imageResource, $x, $y, $next_x, $next_y, $color);
                        }
                        imagefilledellipse($imageResource,$x,$y,$lineWidth,$lineWidth, $color);
                    }
                    
                }
                else
                {

                }


            }

        }

        //----------------------------------------

        if (empty($storePath))
        {
            ob_start();
            $func($imageResource);

            $final_image = ob_get_contents();

            ob_end_clean();

            $return = 'data:' . $mime . ';base64,' . base64_encode($final_image);
        }
        else
        {
            $return = $func($imageResource, $filePath);
        }
        imagedestroy($imageResource);

        if(!$return){
            return false;
        }
        return $return;
    }

    public static function image_mark_from_string($imageName, $imageString, $graphData, $storePath = null, $isShowLabel = true, $isFilled = false)
    {
        $_logs = [];

        $image_info = @getimagesizefromstring($imageString);
        $_logs['$image_info'] = $image_info;

        if (!$image_info)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getimagesizefromstring fail '.json_encode($_logs));
            return false;
        }

        $imageWidth = $image_info[0];
        $imageHeight = $image_info[1];
        $usedMemory = $imageWidth * $imageHeight * 5;//内存占用公式:宽*高*5, 单位byte
        $_logs['$usedMemory'] = $usedMemory;

        //不支持内存使用大于1G, 或分辨率为10000*10000以上的
        if (!empty(Yii::$app->params['memory_max_size']) && $usedMemory > Yii::$app->params['memory_max_size'])
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' usedmemory >1G '.json_encode($_logs));
            return false;
        }

        $imageResource = ImageHelper::imagecreatefromstring($imageString);
        if(!$imageResource)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $createfunc err '.json_encode($_logs));
            return false;
        }

        $imageWidth = ImageSX($imageResource);
        $imageHeight = ImageSY($imageResource);

        $imageType = FileHelper::fileextension($imageName);
        $_logs['$imageType'] = $imageType;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' pathinfo '.json_encode($_logs));

        if (!empty($storePath) && !file_exists($storePath))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mkdir '.json_encode($_logs));
            @mkdir($storePath, 0777, true);
        }
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' ttt2 '.json_encode($_logs));

        switch(strtolower($imageType)){
            case 'png':
                $func = 'imagepng';
                $filePath = $storePath .'/'. $imageName . '.png';
                $mime = 'image/png';
                break;
            case 'jpg':
            case 'jpeg':
                $func = 'imagejpeg';
                $filePath = $storePath .'/'. $imageName . '.jpg';
                $mime = 'image/jpeg';
                break;
            case 'gif':
                $func = 'imagegif';
                $filePath = $storePath .'/'. $imageName . '.gif';
                $mime = 'image/gif';
                break;
            default:
                $func = 'imagejpeg';
                $filePath = $storePath .'/'. $imageName . '.jpg';
                $mime = 'image/jpeg';
        }


        $white_alpha = imagecolorallocatealpha($imageResource,255,255,255, 75);
        $black_alpha = imagecolorallocatealpha($imageResource,0,0,0, 75);
        $yellow_alpha = imagecolorallocatealpha($imageResource, 255, 255, 0, 75);
        $red_alpha    = imagecolorallocatealpha($imageResource, 255, 0, 0, 75);
        $blue_alpha   = imagecolorallocatealpha($imageResource, 0, 0, 255, 75);
        $green_alpha = imagecolorallocatealpha($imageResource,0,128,0, 75);
        $gray_alpha = imagecolorallocatealpha($imageResource,117,121,117, 75);
        $transparent = imagecolorallocatealpha($imageResource,255,255,255, 127);

        $white = imagecolorallocate($imageResource,255,255,255);
        $black = imagecolorallocate($imageResource,0,0,0);
        $yellow = imagecolorallocate($imageResource, 255, 255, 0);
        $red    = imagecolorallocate($imageResource, 255, 0, 0);
        $blue   = imagecolorallocate($imageResource, 0, 0, 255);
        $green = imagecolorallocate($imageResource,0,128,0);
        $gray = imagecolorallocate($imageResource,117,121,117);

        //对label进行排序
        //$graphData = Functions::array_sort($graphData, 'label', 'asc');
        //if (count($graphData) > 1)
        //{
            //var_dump($graphData);
            //exit();
        //}

        if (!empty($graphData) && is_array($graphData))
        {
            //对图形按照面积大小排序
            $graphDataSort = [];
            foreach ($graphData as $k => $v)
            {
                if (isset($v['type']) && in_array($v['type'], ['polygon', 'rect', 'triangle']))
                {
                    $sort = LabelHelper::polygon_area($v['points']);
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
            
            //兼容两种点的坐标模式
            foreach ($graphData as $k => $v)
            {
                if (empty($v['type']))
                {
                    $_logs['$v'] = $v;
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' label no type '.json_encode($_logs));
                    continue;
                }

                $_logs['$graphData$v'] = $v;
                //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' loop '.json_encode($_logs));

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
                $color = '';
                if (isset($v['color']))
                {
                    if(is_array($v['color'])){
                        $color = imagecolorallocate($imageResource, $v['color']['r'], $v['color']['g'], $v['color']['b']);
                    }elseif (strpos($v['color'], '#') !== false) {
                        $rgb = LabelHelper::hex_to_rgb($v['color']);
                        $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                    }
                    else
                    {
                        $colorName = $v['color'];
                        if (isset($$colorName))
                        {
                            $color = $$colorName;
                        }
                        else
                        {
                            $_logs['$v'] = $v;
                            //Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' color notset '.json_encode($_logs));
                            $color = $red;
                        }
                    }
                }
                else
                {
                    $color = $red;
                }
                $_logs['$color'] = $color;
                //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $color '.json_encode($_logs));

                //没有类型, 按照字符串处理
                if (empty($v['type']))
                {
                    //$fontfile = Yii::getAlias('@yii/captcha/SpicyRice.ttf');
                    $fontfile = Yii::getAlias('@common/fonts/SIMYOU.TTF');
                    imagettftext($imageResource, 14, 0, 20, 20, $color, $fontfile, JsonHelper::json_encode_cn([$k => $v]));
                }

                //type\":\"point\",\"points\":[0.7624671916010499,0.0752405949256343],\"radius\":3,\"fill\":\"green\",\"top\":40,\"left\":578,\"label\":\"\"
                elseif ($v['type'] == 'point')
                {
                    $x = $v['points'][0];
                    $y = $v['points'][1];

                    imagefilledarc($imageResource,
                        $x * $imageWidth,
                        $y * $imageHeight,
                        8,
                        8,
                        0,
                        0,
                        $color,
                        IMG_ARC_PIE);
                }
                elseif ($v['type'] == 'points')
                {
                    foreach ($v['points'] as $pointItem)
                    {
                        $x = $pointItem['points'][0];
                        $y = $pointItem['points'][1];

                        imagefilledarc($imageResource,
                            $x * $imageWidth,
                            $y * $imageHeight,
                            8,
                            8,
                            0,
                            0,
                            $color,
                            IMG_ARC_PIE);
                    }

                }
                elseif ($v['type'] == 'bonepoint')
                {
                    $points = [];
                    foreach ($v['nodes'] as $pointItem)
                    {
                        $w = $pointItem['points'][0] * $imageWidth;
                        $h = $pointItem['points'][1] * $imageHeight;

                        $points[] = [$w, $h];

                        //颜色
                        if (in_array($pointItem['color'], ['rgba(186,23,20, 0.8)', '#ff0000']))
                        {
                            $_logs['$v'] = $pointItem;
                            //Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' color notset '.json_encode($_logs));
                            $color = $red;
                        }
                        elseif (isset($pointItem['isInside']) && ($pointItem['isInside'] === false || $pointItem['isInside'] === 'false'))
                        {
                            $rgb = LabelHelper::to_rgb($pointItem['color']);
                            $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                        }
                        elseif ($pointItem['visibility'] === true || $pointItem['visibility'] === 'true')
                        {
                            $rgb = LabelHelper::to_rgb($pointItem['color']);
                            $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                        }
                        else
                        {
                            $_logs['$v'] = $pointItem;
                            //Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' color notset '.json_encode($_logs));
                            $color = $red;
                        }

                        //imagefilledellipse($imageResource, $x * $imageWidth, $y * $imageHeight, 8, 8, $color);
                        imagefilledarc($imageResource, $w, $h, 8, 8, 0, 0, $color, IMG_ARC_PIE);
                        
                        if ($isShowLabel)
                        {
                            $pointItem['label'] = ProjectHandler::formatLabelValue($pointItem['label']);
                            $pointItem['code'] = ProjectHandler::formatLabelValue($pointItem['code']);
                            $label_value = $pointItem['code'] ? $pointItem['code'] : $pointItem['label'];
                            
                            $fontfile = Yii::getAlias('@common/fonts/SIMYOU.TTF');
                            imagettftext($imageResource, 8, 0, $w+8, $h+8, $red, $fontfile, $label_value);
                            //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' show label '.json_encode($_logs));
                        }
                    }

                    //14个骨骼点的情况
                    if (count($points) == 14)
                    {
                        //1,2,3点连线实线
                        imageline($imageResource, $points[0][0], $points[0][1], $points[1][0], $points[1][1], $color);
                        imageline($imageResource, $points[1][0], $points[1][1], $points[2][0], $points[2][1], $color);

                        //4,5,6点连线实线
                        imageline($imageResource, $points[3][0], $points[3][1], $points[4][0], $points[4][1], $color);
                        imageline($imageResource, $points[4][0], $points[4][1], $points[5][0], $points[5][1], $color);

                        //7,8,9点连线实线
                        imageline($imageResource, $points[6][0], $points[6][1], $points[7][0], $points[7][1], $color);
                        imageline($imageResource, $points[7][0], $points[7][1], $points[8][0], $points[8][1], $color);

                        //10,11,12点连线实线
                        imageline($imageResource, $points[9][0], $points[9][1], $points[10][0], $points[10][1], $color);
                        imageline($imageResource, $points[10][0], $points[10][1], $points[11][0], $points[11][1], $color);

                        //13,14点连线实线
                        imageline($imageResource, $points[12][0], $points[12][1], $points[13][0], $points[13][1], $color);

                        //画一条虚线，5 个红色像素，4 个背景像素
                        $bg = imagecolorallocate($imageResource, 204, 204, 204);
                        $style = array($color, $color, $color, $color, $color, $bg, $bg, $bg, $bg);

                        //1,7点连线虚线
                        imagesetstyle($imageResource, $style);
                        imageline($imageResource, $points[0][0], $points[0][1], $points[6][0], $points[6][1], IMG_COLOR_STYLED);

                        //4,10点连线虚线
                        //画一条虚线，5 个红色像素，4 个背景像素
                        imagesetstyle($imageResource, $style);
                        imageline($imageResource, $points[3][0], $points[3][1], $points[9][0], $points[9][1], IMG_COLOR_STYLED);

                        //7,10点连线虚线
                        //画一条虚线，5 个红色像素，4 个背景像素
                        imagesetstyle($imageResource, $style);
                        imageline($imageResource, $points[6][0], $points[6][1], $points[9][0], $points[9][1], IMG_COLOR_STYLED);

                        //1,14点连线虚线
                        //画一条虚线，5 个红色像素，4 个背景像素
                        imagesetstyle($imageResource, $style);
                        imageline($imageResource, $points[0][0], $points[0][1], $points[13][0], $points[13][1], IMG_COLOR_STYLED);

                        //4,14点连线虚线
                        //画一条虚线，5 个红色像素，4 个背景像素
                        imagesetstyle($imageResource, $style);
                        imageline($imageResource, $points[3][0], $points[3][1], $points[13][0], $points[13][1], IMG_COLOR_STYLED);

                    }
                    elseif (count($points) == 21)
                    {

                        //0,1,2,3,4点连线实线
                        $rgb = LabelHelper::hex_to_rgb('#cc0000');
                        $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                        imageline($imageResource, $points[0][0], $points[0][1], $points[1][0], $points[1][1], $color);//0-1

                        $rgb = LabelHelper::hex_to_rgb('#b20000');
                        $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                        imageline($imageResource, $points[1][0], $points[1][1], $points[2][0], $points[2][1], $color);//1-2

                        $rgb = LabelHelper::hex_to_rgb('#e60000');
                        $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                        imageline($imageResource, $points[2][0], $points[2][1], $points[3][0], $points[3][1], $color);//2-3

                        $rgb = LabelHelper::hex_to_rgb('#ff0000');
                        $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                        imageline($imageResource, $points[3][0], $points[3][1], $points[4][0], $points[4][1], $color);//3-4

                        //0,5,6,7,8点连线实线
                        $rgb = LabelHelper::hex_to_rgb('#a3cc00');
                        $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                        imageline($imageResource, $points[0][0], $points[0][1], $points[5][0], $points[5][1], $color);//0-5

                        $rgb = LabelHelper::hex_to_rgb('#8eb300');
                        $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                        imageline($imageResource, $points[5][0], $points[5][1], $points[6][0], $points[6][1],  $color);//5-6

                        $rgb = LabelHelper::hex_to_rgb('#b8e600');
                        $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                        imageline($imageResource, $points[6][0], $points[6][1], $points[7][0], $points[7][1],  $color);//6-7

                        $rgb = LabelHelper::hex_to_rgb('#cbff00');
                        $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                        imageline($imageResource, $points[7][0], $points[7][1], $points[8][0], $points[8][1],  $color);//7-8

                        //0,9,10,11,12点连线实线
                        $rgb = LabelHelper::hex_to_rgb('#00cc52');
                        $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                        imageline($imageResource, $points[0][0], $points[0][1], $points[9][0], $points[9][1], $color);//0-9

                        $rgb = LabelHelper::hex_to_rgb('#00b245');
                        $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                        imageline($imageResource, $points[9][0], $points[9][1], $points[10][0], $points[10][1], $color);//9-10

                        $rgb = LabelHelper::hex_to_rgb('#00e65c');
                        $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                        imageline($imageResource, $points[10][0], $points[10][1], $points[11][0], $points[11][1], $color);//10-11

                        $rgb = LabelHelper::hex_to_rgb('#00ff66');
                        $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                        imageline($imageResource, $points[11][0], $points[11][1], $points[12][0], $points[12][1], $color);//11-12

                        //0,13,14,15,16点连线实线
                        $rgb = LabelHelper::hex_to_rgb('#0052cc');
                        $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                        imageline($imageResource, $points[0][0], $points[0][1], $points[13][0], $points[13][1], $color);//0-13

                        $rgb = LabelHelper::hex_to_rgb('#0045b2');
                        $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                        imageline($imageResource, $points[13][0], $points[13][1], $points[14][0], $points[14][1], $color);//13-14

                        $rgb = LabelHelper::hex_to_rgb('#0059e8');
                        $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                        imageline($imageResource, $points[14][0], $points[14][1], $points[15][0], $points[15][1], $color);//14-15

                        $rgb = LabelHelper::hex_to_rgb('#0066ff');
                        $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                        imageline($imageResource, $points[15][0], $points[15][1], $points[16][0], $points[16][1], $color);//15-16

                        //0,17,18,19,20点连线实线
                        $rgb = LabelHelper::hex_to_rgb('#a200cc');
                        $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                        imageline($imageResource, $points[0][0], $points[0][1], $points[17][0], $points[17][1], $color);//0-17

                        $rgb = LabelHelper::hex_to_rgb('#8e00b3');
                        $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                        imageline($imageResource, $points[17][0], $points[17][1], $points[18][0], $points[18][1], $color);//17-18

                        $rgb = LabelHelper::hex_to_rgb('#b800e6');
                        $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                        imageline($imageResource, $points[18][0], $points[18][1], $points[19][0], $points[19][1], $color);//18-19

                        $rgb = LabelHelper::hex_to_rgb('#cb00ff');
                        $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                        imageline($imageResource, $points[19][0], $points[19][1], $points[20][0], $points[20][1], $color);//19-20
                    }

                }
                elseif ($v['type'] == 'splinecurve')
                {
                    $real_points = array();
                    foreach ($v['points'] as $k_ => $point_pair)
                    {
                        $real_points[] = [
                            'x' => $imageWidth * $point_pair['x'],
                            'y' => $imageHeight * $point_pair['y']
                        ];
                    }
                    
                    $all_points = LabelHelper::spline($real_points);
                    //$_logs['$all_points'] = $all_points;
                    //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' spline '.json_encode($_logs));
                    
                    if (!$all_points)
                    {
                        continue;
                    }

                    foreach ($all_points as $k_ => $point_pair)
                    {
                        $x = $point_pair['x'];
                        $y = $point_pair['y'];
                        imagefilledarc($imageResource,
                            $x,
                            $y,
                            $imageWidth * 2 /1000 ,
                            $imageHeight * 2 / 1000,
                            0,
                            0,
                            $color,
                            IMG_ARC_PIE);
                    }

                    if ($isShowLabel)
                    {
                        $real_points_count = count($all_points);
                        $show_label_position = ceil($real_points_count/2);
                        $show_label_point = $all_points[$show_label_position];
                        $_logs['$show_label_position'] = $show_label_position;
                        $_logs['$show_label_point'] = $show_label_point;

                        $v['label'] = ProjectHandler::formatLabelValue($v['label']);
                        $v['code'] = ProjectHandler::formatLabelValue($v['code']);
                        $label_value = $v['code'] ? $v['code'] : $v['label'];

                        //$fontfile = Yii::getAlias('@yii/captcha/SpicyRice.ttf');
                        $fontfile = Yii::getAlias('@common/fonts/SIMYOU.TTF');
                        imagettftext($imageResource, 14, 0, $show_label_point['x'], $show_label_point['y'], $color, $fontfile, $label_value);
                        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' show label '.json_encode($_logs));
                    }

                }
                elseif ($v['type'] == 'closedcurve')
                {
                    $real_points = array();
                    foreach ($v['points'] as $k_ => $point_pair)
                    {
                        $real_points[] = [
                            'x' => $imageWidth * $point_pair['x'],
                            'y' => $imageHeight * $point_pair['y']
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
                        $real_points[] = $point_pair['x'];
                        $real_points[] = $point_pair['y'];
                    }

                    if (count($real_points) < 3)
                    {
                        $_logs['$real_points'] = $real_points;
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $real_points < 3 '.json_encode($_logs));
                        continue;
                    }

                    //imagepolygon($imageResource,$real_points,count($real_points)/2, $color);
                    imagepolygon($imageResource,$real_points,count($real_points)/2, $color);
                }
                //{\"type\":\"line\",\"points\":[{\"x\":0.2664041994750656,\"y\":0.0804899387576553},{\"x\":0.4120734908136483,\"y\":0.29571303587051617}],\"label\":\"\",\"stroke\":\"green\",\"strokeWidth\":4}
                elseif ($v['type'] == 'line')
                {
                    $start_point = $v['points'][0];
                    $end_point = $v['points'][1];

                    imageline(
                        $imageResource,
                        $start_point['x'] * $imageWidth,
                        $start_point['y'] * $imageHeight,
                        $end_point['x'] * $imageWidth,
                        $end_point['y'] * $imageHeight,
                        $color);
                }
                elseif (in_array($v['type'], ['unclosedpolygon']))
                {
                    $real_points = array();
                    foreach ($v['points'] as $k_ => $point_pair)
                    {
                        $real_points[] = [
                            'x' => $imageWidth * $point_pair['x'],
                            'y' => $imageHeight * $point_pair['y']
                        ];
                    }

                    $all_points = $real_points;
                    //$_logs['$all_points'] = $all_points;
                    //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' spline '.json_encode($_logs));

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

                        imageline($imageResource,
                            $x,
                            $y,
                            $next_x,
                            $next_y,
                            $color);
                    }

                    if ($isShowLabel)
                    {
                        $real_points_count = count($all_points);
                        $show_label_position = ceil($real_points_count/2);
                        $show_label_point = $all_points[$show_label_position];
                        $_logs['$show_label_position'] = $show_label_position;
                        $_logs['$show_label_point'] = $show_label_point;

                        $v['label'] = ProjectHandler::formatLabelValue($v['label']);
                        $v['code'] = ProjectHandler::formatLabelValue($v['code']);
                        $label_value = $v['code'] ? $v['code'] : $v['label'];
                        
                        //$fontfile = Yii::getAlias('@yii/captcha/SpicyRice.ttf');
                        $fontfile = Yii::getAlias('@common/fonts/SIMYOU.TTF');
                        imagettftext($imageResource, 14, 0, $show_label_point['x'], $show_label_point['y'], $color, $fontfile, $label_value);
                        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' show label '.json_encode($_logs));
                    }
                }
                //{\"type\":\"rect\",\"points\":[{\"x\":0.19291338582677164,\"y\":0.1994750656167979},{\"x\":0.2736220472440945,\"y\":0.1994750656167979},{\"x\":0.3543307086614173,\"y\":0.1994750656167979}
                elseif($v['type'] == 'rect_')
                {
                    //检查最小坐标点,最大坐标点
                    $left_top_point = [];
                    $right_bottom_point = [];
                    foreach ($v['points'] as $pointVal)
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

                    imagerectangle(
                        $imageResource,
                        $left_top_point['x'] * $imageWidth,
                        $left_top_point['y'] * $imageHeight,
                        $right_bottom_point['x'] * $imageWidth,
                        $right_bottom_point['y'] * $imageHeight,
                        $color);
                    //imagefilledrectangle($imageResource,$left_top_point['x'],$left_top_point['y'],$right_bottom_point['x'],$right_bottom_point['y'], $color);
                }
                //{\"type\":\"polygon\",\"points\":[{\"x\":0.14041994750656167,\"y\":0.10148731408573929},{\"x\":0.07480314960629922,\"y\":0.35345581802274717}
                elseif (in_array($v['type'], ['polygon', 'rect', 'triangle', 'trapezoid', 'quadrangle']))
                {
                    //处理点
                    $real_points = array();
                    foreach ($v['points'] as $k_ => $point_pair)
                    {
                        $real_points[] = $imageWidth * $point_pair['x'];
                        $real_points[] = $imageHeight * $point_pair['y'];
                    }


                    if (count($real_points) < 3)
                    {
                        $_logs['$real_points'] = $real_points;
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $real_points < 3 '.json_encode($_logs));
                        continue;
                    }

                    //处理线宽
                    $widthbase = '';
                    if($imageWidth > $imageHeight){
                        $proportion = $imageWidth / $imageHeight;
                        $widthbase = $imageWidth;
                    }else{
                        $proportion = $imageHeight / $imageWidth;
                        $widthbase = $imageHeight;
                    }
                    if($proportion > 4){
                        $lineproportion = $proportion / 2;
                    }elseif($proportion > 2  &&  $proportion < 4){
                        $lineproportion = $proportion / 2.2;
                    }else{
                        $lineproportion = $proportion;
                    }

                    if(isset($v['pointsAtoA'])){
                        $lineWidthPoly = floor($widthbase * $lineproportion / 1000);
                        if($lineWidthPoly < 1){
                            $lineWidthPoly = 1;
                        }
                    }else{
                        $lineWidthPoly = floor($widthbase * $lineproportion / 1500);
                        if($lineWidthPoly < 1){
                            $lineWidthPoly = 1;
                        }

                    }
                    // Set the line thickness
                    imagesetthickness ( $imageResource , $lineWidthPoly );

                    if ($isFilled)
                    {
                        imagefilledpolygon($imageResource,$real_points,count($real_points)/2, $color);
                    }
                    else
                    {
                        imagepolygon($imageResource,$real_points,count($real_points)/2, $color);
                    }

                    if ($isShowLabel)
                    {
                        $v['label'] = ProjectHandler::formatLabelValue($v['label']);
                        $v['code'] = ProjectHandler::formatLabelValue($v['code']);
                        $label_value = $v['code'] ? $v['code'] : $v['label'];
                        
                        //$fontfile = Yii::getAlias('@yii/captcha/SpicyRice.ttf');
                        $fontfile = Yii::getAlias('@common/fonts/SIMYOU.TTF');
                        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' show label '.json_encode($_logs));
                        if(isset($v['pointsAtoA'])){
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' pointsAtoA '.json_encode($v['pointsAtoA']));
                            imagettftext($imageResource, 18, 0, $v['pointsAtoA'][0]['x'] * $imageWidth + 5, $v['pointsAtoA'][0]['y'] * $imageHeight,$color, $fontfile, $v['pointsAtoA'][0]['label']);
                            imagettftext($imageResource, 18, 0, $v['pointsAtoA'][1]['x'] * $imageWidth - 8, $v['pointsAtoA'][1]['y'] * $imageHeight,$color, $fontfile, $v['pointsAtoA'][1]['label']);
                        }else{
                            imagettftext($imageResource, 14, 0, $real_points[0], $real_points[1], $color, $fontfile, $label_value);
                        }
                    }

                }
                //长方体,梯形
                elseif (in_array($v['type'], ['cuboid']))
                {
                    //处理点
                    $front_points = [];
                    $back_points = [];
                    foreach ($v['points'] as $k_ => $point_pair)
                    {
                        //0,1,2,3
                        if ($k_ < 4)
                        {
                            $front_points[] = $imageWidth * $point_pair['x'];
                            $front_points[] = $imageHeight * $point_pair['y'];
                        }
                        else
                        {
                            $back_points[] = $imageWidth * $point_pair['x'];
                            $back_points[] = $imageHeight * $point_pair['y'];
                        }
                    }
                    
                    if (count($front_points) < 3 || count($back_points) < 3)
                    {
                        $_logs['$front_points'] = $front_points;
                        $_logs['$back_points'] = $back_points;
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' points < 3 '.json_encode($_logs));
                        continue;
                    }

                    imagepolygon($imageResource,$front_points,count($front_points)/2, $color);
                    imagepolygon($imageResource,$back_points,count($back_points)/2, $color);

                    //画一条虚线，5 个红色像素，4 个背景像素
                    $bg = imagecolorallocate($imageResource, 255, 255, 255);
                    $style = array($color, $color, $color, $color, $color, $bg, $bg, $bg, $bg, $bg);

                    //1,7点连线虚线
                    //imagesetstyle($imageResource, $style);
                    imageline($imageResource, $front_points[0], $front_points[1], $back_points[0], $back_points[1], $color);
                    //imagesetstyle($imageResource, $style);
                    imageline($imageResource, $front_points[2], $front_points[3], $back_points[2], $back_points[3], $color);
                    //imagesetstyle($imageResource, $style);
                    imageline($imageResource, $front_points[4], $front_points[5], $back_points[4], $back_points[5], $color);
                    //imagesetstyle($imageResource, $style);
                    imageline($imageResource, $front_points[6], $front_points[7], $back_points[6], $back_points[7], $color);

                    if ($isShowLabel)
                    {
                        $v['label'] = ProjectHandler::formatLabelValue($v['label']);
                        $v['code'] = ProjectHandler::formatLabelValue($v['code']);
                        $label_value = $v['code'] ? $v['code'] : $v['label'];

                        //$fontfile = Yii::getAlias('@yii/captcha/SpicyRice.ttf');
                        $fontfile = Yii::getAlias('@common/fonts/SIMYOU.TTF');
                        imagettftext($imageResource, 14, 0, $front_points[0], $front_points[1], $color, $fontfile, $label_value);
                        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' show label '.json_encode($_logs));
                    }


                }
                elseif ($v['type'] == 'pencilline')
                {
                    $lineWidth = $v['strokeWidth'];
                    imagesetthickness($imageResource, $lineWidth);
                    
                    $real_points = array();
                    foreach ($v['points'] as $k_ => $point_pair)
                    {
                        $real_points[] = [
                            'x' => $imageWidth * $point_pair['x'],
                            'y' => $imageHeight * $point_pair['y']
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
                            
                            imageline($imageResource, $x, $y, $next_x, $next_y, $color);
                        }
                        imagefilledellipse($imageResource,$x,$y,$lineWidth,$lineWidth, $color);
                    }
                    
                    
                    if ($isShowLabel)
                    {
                        $real_points_count = count($all_points);
                        $show_label_position = ceil($real_points_count/2);
                        $show_label_point = $all_points[$show_label_position];
                        $_logs['$show_label_position'] = $show_label_position;
                        $_logs['$show_label_point'] = $show_label_point;
                    
                        $v['label'] = ProjectHandler::formatLabelValue($v['label']);
                        $v['code'] = ProjectHandler::formatLabelValue($v['code']);
                        $label_value = $v['code'] ? $v['code'] : $v['label'];
                    
                        //$fontfile = Yii::getAlias('@yii/captcha/SpicyRice.ttf');
                        $fontfile = Yii::getAlias('@common/fonts/SIMYOU.TTF');
                        imagettftext($imageResource, 14, 0, $show_label_point['x'], $show_label_point['y'], $color, $fontfile, $label_value);
                        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' show label '.json_encode($_logs));
                    }
                    
                    
                }
                //未知类型, 当做字符串处理
                else
                {
                    //$fontfile = Yii::getAlias('@common/fonts/SIMYOU.TTF');
                    //imagettftext($imageResource, 14, 0, 0, 0, $color, $fontfile, JsonHelper::json_encode_cn([$k => $v]));
                }

            }
        }


        //----------------------------------------

        $dst_im = imagecreatetruecolor($imageWidth, $imageHeight);
        imagecopyresampled($dst_im, $imageResource, 0, 0, 0, 0, $imageWidth, $imageHeight, $imageWidth, $imageHeight);
        $imageResource = $dst_im;

        //----------------------------------------
        if (empty($storePath))
        {
            ob_start();
            $func($imageResource);

            $final_image = ob_get_contents();

            ob_end_clean();

            $return = 'data:' . $mime . ';base64,' . base64_encode($final_image);
        }
        else
        {
            $return = $func($imageResource, $filePath);
        }

        imagedestroy($imageResource);

        if(!$return){
            return false;
        }
        return $return;
    }


    /**
     * 生成标记图
     *
     *
     * @param string $imageUrl
     * @param null|string $graphData 图形数据, 若为null则返回base64格式的图片流; 若为字符串, 则保存到该地址
     * @param string $storePath
     * @param string $outputType
     * @param int $newWidth : null 表原图宽度
     * @param int $newHeight : null 表原图高度
     */
    public static function image_markFilled_from_string($imageName, $imageString, $graphData, $storePath = null, $config = array())
    {
        $_logs = [];

        $image_info = @getimagesizefromstring($imageString);
        $_logs['$image_info'] = $image_info;

        if (!$image_info)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getimagesizefromstring fail '.json_encode($_logs));
            return false;
        }

        $imageWidth = $image_info[0];
        $imageHeight = $image_info[1];
        $usedMemory = $imageWidth * $imageHeight * 5;//内存占用公式:宽*高*5, 单位byte
        $_logs['$usedMemory'] = $usedMemory;

        //不支持内存使用大于1G, 或分辨率为10000*10000以上的
        if (!empty(Yii::$app->params['memory_max_size']) && $usedMemory > Yii::$app->params['memory_max_size'])
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' usedmemory >1G '.json_encode($_logs));
            return false;
        }

        $imageResource = ImageHelper::imagecreatefromstring($imageString);
        if(!$imageResource)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $createfunc err '.json_encode($_logs));
            return false;
        }

        $imageWidth = ImageSX($imageResource);
        $imageHeight = ImageSY($imageResource);

        $imageType = FileHelper::fileextension($imageName);
        $_logs['$imageType'] = $imageType;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' pathinfo '.json_encode($_logs));

        if (!empty($storePath) && !file_exists($storePath))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mkdir '.json_encode($_logs));
            @mkdir($storePath, 0777, true);
        }
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' ttt2 '.json_encode($_logs));

        switch(strtolower($imageType)){
            case 'png':
                $func = 'imagepng';
                $filePath = $storePath .'/'. $imageName . '.png';
                $mime = 'image/png';
                break;
            case 'jpg':
            case 'jpeg':
                $func = 'imagejpeg';
                $filePath = $storePath .'/'. $imageName . '.jpg';
                $mime = 'image/jpeg';
                break;
            case 'gif':
                $func = 'imagegif';
                $filePath = $storePath .'/'. $imageName . '.gif';
                $mime = 'image/gif';
                break;
            default:
                $func = 'imagejpeg';
                $filePath = $storePath .'/'. $imageName . '.jpg';
                $mime = 'image/jpeg';
        }

        $white_alpha = imagecolorallocatealpha($imageResource,255,255,255, 75);
        $black_alpha = imagecolorallocatealpha($imageResource,0,0,0, 75);
        $yellow_alpha = imagecolorallocatealpha($imageResource, 255, 255, 0, 75);
        $red_alpha    = imagecolorallocatealpha($imageResource, 255, 0, 0, 75);
        $blue_alpha   = imagecolorallocatealpha($imageResource, 0, 0, 255, 75);
        $green_alpha = imagecolorallocatealpha($imageResource,0,128,0, 75);
        $gray_alpha = imagecolorallocatealpha($imageResource,117,121,117, 75);
        $transparent = imagecolorallocatealpha($imageResource,255,255,255, 127);

        $white = imagecolorallocate($imageResource,255,255,255);
        $black = imagecolorallocate($imageResource,0,0,0);
        $yellow = imagecolorallocate($imageResource, 255, 255, 0);
        $red    = imagecolorallocate($imageResource, 255, 0, 0);
        $blue   = imagecolorallocate($imageResource, 0, 0, 255);
        $green = imagecolorallocate($imageResource,0,128,0);
        $gray = imagecolorallocate($imageResource,117,121,117);
        $other1 = imagecolorallocate($imageResource,0,250,154);
        $other2 = imagecolorallocate($imageResource,34,139,34);
        $other3 = imagecolorallocate($imageResource,255,140,0);
        $other4 = imagecolorallocate($imageResource,244,164,96);

        //对label进行排序
        //$graphData = Functions::array_sort($graphData, 'label', 'asc');
        //if (count($graphData) > 1)
        //{
        //var_dump($graphData);
        //exit();
        //}

        if (!empty($graphData) && is_array($graphData))
        {
            //对图形按照面积大小排序
            $graphDataSort = [];
            foreach ($graphData as $k => $v)
            {
                if (isset($v['type']) && in_array($v['type'], ['polygon', 'rect', 'triangle']))
                {
                    $sort = LabelHelper::polygon_area($v['points']);
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
            
            
            foreach ($graphData as $k => $v)
            {
                if (empty($v['type']))
                {
                    $_logs['$v'] = $v;
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' label no type '.json_encode($_logs));
                    continue;
                }

                $_logs['$graphData$v'] = $v;
                //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' loop '.json_encode($_logs));

                //处理颜色
                $color = '';
                if (isset($v['color']))
                {
                    if (strpos($v['color'], '#') !== false)
                    {
                        $rgb = LabelHelper::hex_to_rgb($v['color']);
                        //黑色不能使用透明度
                        if($v['color'] == '#000000'||(isset($config['opacity']) && $config['opacity'])){
                            $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                        }else{
                            $color = imagecolorallocatealpha($imageResource, $rgb['r'], $rgb['g'], $rgb['b'],75);
                        }
                        
                    }
                    else
                    {
                        $colorName = $v['color'];
                        if (isset($$colorName))
                        {
                            $color = $$colorName;
                        }
                        else
                        {
                            $_logs['$v'] = $v;
                            //Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' color notset '.json_encode($_logs));
                            $color = $red;
                        }
                    }
                }
                else
                {
                    $color = $red;
                }
                $_logs['$color'] = $color;
                //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $color '.json_encode($_logs));

                //没有类型, 按照字符串处理
                if (empty($v['type']))
                {
                    //$fontfile = Yii::getAlias('@yii/captcha/SpicyRice.ttf');
                    $fontfile = Yii::getAlias('@common/fonts/SIMYOU.TTF');
                    imagettftext($imageResource, 14, 0, 0, 0, $color, $fontfile, JsonHelper::json_encode_cn([$k => $v]));
                }
                //type\":\"point\",\"points\":[0.7624671916010499,0.0752405949256343],\"radius\":3,\"fill\":\"green\",\"top\":40,\"left\":578,\"label\":\"\"
                elseif ($v['type'] == 'point')
                {
                    $x = $v['points'][0];
                    $y = $v['points'][1];

                    //                 imagefilledellipse(
                    //                     $imageResource,
                    //                     $x * $imageWidth,
                    //                     $y * $imageHeight,
                    //                     8,
                    //                     8,
                    //                     $color);

                    imagefilledarc($imageResource,
                        $x * $imageWidth,
                        $y * $imageHeight,
                        8,
                        8,
                        0,
                        0,
                        $color,
                        IMG_ARC_PIE);
                }
                elseif ($v['type'] == 'points')
                {
                    foreach ($v['points'] as $pointItem)
                    {
                        $x = $pointItem['points'][0];
                        $y = $pointItem['points'][1];

                        //                 imagefilledellipse(
                        //                     $imageResource,
                        //                     $x * $imageWidth,
                        //                     $y * $imageHeight,
                        //                     8,
                        //                     8,
                        //                     $color);

                        imagefilledarc($imageResource,
                            $x * $imageWidth,
                            $y * $imageHeight,
                            8,
                            8,
                            0,
                            0,
                            $color,
                            IMG_ARC_PIE);
                    }

                }
                //{\"type\":\"line\",\"points\":[{\"x\":0.2664041994750656,\"y\":0.0804899387576553},{\"x\":0.4120734908136483,\"y\":0.29571303587051617}],\"label\":\"\",\"stroke\":\"green\",\"strokeWidth\":4}
                elseif ($v['type'] == 'line')
                {
                    $start_point = $v['points'][0];
                    $end_point = $v['points'][1];

                    imageline(
                        $imageResource,
                        $start_point['x'] * $imageWidth,
                        $start_point['y'] * $imageHeight,
                        $end_point['x'] * $imageWidth,
                        $end_point['y'] * $imageHeight,
                        $color);
                }
                //{\"type\":\"rect\",\"points\":[{\"x\":0.19291338582677164,\"y\":0.1994750656167979},{\"x\":0.2736220472440945,\"y\":0.1994750656167979},{\"x\":0.3543307086614173,\"y\":0.1994750656167979}
                elseif($v['type'] == 'rect_')
                {
                    //检查最小坐标点,最大坐标点
                    $left_top_point = [];
                    $right_bottom_point = [];
                    foreach ($v['points'] as $pointVal)
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

                    imagerectangle(
                        $imageResource,
                        $left_top_point['x'] * $imageWidth,
                        $left_top_point['y'] * $imageHeight,
                        $right_bottom_point['x'] * $imageWidth,
                        $right_bottom_point['y'] * $imageHeight,
                        $color);
                    //imagefilledrectangle($imageResource,$left_top_point['x'],$left_top_point['y'],$right_bottom_point['x'],$right_bottom_point['y'], $color);
                }
                //{\"type\":\"polygon\",\"points\":[{\"x\":0.14041994750656167,\"y\":0.10148731408573929},{\"x\":0.07480314960629922,\"y\":0.35345581802274717}
                elseif (in_array($v['type'], ['polygon', 'rect', 'triangle', 'trapezoid', 'quadrangle']))
                {
                    //处理点
                    $real_points = array();
                    foreach ($v['points'] as $k_ => $point_pair)
                    {
                        if (is_array($point_pair))
                        {
                            $real_points[] = $imageWidth * $point_pair['x'];
                            $real_points[] = $imageHeight * $point_pair['y'];
                        }
                        else
                        {
                            //偶数是x坐标, 奇数是y坐标
                            if ($k_ % 2 == 0)
                            {
                                $real_points[] = $imageWidth * $point_pair;
                            }
                            else
                            {
                                $real_points[] = $imageHeight * $point_pair;
                            }
                        }
                    }

                    if (count($real_points) < 3)
                    {
                        $_logs['$real_points'] = $real_points;
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $real_points < 3 '.json_encode($_logs));
                        continue;
                    }

                    //填充颜色
                    if(isset($config[$v['type']]) && !$config[$v['type']])
                    {
                        //不填充
                        imagepolygon($imageResource,$real_points,count($real_points)/2, $color);
                    }
                    else
                    {
                        //填充
                        imagefilledpolygon($imageResource,$real_points,count($real_points)/2, $color);
                    }


                    if(isset($v['label'])){

                        $v['label'] = ProjectHandler::formatLabelValue($v['label']);
                        $v['code'] = ProjectHandler::formatLabelValue($v['code']);
                        $label_value = $v['code'] ? $v['code'] : $v['label'];

                        //$fontfile = Yii::getAlias('@yii/captcha/SpicyRice.ttf');
                        $fontfile = Yii::getAlias('@common/fonts/SIMYOU.TTF');
                        imagettftext($imageResource, 14, 0, $real_points[0], $real_points[1], $color, $fontfile, $label_value);
                    }
                }
                elseif ($v['type'] == 'pencilline')
                {
                    $lineWidth = $v['strokeWidth'];
                    imagesetthickness($imageResource, $lineWidth);
                    
                    $real_points = array();
                    foreach ($v['points'] as $k_ => $point_pair)
                    {
                        $real_points[] = [
                            'x' => $imageWidth * $point_pair['x'],
                            'y' => $imageHeight * $point_pair['y']
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
                        
                            imageline($imageResource, $x, $y, $next_x, $next_y, $color);
                        }
                        imagefilledellipse($imageResource,$x,$y,$lineWidth,$lineWidth, $color);
                    }
                    
                }
                //未知类型, 当做字符串处理
                else
                {
                    //$fontfile = Yii::getAlias('@common/fonts/SIMYOU.TTF');
                    //imagettftext($imageResource, 14, 0, 0, 0, $color, $fontfile, JsonHelper::json_encode_cn([$k => $v]));
                }

            }
        }

        //----------------------------------------

        $dst_im = imagecreatetruecolor($imageWidth, $imageHeight);
        imagecopyresampled($dst_im, $imageResource, 0, 0, 0, 0, $imageWidth, $imageHeight, $imageWidth, $imageHeight);
        $imageResource = $dst_im;

        //----------------------------------------
        if (empty($storePath))
        {
            ob_start();
            $func($imageResource);

            $final_image = ob_get_contents();

            ob_end_clean();

            $return = 'data:' . $mime . ';base64,' . base64_encode($final_image);
        }
        else
        {
            $return = $func($imageResource, $filePath);
        }

        imagedestroy($imageResource);

        if(!$return){
            return false;
        }
        return $return;
    }

    /**
     * 按照标注的矩形框裁剪图片
     * @param $imageName 图片名包含路径
     * @param $imageString 图片信息
     * @param $graphData 标注结果
     * @param null $storePath 保存路径
     */
    public static function image_rect_copy($imageName, $imageString, $graphData, $storePath = null, $config = [] ){
        $_logs = [];

        $image_info = @getimagesizefromstring($imageString);
        $_logs['$image_info'] = $image_info;

        if (!$image_info)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getimagesizefromstring fail '.json_encode($_logs));
            return false;
        }

        $imageWidth = $image_info[0];
        $imageHeight = $image_info[1];
        $usedMemory = $imageWidth * $imageHeight * 5;//内存占用公式:宽*高*5, 单位byte
        $_logs['$usedMemory'] = $usedMemory;

        //不支持内存使用大于1G, 或分辨率为10000*10000以上的
        if (!empty(Yii::$app->params['memory_max_size']) && $usedMemory > Yii::$app->params['memory_max_size'])
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' usedmemory >1G '.json_encode($_logs));
            return false;
        }

        $imageResource = ImageHelper::imagecreatefromstring($imageString);
        if(!$imageResource)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $createfunc err '.json_encode($_logs));
            return false;
        }

        $imageWidth = ImageSX($imageResource);
        $imageHeight = ImageSY($imageResource);

        $imageType = FileHelper::fileextension($imageName);
        $_logs['$imageType'] = $imageType;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' pathinfo '.json_encode($_logs));

        if (!empty($storePath) && !file_exists($storePath))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mkdir '.json_encode($_logs));
            @mkdir($storePath, 0777, true);
        }
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' ttt2 '.json_encode($_logs));
        if(isset($config['ext'])){
            $ext = $config['ext'];
        }else{
            $ext = strtolower($imageType);
        }
        switch($ext){
            case 'png':
                $func = 'imagepng';
                $filePath = $storePath .'/'. $imageName;
                $ext = '.png';
                $mime = 'image/png';
                break;
            case 'jpg':
            case 'jpeg':
                $func = 'imagejpeg';
                $filePath = $storePath .'/'. $imageName;
                $ext = '.jpg';
                $mime = 'image/jpeg';
                break;
            case 'gif':
                $func = 'imagegif';
                $filePath = $storePath .'/'. $imageName;
                $ext = '.gif';
                $mime = 'image/gif';
                break;
            default:
                $func = 'imagejpeg';
                $filePath = $storePath .'/'. $imageName;
                $ext = '.jpg';
                $mime = 'image/jpeg';
        }

        if (!empty($graphData) && is_array($graphData))
        {
            foreach ($graphData as $k => $v)
            {
                if (empty($v['type']))
                {
                    $_logs['$v'] = $v;
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' label no type '.json_encode($_logs));
                    continue;
                }

                $_logs['$graphData$v'] = $v;

                //没有类型, 按照字符串处理
                if (empty($v['type']))
                {
                    continue;
                }
                elseif ($v['type'] == 'rect')
                {
                    //检查最小坐标点,最大坐标点
                    $left_top_point = [];
                    $right_bottom_point = [];
                    foreach ($v['points'] as $k_ => $point_pair)
                    {
                        if (!isset($left_top_point['x']) || $point_pair['x'] < $left_top_point['x'])
                        {
                            $left_top_point['x'] = $point_pair['x'];
                        }
                        if (!isset($left_top_point['y']) || $point_pair['y'] < $left_top_point['y'])
                        {
                            $left_top_point['y'] = $point_pair['y'];
                        }

                        if (!isset($right_bottom_point['x']) || $point_pair['x'] > $right_bottom_point['x'])
                        {
                            $right_bottom_point['x'] = $point_pair['x'];
                        }
                        if (!isset($right_bottom_point['y']) || $point_pair['y'] > $right_bottom_point['y'])
                        {
                            $right_bottom_point['y'] = $point_pair['y'];
                        }
                    }
                    if ($left_top_point['x'] < 0)
                    {
                        $left_top_point['x'] = 0;
                    }

                    if ($left_top_point['y'] < 0)
                    {
                        $left_top_point['y'] = 0;
                    }

                    if ($right_bottom_point['x'] < 0)
                    {
                        $right_bottom_point['x'] = 0;
                    }

                    if ($right_bottom_point['y'] < 0)
                    {
                        $right_bottom_point['y'] = 0;
                    }

                    $width = abs($right_bottom_point['x'] - $left_top_point['x']) * $imageWidth;
                    $height = abs($right_bottom_point['y'] - $left_top_point['y']) * $imageHeight;
                    // 剪裁
                    $croped = imagecreatetruecolor($width, $height);
                    imagecopy($croped, $imageResource, 0, 0, $left_top_point['x'] * $imageWidth, $left_top_point['y'] * $imageHeight,$width, $height);
                    //保存
                    $k_complemented = StringHelper::number_complement($k+1,4,0);
                    if (!file_exists(dirname($filePath.'_'.$k_complemented.$ext))) mkdir(dirname($filePath.'_'.$k_complemented.$ext), 0777, true);
                    $isCom = $func($croped, $filePath.'_'.$k_complemented.$ext);
                    imagedestroy($croped);
                    // 调整尺寸
                    if(isset($config['width']) && $isCom){
                        $imageStrings = ImageHelper::image_get_content($filePath.'_'.$k_complemented.$ext);
                        $src_im = ImageHelper::imagecreatefromstring($imageStrings);
                        $dst_im = imagecreatetruecolor($config['width'], $config['height']);
                        imagecopyresized($dst_im, $src_im, 0, 0, 0, 0, $config['width'], $config['height'], $width, $height);
                        $func($dst_im, $filePath.'_'.$k_complemented.$ext);
                        imagedestroy($src_im);
                        imagedestroy($dst_im);
                    }
                }
                //未知类型, 当做字符串处理
                else
                {

                }

            }
        }
        return true;
    }
    
    /*
     * mask图 快手
     * @author shuaiqun
     */
    public static function image_mask_from_string_for_ks($imageName, $imageString, $graphData, $storePath = null, $outputType = 'png', $config = array())
    {
        $_logs = [];
    
        $image_info = @getimagesizefromstring($imageString);
        $_logs['$image_info'] = $image_info;
    
        if (!$image_info)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getimagesizefromstring fail '.json_encode($_logs));
            return false;
        }
    
        $imageWidth = $image_info[0];
        $imageHeight = $image_info[1];
        $usedMemory = $imageWidth * $imageHeight * 5;//内存占用公式:宽*高*5, 单位byte
        $_logs['$usedMemory'] = $usedMemory;
    
        //不支持内存使用大于1G, 或分辨率为10000*10000以上的
        if (!empty(Yii::$app->params['memory_max_size']) && $usedMemory > Yii::$app->params['memory_max_size'])
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' usedmemory >1G '.json_encode($_logs));
            return false;
        }
    
        $imageResource = ImageHelper::imagecreatefromstring($imageString);
        if(!$imageResource)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $createfunc err '.json_encode($_logs));
            return false;
        }
    
        $imageWidth = ImageSX($imageResource);
        $imageHeight = ImageSY($imageResource);
    
        $imageType = FileHelper::fileextension($imageName);
    
        $imageResource = imagecreatetruecolor($imageWidth, $imageHeight);
        if(!$imageResource)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $createfunc err '.json_encode($_logs));
            return false;
        }
    
        if (!empty($storePath) && !file_exists($storePath))
        {
            @mkdir($storePath, 0777, true);
        }
    
        switch(strtolower($outputType)){
            case 'png':
                $func = 'imagepng';
                $filePath = $storePath .'/'. $imageName . '.png';
                $mime = 'image/png';
                break;
            case 'jpg':
            case 'jpeg':
                $func = 'imagejpeg';
                $filePath = $storePath .'/'. $imageName . '.jpg';
                $mime = 'image/jpeg';
                break;
            case 'gif':
                $func = 'imagegif';
                $filePath = $storePath .'/'. $imageName . '.gif';
                $mime = 'image/gif';
                break;
            default:
                $func = 'imagejpeg';
                $filePath = $storePath .'/'. $imageName . '.jpg';
                $mime = 'image/jpeg';
        }
    
        $white_alpha = imagecolorallocatealpha($imageResource,255,255,255, 75);
        $black_alpha = imagecolorallocatealpha($imageResource,0,0,0, 75);
        $yellow_alpha = imagecolorallocatealpha($imageResource, 255, 255, 0, 75);
        $red_alpha    = imagecolorallocatealpha($imageResource, 255, 0, 0, 75);
        $blue_alpha   = imagecolorallocatealpha($imageResource, 0, 0, 255, 75);
        $green_alpha = imagecolorallocatealpha($imageResource,0,128,0, 75);
        $gray_alpha = imagecolorallocatealpha($imageResource,117,121,117, 75);
        $transparent = imagecolorallocatealpha($imageResource,255,255,255, 127);
    
        $white = imagecolorallocate($imageResource,255,255,255);
        $black = imagecolorallocate($imageResource,0,0,0);
        $yellow = imagecolorallocate($imageResource, 255, 255, 0);
        $red    = imagecolorallocate($imageResource, 255, 0, 0);
        $blue   = imagecolorallocate($imageResource, 0, 0, 255);
        $green = imagecolorallocate($imageResource,0,128,0);
        $gray = imagecolorallocate($imageResource,117,121,117);
    
    
        if(isset($config['bg'])){
            //设置背景
            $bg = imagecolorallocate($imageResource,$config['bg']['r'],$config['bg']['g'],$config['bg']['b']);
            imagefill($imageResource, 0, 0, $bg);
        }else{
            //设置背景
            imagefill($imageResource, 0, 0, $black);
        }
    
        if (!empty($graphData) && is_array($graphData))
        {
            //$_logs['$graphData'] = $graphData;
    
            //对图形按照面积大小排序
            $graphDataSort = [];
            foreach ($graphData as $k => $v)
            {
                if (isset($v['type']) && in_array($v['type'], ['polygon', 'rect', 'triangle']))
                {
//                     $sort = LabelHelper::polygon_area($v['points']);
                    if($v['label'][0] == '人脸')
                    {
                        $graphDataSort[0][] = $v;
                    }
                    elseif($v['label'][0] == '眼珠')
                    {
                        $graphDataSort[1][] = $v;
                    }
                    elseif($v['label'][0] == '嘴唇')
                    {
                        $graphDataSort[2][] = $v;
                    }
                    elseif($v['label'][0] == '遮挡物')
                    {
                        $graphDataSort[3][] = $v;
                    }
                    elseif($v['label'][0] == '手')
                    {
                        $graphDataSort[4][] = $v;
                    }
                    elseif($v['label'][0] == '背景')
                    {
                        $graphDataSort[5][] = $v;
                    }
                }
   
            }
            ksort($graphDataSort);
            $_logs['$graphDataSort'] = $graphDataSort;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' DataSort error '.json_encode($_logs));
            //$_logs['$graphDataSort'] = $graphDataSort;
//             $graphData = array_combine($graphDataSort, $graphData);
//             krsort($graphData);
            //$_logs['$graphData.1'] = $graphData;
    
            foreach ($graphDataSort as $key => $result)
            {
                //因需支持多个脸，手，眼，遮挡物，所以加一层循环
                foreach($result as $k => $v)
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
                    $color = '';
                    if (isset($v['color']))
                    {
                        if(is_array($v['color'])){
                            $color = imagecolorallocate($imageResource, $v['color']['r'], $v['color']['g'], $v['color']['b']);
                        }elseif (strpos($v['color'], '#') !== false)
                        {
                            $rgb = LabelHelper::hex_to_rgb($v['color']);
                            $color = imagecolorallocate($imageResource, $rgb['r'], $rgb['g'], $rgb['b']);
                            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' color set '.json_encode($_logs));
                        }
                        else
                        {
                            $colorName = $v['color'];
                            if (isset($$colorName))
                            {
                                $color = $$colorName;
                            }
                            else
                            {
                                $_logs['$v'] = $v;
                                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' color notset '.json_encode($_logs));
                                $color = $red;
                            }
                        }
                    }
                    else
                    {
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' default '.json_encode($_logs));
                        $color = $red;
                    }
                    $_logs['$color'] = $color;
//                     Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $color '.json_encode($_logs));
                    
                    
                    
                    //{\"type\":\"polygon\",\"points\":[{\"x\":0.14041994750656167,\"y\":0.10148731408573929},{\"x\":0.07480314960629922,\"y\":0.35345581802274717}
                    if (in_array($v['type'], ['polygon']))
                    {
                        //处理点
                        $real_points = array();
                        foreach ($v['points'] as $k_ => $point_pair)
                        {
                            if (is_array($point_pair))
                            {
                                $real_points[] = $imageWidth * $point_pair['x'];
                                $real_points[] = $imageHeight * $point_pair['y'];
                            }
                            else
                            {
                                //偶数是x坐标, 奇数是y坐标
                                if ($k_ % 2 == 0)
                                {
                                    $real_points[] = $imageWidth * $point_pair;
                                }
                                else
                                {
                                    $real_points[] = $imageHeight * $point_pair;
                                }
                            }
                        }
                    
                        if (count($real_points) < 3)
                        {
                            $_logs['$real_points'] = $real_points;
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $real_points < 3 '.json_encode($_logs));
                            continue;
                        }
                    
                        //imagepolygon($imageResource,$real_points,count($real_points)/2, $color);
                        imagefilledpolygon($imageResource,$real_points,count($real_points)/2, $color);
                    }
                }
                
            }
        }
    
        //----------------------------------------
    
        if (empty($storePath))
        {
            ob_start();
            $func($imageResource);
    
            $final_image = ob_get_contents();
    
            ob_end_clean();
    
            $return = 'data:' . $mime . ';base64,' . base64_encode($final_image);
        }
        else
        {
            $return = $func($imageResource, $filePath);
        }
        imagedestroy($imageResource);
    
        if(!$return){
            return false;
        }
        return $return;
    }
}
