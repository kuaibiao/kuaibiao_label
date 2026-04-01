<?php
namespace common\helpers;

use Yii;

/**
 * LabelHelper provides label functionality that you can use in your
 * application.
 */
class LabelHelper
{
    /**
     * 格式化矩形框的点坐标
     *
     * points
     * [
     *  ['x':'1','y':'1'],
     *  ['x':'2','y':'2'],
     *  ...
     * ]
     *
     * or
     * [
     *  0 => 1,
     *  1 => 1,
     *  2 => 2,
     *  3 => 2,
     *  ...
     * ]
     *
     *
     * @param array $points : 可能是8个点, 也可能是4个点
     * @return 返回四个顶点的坐标, 顺时针方向
     */
    public static function rect_points_format($points)
    {
        $_logs = [];
        $_logs['$points'] = $points;
    
        if (!is_array($points[0]))
        {
            $points = array_chunk($points, 2);
            foreach ($points as $k => $pointVal)
            {
                $points[$k] = array_combine(['x', 'y'], $pointVal);
            }
        }
        $_logs['$points1'] = $points;
    
        if (count($points) != 4 && count($points) != 8)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $points count not 4 or 8  '.json_encode($_logs));
            return false;
        }
    
        $left_top_point = [];
        $right_top_point = [];
        $right_bottom_point = [];
        $left_bottom_point = [];
        foreach ($points as $pointVal)
        {
            if ($pointVal['x'] < 0)
            {
                $pointVal['x'] = 0;
            }
    
            if ($pointVal['y'] < 0)
            {
                $pointVal['y'] = 0;
            }
    
            //x 坐标值最小; x相同时,取y较大的
            if (!isset($left_top_point['x']) || $pointVal['x'] < $left_top_point['x'])
            {
                $left_top_point['x'] = $pointVal['x'];
                $left_top_point['y'] = $pointVal['y'];
            }
            elseif ($pointVal['x'] == $left_top_point['x'] && $pointVal['y'] < $left_top_point['y'])
            {
                $left_top_point['x'] = $pointVal['x'];
                $left_top_point['y'] = $pointVal['y'];
            }
    
            //y 坐标值最小; y相同时,取x较小的
            if (!isset($right_top_point['y']) || $pointVal['y'] < $right_top_point['y'])
            {
                $right_top_point['x'] = $pointVal['x'];
                $right_top_point['y'] = $pointVal['y'];
            }
            elseif ($pointVal['y'] == $right_top_point['y'] && $pointVal['x'] > $right_top_point['x'])
            {
                $right_top_point['x'] = $pointVal['x'];
                $right_top_point['y'] = $pointVal['y'];
            }
    
            if (!isset($right_bottom_point['x']) || $pointVal['x'] > $right_bottom_point['x'])
            {
                $right_bottom_point['x'] = $pointVal['x'];
                $right_bottom_point['y'] = $pointVal['y'];
            }
            elseif ($pointVal['x'] == $right_bottom_point['x'] && $pointVal['y'] > $right_bottom_point['y'])
            {
                $right_bottom_point['x'] = $pointVal['x'];
                $right_bottom_point['y'] = $pointVal['y'];
            }
    
            if (!isset($left_bottom_point['y']) || $pointVal['y'] > $left_bottom_point['y'])
            {
                $left_bottom_point['x'] = $pointVal['x'];
                $left_bottom_point['y'] = $pointVal['y'];
            }
            elseif ($pointVal['y'] == $left_bottom_point['y'] && $pointVal['x'] < $left_bottom_point['x'])
            {
                $left_bottom_point['x'] = $pointVal['x'];
                $left_bottom_point['y'] = $pointVal['y'];
            }
        }
    
        $_logs['$left_top_point'] = $left_top_point;
        $_logs['$right_top_point'] = $right_top_point;
        $_logs['$right_bottom_point'] = $right_bottom_point;
        $_logs['$left_bottom_point'] = $left_bottom_point;
    
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.json_encode($_logs));
        return [$left_top_point, $right_top_point, $right_bottom_point, $left_bottom_point];
    }
    
    public static function rect_get_width_height_from_points($points)
    {
        list($left_top_point, $right_top_point, $right_bottom_point, $left_bottom_point) = self::rect_points_format($points);
    
        $width = $right_bottom_point['x'] - $left_top_point['x'];
        $height = $right_bottom_point['y'] - $left_top_point['y'];
    
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.json_encode([$width, $height]));
        return [$width, $height];
    }
    
    /**
     * 根据矩形框原点获取其余的顶点
     * @param $x 原点x坐标
     * @param $y 原点y坐标
     * @param $width 矩形框宽
     * @param $height 矩形框高
     * @return array 四个顶点坐标
     */
    public static function rect_get_points_from_origin($x,$y,$width,$height){
        return [
            [ 'x'=> $x, 'y' => $y ],
            [ 'x'=> $x + $width, 'y'=> $y ],
            ['x'=> $x + $width,'y'=> $y + $height ],
            [ 'x'=> $x,'y'=> $y + $height ]
        ];
    }
    
    public static function pointsLong($point1, $point2)
    {
        $xlong = abs($point1[0] - $point2[0]);
        $ylong = abs($point1[1] - $point2[1]);
        return sqrt(pow($xlong, 2) + pow($ylong, 2));
    }
    
    /**
     * 判断点是否在矩形框内
     * @param array $rect 矩形坐标 [[x1,y1], [x2,y2], [x3, y3], [x4, y4]]
     * @param array $point 点坐标 [x1, y1]
     * @return  boolean [<description>]
     */
    public static function pointInRect($rect, $point){
        $minPoint   = [];
        $maxPoint   = [];
        foreach($rect as $p){
            if(!$minPoint){
                $minPoint   = $p;
            }else{
                if($p[0] + $p[1] < $minPoint[0] + $minPoint[1]){
                    $minPoint   = $p;
                }
            }
    
            if(!$maxPoint){
                $maxPoint   = $p;
            }else{
                if($p[0] + $p[1] > $maxPoint[0] + $maxPoint[1]){
                    $maxPoint   = $p;
                }
            }
        }
    
        if($point[0] >= $minPoint[0] && $point[0] <= $maxPoint[0]
            && $point[1] >= $minPoint[1] && $point[1] <= $maxPoint[1]){
            return true;
        }
    
        return false;
    }
    
    public static function pointInRectXY($rect, $point){
        $minPoint   = [];
        $maxPoint   = [];
        foreach($rect as $p){
            if(!$minPoint){
                $minPoint   = $p;
            }else{
                if($p['x'] + $p['y'] < $minPoint['x'] + $minPoint['y']){
                    $minPoint   = $p;
                }
            }
    
            if(!$maxPoint){
                $maxPoint   = $p;
            }else{
                if($p['x'] + $p['y'] > $maxPoint['x'] + $maxPoint['y']){
                    $maxPoint   = $p;
                }
            }
        }
    
        if($point['x'] >= $minPoint['x'] && $point['x'] <= $maxPoint['x']
            && $point['y'] >= $minPoint['y'] && $point['y'] <= $maxPoint['y']){
            return true;
        }
    
        return false;
    }
    
    /**
     * 多边形点X坐标递增
     *      *
     * @param unknown $points
     * @return ['bol','number'] true 是递增或相等 false 非递增
     */
    public static function polygon_progressive_increase($points){
        $isIncrease = true;
        $number = [];
        $point_num = count($points);
        if ($point_num<3) {
            return $isIncrease;
        }
        $n = count($points);
        for($i = 0; $i < $n; $i++){
            if($i < ($n -1)){
                $one = $points[$i % $n];
                $twn = $points[($i+1) % $n];
                if($twn['x'] < $one['x']){
                    $isIncrease = false;
                    $number[] = $i + 2;
                }
            }
    
        }
        if(count($number)){
            $numbers = implode(',',$number);
        }else{
            $numbers = '';
        }
        return ['bol' => $isIncrease,'number' => $numbers];
    }
    
    /**
     * 计算凸多边形
     *
     * @param unknown $points
     * @return true 凸多边形 false 非凸多边形
     */
    public static function polygon_raised($points)
    {
        $point_num = count($points);
        if ($point_num<3) {
            return false;
        }
    
        //计算多边形凸凹性
        $n = count($points);
        $raised = true;
        $crossproduct_one = null;
        for($i = 0; $i < $n; $i++){
            //取三个点
            $one = $points[$i % $n];
            $two = $points[($i + 1) % $n];
            $three = $points[($i + 2) % $n];
            //获取两个向量
            if(isset($two[0]) && isset($two[1])){
                $p1 = [$two[0] - $one[0],$two[1] - $one[1]];
                $p2 = [$three[0] - $two[0],$three[1] - $two[1]];
            }elseif(isset($two['x']) && isset($two['y'])){
                $p1 = [$two['x'] - $one['x'],$two['y'] - $one['y']];
                $p2 = [$three['x'] - $one['x'],$three['y'] - $one['y']];
            }else{
                break;
            }
            //计算叉积
            $crossproduct = $p1[0] * $p2[1] - $p2[0] * $p1[1];
            if($crossproduct_one === null){
                $crossproduct_one = $crossproduct;
            }else{
                $crossproduct_other = $crossproduct_one * $crossproduct;
                if($crossproduct_other < 0){
                    $raised = false;
                    break;
                }
            }
        }
    
        return $raised;
    }
    /**
     * 计算多边形的面积
     *
     * @param unknown $points
     */
    public static function polygon_area($points)
    {
        $point_num = count($points);
        if ($point_num<3)
        {
            return 0;
        }
        $area = $points[0]['y'] * ($points[$point_num-1]['x'] - $points[1]['x']);
        for($i = 1; $i < $point_num; ++$i)
            $area += $points[$i]['y'] * ($points[$i-1]['x'] - $points[($i+1)%$point_num]['x']);
        return abs($area/2.0);
    }
    
    /**
     * 计算图形包围矩形框的左上顶点及宽和高
     *
     * @param unknown $points
     */
    public static function encircle_rect($points)
    {
        $left_top_point = [];
        $right_bottom_point = [];
        foreach($points as $pointVal){
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
        }
    
        $width = abs($right_bottom_point['x'] - $left_top_point['x']);
        $height = abs($right_bottom_point['y'] - $left_top_point['y']);
    
        return [$left_top_point['x'],$left_top_point['y'],$width,$height];
    }
    
    /**
     * 三次样条差值
     *
     * input
     * "[
     {
     "x": 157.08032128514054,
     "y": 214
     },
     {
     "x": 169.08032128514054,
     "y": 310
     },
     *
     *
     *output
     *[
     {
     "x": 157.08032128514054,
     "y": 214
     },
     {
     "x": 156.71341018303787,
     "y": 217.4987663512409
     },
     *
     * @param array $points
     * @return array
     */
    public static function spline($points = [])
    {
        //去除重合的点
        $points = array_unique($points, SORT_REGULAR);
        //自然样条插值
        $PointXY = array_slice($points,0);
        if (!is_array($PointXY) || count($PointXY) < 2)
        {
            return [];
        }
    
        $deltaTT = 3;//递增量
        $lngXiFen = 2000;
        //lngP,lngT,BX3,BX4,BY3, BY4, CX, CY, TT,
        $dblTemp = $dblA = $dblB = $dblC = $dblDX = $dblDY = $dblPX = $dblPY = $dblQX = $dblQY = $U1 = $V1 = [];
        //$lngPointCount; //要描绘的点的个数
        $lngPointCount = count($PointXY) - 1;
        $lngPointCount = $lngPointCount > 0 ? $lngPointCount : 0;
        $dblTemp = array_fill(0,count($PointXY),0);
        $dblA = array_fill(0,count($PointXY),0);
        $dblB = array_fill(0,count($PointXY),0);
        $dblC = array_fill(0,count($PointXY),0);
        $dblDX = array_fill(0,count($PointXY),0);
        $dblDY = array_fill(0,count($PointXY),0);
        $dblPX = array_fill(0,count($PointXY),0);
        $dblPY = array_fill(0,count($PointXY),0);
        $dblQX = array_fill(0,count($PointXY),0);
        $dblQY = array_fill(0,count($PointXY),0);
    
        for($lngP = 1; $lngP <= $lngPointCount; $lngP++){
            $dblTemp[$lngP] = sqrt(pow(($PointXY[$lngP]['x'] - $PointXY[$lngP - 1]['x']),2) + pow(($PointXY[$lngP]['y'] - $PointXY[$lngP - 1]['y']),2));
        }
    
        $lngP = $lngP - 1;
        $dblA[0] = 2;
        $dblC[0] = 1;
        $dblDX[0] = $dblTemp[1] > 0 ? (3 * ($PointXY[1]['x'] - $PointXY[0]['x']) / $dblTemp[1]) : 0;
        $dblDY[0] = $dblTemp[1] > 0 ? (3 * ($PointXY[1]['y'] - $PointXY[0]['y']) / $dblTemp[1]) : 0;
        $dblA[$lngP] = 2;
        $dblB[$lngP] = 1;
        $dblDX[$lngP] = 3 * ($PointXY[$lngP]['x'] - $PointXY[$lngP - 1]['x']) / $dblTemp[$lngP];
        $dblDY[$lngP] = 3 * ($PointXY[$lngP]['y'] - $PointXY[$lngP - 1]['y']) / $dblTemp[$lngP];
    
        for($lngP = 1; $lngP <= $lngPointCount -1; $lngP++){
            $dblA[$lngP] = 2 * ($dblTemp[$lngP] + $dblTemp[$lngP + 1]);
            $dblB[$lngP] = $dblTemp[$lngP + 1];
            $dblC[$lngP] = $dblTemp[$lngP];
            $dblDX[$lngP] = 3 * ($dblTemp[$lngP] * ($PointXY[$lngP + 1]['x'] - $PointXY[$lngP]['x']) / $dblTemp[$lngP + 1] + $dblTemp[$lngP + 1] * ($PointXY[$lngP]['x'] - $PointXY[$lngP - 1]['x']) / $dblTemp[$lngP]);
            $dblDY[$lngP] = 3 * ($dblTemp[$lngP] * ($PointXY[$lngP + 1]['y'] - $PointXY[$lngP]['y']) / $dblTemp[$lngP + 1] + $dblTemp[$lngP + 1] * ($PointXY[$lngP]['y'] - $PointXY[$lngP - 1]['y']) / $dblTemp[$lngP]);
        }
    
        $dblC[0] = $dblC[0] / $dblA[0];
        for($lngP = 1; $lngP <= $lngPointCount - 1; $lngP++){
            $dblA[$lngP] = $dblA[$lngP] - $dblB[$lngP] * $dblC[$lngP - 1];
            $dblC[$lngP] = $dblC[$lngP] / $dblA[$lngP];
        }
    
        $dblA[$lngPointCount] = $dblA[$lngPointCount] - $dblB[$lngPointCount] * $dblC[$lngP - 1];
        $dblQX[0] = $dblDX[0] / $dblA[0];
        $dblQY[0] = $dblDY[0] / $dblA[0];
        for($lngP = 1; $lngP <= $lngPointCount; $lngP++){
            $dblQX[$lngP] = ($dblDX[$lngP] - $dblB[$lngP] * $dblQX[$lngP - 1]) / $dblA[$lngP];
            $dblQY[$lngP] = ($dblDY[$lngP] - $dblB[$lngP] * $dblQY[$lngP - 1]) / $dblA[$lngP];
        }
    
        $dblPX[$lngPointCount] = $dblQX[$lngPointCount];
        $dblPY[$lngPointCount] = $dblQY[$lngPointCount];
    
        for($lngP = $lngPointCount - 1; $lngP >= 0; $lngP--){
            $dblPX[$lngP] = $dblQX[$lngP] - $dblC[$lngP] * $dblPX[$lngP + 1];
            $dblPY[$lngP] = $dblQY[$lngP] - $dblC[$lngP] * $dblPY[$lngP + 1];
        }
    
        $lngT = 0;
        for($lngP = 0; $lngP <= $lngPointCount - 1; $lngP++){
            $BX3 = (3 * ($PointXY[$lngP + 1]['x'] - $PointXY[$lngP]['x']) / $dblTemp[$lngP + 1] - 2 * $dblPX[$lngP] - $dblPX[$lngP + 1]) / $dblTemp[$lngP + 1];
            $BX4 = ((2 * ($PointXY[$lngP]['x'] - $PointXY[$lngP + 1]['x']) / $dblTemp[$lngP + 1] + $dblPX[$lngP] + $dblPX[$lngP + 1]) / $dblTemp[$lngP + 1]) / $dblTemp[$lngP + 1];
            $BY3 = (3 * ($PointXY[$lngP + 1]['y'] - $PointXY[$lngP]['y']) / $dblTemp[$lngP + 1] - 2 * $dblPY[$lngP] - $dblPY[$lngP + 1]) / $dblTemp[$lngP + 1];
            $BY4 = ((2 * ($PointXY[$lngP]['y'] - $PointXY[$lngP + 1]['y']) / $dblTemp[$lngP + 1] + $dblPY[$lngP] + $dblPY[$lngP + 1]) / $dblTemp[$lngP + 1]) / $dblTemp[$lngP + 1];
            $TT = 0;
    
            while($TT <= $dblTemp[$lngP + 1]){
                $CX = $PointXY[$lngP]['x'] + ($dblPX[$lngP] + ($BX3 + $BX4 * $TT) * $TT) * $TT;
                $CY = $PointXY[$lngP]['y'] + ($dblPY[$lngP] + ($BY3 + $BY4 * $TT) * $TT) * $TT;
                $U1[$lngT] = $CX;
                $V1[$lngT] = $CY;
                $lngT = $lngT + 1;
                if($lngT > $lngXiFen){
                    $lngXiFen = $lngXiFen * 4;
                }
                $TT = $TT + $deltaTT;
            }
    
            $U1[$lngT] = $PointXY[$lngP + 1]['x'];
            $V1[$lngT] = $PointXY[$lngP + 1]['y'];
            $lngT = $lngT + 1;
        }
    
        $result = [];
        foreach ($U1 as $key=>$val){
            array_push($result,[
                'x'=>$val,
                'y'=>$V1[$key]
            ]);
        }
        return $result;
    }
    
    public static function to_rgb($color)
    {
        if (strpos($color, '#') !== false)
        {
            $rgb = self::hex_to_rgb($color);
            //$color = imagecolorallocate($imgRes, $rgb['r'], $rgb['g'], $rgb['b']);
        }
        elseif (strpos($color, 'rgba') !== false)
        {
            //rgba(186,23,20, 0.8)
            $rgb = self::rgba_to_rgb($color);
            //$color = imagecolorallocate($imgRes, $rgb[0], $rgb[1], $rgb[2]);
        }
        else
        {
            $rgb = false;
        }
    
        return $rgb;
    }
    
    public static function rgba_to_rgb($color)
    {
        //rgba(186,23,20, 0.8)
        $rgb = explode(',', trim($color, ')rgba('));
    
        return ['r' => $rgb[0], 'g' => $rgb[1], 'b' => $rgb[2]];
    }
    
    /**
     * RGB转 十六进制
     * @param $rgb RGB颜色的字符串 如：rgb(255,255,255);
     * @return string 十六进制颜色值 如：#FFFFFF
     */
    public static function rgb_to_hex($rgb){
        $regexp = '/^rgb\(([0-9]{0,3})\,\s*([0-9]{0,3})\,\s*([0-9]{0,3})\)/';
        $re = preg_match($regexp, $rgb, $match);
        $re = array_shift($match);
        $hexColor = "#";
        $hex = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F');
        for ($i = 0; $i < 3; $i++) {
            $r = null;
            $c = $match[$i];
            $hexAr = array();
            while ($c > 16) {
                $r = $c % 16;
                $c = ($c / 16) >> 0;
                array_push($hexAr, $hex[$r]);
            }
            array_push($hexAr, $hex[$c]);
            $ret = array_reverse($hexAr);
            $item = implode('', $ret);
            $item = str_pad($item, 2, '0', STR_PAD_LEFT);
            $hexColor .= $item;
        }
        return $hexColor;
    }
    /**
     * 十六进制 转 RGB
     * #ffffff 转rgb
     */
    public static function hex_to_rgb($hexColor) {
        $color = str_replace('#', '', $hexColor);
        if (strlen($color) > 3) {
            $rgb = array(
                'r' => hexdec(substr($color, 0, 2)),
                'g' => hexdec(substr($color, 2, 2)),
                'b' => hexdec(substr($color, 4, 2))
            );
        } else {
            $color = $hexColor;
            $r = substr($color, 0, 1) . substr($color, 0, 1);
            $g = substr($color, 1, 1) . substr($color, 1, 1);
            $b = substr($color, 2, 1) . substr($color, 2, 1);
            $rgb = array(
                'r' => hexdec($r),
                'g' => hexdec($g),
                'b' => hexdec($b)
            );
        }
        return $rgb;
    }
    
    public static function audio_waveform_wav($file,$f=0,$w=0)
    {
        if(!is_file($file))return 0;
        $fp=fopen($file,'r');
        $raw=fread($fp,36);//对应36个字节的文件头，包含该文件的一些信息
        $str="";
        $header=unpack('A4Riff/VSize/A4Wav/A4Head/VHeadSize/vPCM/vChannels/VSampleRate/VByteRate/vBlockAlign/vSampleBits', $raw);
        //print_r($header);//查看文件头
        foreach($header as $k=>$v)
            $str.=$k.":".$v." ";
        fseek($fp,36+$header['HeadSize']-16);
        $raw=fread($fp,8);
        $data=unpack('A4Data/VDataSize',$raw);
         
        foreach($data as $k=>$v)
            $str.=$k.":".$v." ";
        $b=$header['SampleBits'];//样本数据位数
        $c=$header['Channels'];//声道数
        $l=$b*$c/8;//需要用16比特的原因就在这里，16比特代表采集位数是2个字节，除于8
        $s=$data['DataSize']/$l;//文件数据长度/（样本采集位数*采集频率）=歌曲时间吧
        $r=$header['SampleRate'];//采集频率
        if($f) $h=pow(2,$b)/$f;//设定波的高度
        else {$h=200;$f=pow(2,$b-1)/$h;}
        if($w==0)$w=round($r/5);//后面的5是时间，1000表示按秒为单位
         
         
        //header("Content-type:image/png");//查看弹出数据时需要把画图的部分隐藏掉哦
    
        $im=@imagecreate($s/$w,$h*$c*2);
    
        //$background_color = imagecolorallocate($im, 255, 255, 255);
        $text_color = imagecolorallocate($im, 233, 14, 91);
        $x=0;$y=array();$yn=array();
        for($i=0;$i<$c;$i++)$y[$i]=$h*$i+$h;
        $n=$l*$w;
    
    
    
        while(1)
        {
            if($s==0) break;
            if($s<$n)$n=$s;
            $samples=fread($fp,5*$n);//后面的5是时间，1000表示按秒为单位
             
            if($samples==FALSE)break;
            $packed=unpack('s*',$samples);
    
            foreach($packed as $k=>$v)
            {
                $cnt=($k-1)%($w*$l);
                if($cnt>$c-1)continue;
                $yn[$cnt]=$h*$cnt+$h-$v/$f;
                imageline($im,$x,$y[$cnt],$x+1,$yn[$cnt],$text_color);
    
                $y[$cnt]=$yn[$cnt];
                $x++;
                 
            }
            $s-=$n;
        }
    
         
    
        //imagepng($im);
        //imagedestroy($im);
    
    }
    
    /**
     * 判断一个坐标是否在一个多边形内（由多个坐标围成的）
     * 基本思想是利用射线法，计算射线与多边形各边的交点，如果是偶数，则点在多边形外，否则
     * 在多边形内。还会考虑一些特殊情况，如点在多边形顶点上，点在多边形边上等特殊情况。
     * @param $point 指定点坐标
     * @param $poly 多边形坐标 顺时针方向
     */
    function pointInPolygon($poly, $point) {
        $N = count($poly);
        $boundOrVertex = true; //如果点位于多边形的顶点或边上，也算做点在多边形内，直接返回true
        $intersectCount = 0;//cross points count of x
        $precision = 2e-10; //浮点类型计算时候与0比较时候的容差
        $p1 = 0;//neighbour bound vertices
        $p2 = 0;
        $p = $point; //测试点
    
        $p1 = $poly[0];//left vertex
        for ($i = 1; $i <= $N; ++$i) {//check all rays
            // dump($p1);
            if ($p['lng'] == $p1['lng'] && $p['lat'] == $p1['lat']) {
                return $boundOrVertex;//p is an vertex
            }
    
            $p2 = $poly[$i % $N];//right vertex
            if ($p['lat'] < min($p1['lat'], $p2['lat']) || $p['lat'] > max($p1['lat'], $p2['lat'])) {//ray is outside of our interests
                $p1 = $p2;
                continue;//next ray left point
            }
    
            if ($p['lat'] > min($p1['lat'], $p2['lat']) && $p['lat'] < max($p1['lat'], $p2['lat'])) {//ray is crossing over by the algorithm (common part of)
                if($p['lng'] <= max($p1['lng'], $p2['lng'])){//x is before of ray
                    if ($p1['lat'] == $p2['lat'] && $p['lng'] >= min($p1['lng'], $p2['lng'])) {//overlies on a horizontal ray
                        return $boundOrVertex;
                    }
    
                    if ($p1['lng'] == $p2['lng']) {//ray is vertical
                        if ($p1['lng'] == $p['lng']) {//overlies on a vertical ray
                            return $boundOrVertex;
                        } else {//before ray
                            ++$intersectCount;
                        }
                    } else {//cross point on the left side
                        $xinters = ($p['lat'] - $p1['lat']) * ($p2['lng'] - $p1['lng']) / ($p2['lat'] - $p1['lat']) + $p1['lng'];//cross point of lng
                        if (abs($p['lng'] - $xinters) < $precision) {//overlies on a ray
                            return $boundOrVertex;
                        }
    
                        if ($p['lng'] < $xinters) {//before ray
                            ++$intersectCount;
                        }
                    }
                }
            } else {//special case when ray is crossing through the vertex
                if ($p['lat'] == $p2['lat'] && $p['lng'] <= $p2['lng']) {//p crossing over p2
                    $p3 = $poly[($i+1) % $N]; //next vertex
                    if ($p['lat'] >= min($p1['lat'], $p3['lat']) && $p['lat'] <= max($p1['lat'], $p3['lat'])) { //p.lat lies between p1.lat & p3.lat
                        ++$intersectCount;
                    } else {
                        $intersectCount += 2;
                    }
                }
            }
            $p1 = $p2;//next ray left point
        }
    
        if ($intersectCount % 2 == 0) {//偶数在多边形外
            return false;
        } else { //奇数在多边形内
            return true;
        }
    }
    
    /**
     * 去除掉pcd文件的头信息
     *
     * @param string $file_contents
     * @param string $trimRN 是否去除掉\r\n
     */
    public static function threed_file_trim_header($file_contents = '', $trimRN = false)
    {
        $file_contents = str_replace("\r\n", "\n", $file_contents);
        $lineArr = explode("\n", $file_contents);
    
        $contents = '';
        foreach ($lineArr as $lineStr)
        {
            if (preg_match('/^[\d\-\s]/', $lineStr))
            {
                $contents .= $lineStr . (!$trimRN ? "\n" : '');
            }
        }
    
        return $contents;
    }
}