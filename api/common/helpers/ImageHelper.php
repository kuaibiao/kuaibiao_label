<?php 
namespace common\helpers;

use Yii;

/**
 * ImageHelper provides additional image functionality that you can use in your
 * application.
 */
class ImageHelper
{
    public static function base64_encode_image($img_bin, $img_type)
    {
        return 'data:image/'.$img_type.';base64,'.base64_encode($img_bin);
    }
    
    public static function base64_decode_image($img_data)
    {
        //是否标准格式
        if (strpos(substr($img_data, 0, 30), ';base64,'))
        {
            //data:image/png;base64,iVBORw0KGgo
            //data:image_apng;base64,iVBORw0KGgo
            if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $img_data, $matches))
            {
                $img_type = $matches[1];
                $img_bin = base64_decode($matches[2]);
                return [$img_type, $img_bin];
            }
        }
    
        return false;
    }
    
    /**
     * 获取图片内容, 并矫正图片旋转
     * @param string $path
     * @param bool $auto_rotate
     */
    public static function image_get_content($path, $auto_rotate = true)
    {
        $_logs = ['$path' => $path, '$auto_rotate' => $auto_rotate];
    
        $imageBinary = @file_get_contents($path);
        if (empty($imageBinary))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file_get_contents null '.json_encode($_logs));
            return false;
        }
    
        //-----------------------------
        //判断是否需要旋转
        if ($auto_rotate)
        {
            if (function_exists('exif_read_data'))
            {
                $exif = @exif_read_data($path);
                $_logs['$exif'] = $exif;
                
                // exif信息头, 包含了照片的基本信息, 包括拍摄时间, 颜色, 宽高, 方向
                if(!empty($exif['Orientation']))
                {
                    $_logs['$path'] = $path;
                    $_logs['$exif'] = $exif;
                    $imageResource = @imagecreatefromstring($imageBinary);
                    if (!$imageResource)
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' imagecreatefromstring error '.json_encode($_logs));
                    }
                    else
                    {
                        switch($exif['Orientation']) {
                            case 8:$imageResource = imagerotate($imageResource, 90, 0);break;
                            case 3:$imageResource = imagerotate($imageResource, 180, 0);break;
                            case 6:$imageResource = imagerotate($imageResource, -90, 0);break;
                        }
                        //imagejpeg($image_obj, $path);
                        //$imageBinary = file_get_contents($path);
                        ob_start();
                        imagejpeg($imageResource);
                        $imageBinary = ob_get_contents();
                        ob_end_clean();
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' exif_read_data succ '.json_encode($_logs));
                    }
                }
            }
            else
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' not find exif extension '.json_encode($_logs));
            }
        }
        //-----------------------------
    
        return $imageBinary;
    }

    /**
     * 对图片转化格式
     *
     * @param string $image_binary
     * @param string $image_type
     */
    public static function image_convert($image_binary, $image_type = 'jpeg')
    {
        $_logs = ['$image_type' => $image_type];
    
        $image = new \Imagick();
        $image->readimageblob($image_binary);
        $image->setimageformat($image_type);
        $content = $image->getImageBlob();
    
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return $content;
    }

	/**
	 * notes: 获取图片旋转方向
	 * @param path 图片地址
	 * return int	0:无旋转 3:旋转180°6:旋转-90°8:旋转90°
	 */
	public static function rotate_orientation($path)
	{
		if (function_exists('exif_read_data'))
		{
			$exif = @exif_read_data($path);
			$_logs['$exif'] = $exif;

			// exif信息头, 包含了照片的基本信息, 包括拍摄时间, 颜色, 宽高, 方向
			if(!empty($exif['Orientation']))
			{
				$_logs['$path'] = $path;
				$_logs['$exif'] = $exif;
				if(in_array($exif['Orientation'],[3,6,8]))
				{
					Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' exif_read_data succ '.json_encode($_logs));
					return $exif['Orientation'];
				}
				else
				{
					Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' image not rotate '.json_encode($_logs));
				}
			}
		}
		else
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' not find exif extension ');
		}
		return 0;
	}
    /**
     * 在原有的基础上加入bmp的支持
     *
     * @param unknown $imageString
     */
    public static function imagecreatefromstring($imageString)
    {
        if (PHP_VERSION >= 7.2)
        {
            return @imagecreatefromstring($imageString);
        }
    
        $imageHeader = substr($imageString, 0, 2);
        //$_logs['$imageHeader'] = $imageHeader;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $imageHeader '.serialize($_logs));
    
        if ($imageHeader == 'BM')
        {
            return self::imagecreatefromstringbmp($imageString);
        }
        else
        {
            return @imagecreatefromstring($imageString);
        }
    }
    
    /**
     * BMP 创建函数
     * @author simon
     * @param string $filename path of bmp file
     * @example who use,who knows
     * @return resource of GD
     */
    public static function imagecreatefromstringbmp( $imageString )
    {
        $cursor = 0;
    
        $FILE = @unpack( "vfile_type/Vfile_size/Vreserved/Vbitmap_offset", substr($imageString, $cursor, 14));
        $cursor += 14;
        $_logs['$FILE'] = $FILE;
    
        if ( $FILE['file_type'] != 19778 )
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $BMP '.json_encode($_logs));
            return FALSE;
        }
    
        $BMP = @unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel' .
            '/Vcompression/Vsize_bitmap/Vhoriz_resolution' .
            '/Vvert_resolution/Vcolors_used/Vcolors_important', substr($imageString, $cursor, 40));
        $cursor += 40;
        $_logs['$BMP'] = $BMP;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $BMP '.json_encode($_logs));
    
        //此处获取的高比较准确
        list($width, $height, $type, $attr) = getimagesizefromstring($imageString);
        $BMP['width'] = $width;
        $BMP['height'] = $height;
    
        $BMP['colors'] = pow(2, $BMP['bits_per_pixel']);
        if ($BMP['size_bitmap'] == 0)
            $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
    
        $BMP['bytes_per_pixel'] = $BMP['bits_per_pixel'] / 8;
        $BMP['bytes_per_pixel2'] = ceil($BMP['bytes_per_pixel']);
        $BMP['decal'] = ($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
        $BMP['decal'] -= floor($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
        $BMP['decal'] = 4 - (4 * $BMP['decal']);
        if ($BMP['decal'] == 4)
            $BMP['decal'] = 0;
    
        $PALETTE = array();
        if ($BMP['colors'] < 16777216) {
            $PALETTE = @unpack('V' . $BMP['colors'], substr($imageString, $cursor, $BMP['colors'] * 4));
            $IMG = substr($imageString, $cursor+$BMP['colors'] * 4, $BMP['size_bitmap']);
        }
        else
        {
            $IMG = substr($imageString, $cursor, $BMP['size_bitmap']);
        }
    
        $VIDE = chr(0);
    
        $res = imagecreatetruecolor($BMP['width'], $BMP['height']);
        $P = 0;
        $Y = $BMP['height'] - 1;
        while ($Y >= 0) {
            $X = 0;
            while ($X < $BMP['width']) {
                switch ($BMP['bits_per_pixel']) {
                    case 32:
                        $COLOR = @unpack("V", substr($IMG, $P, 3) . $VIDE);
                        break;
                    case 24:
                        $COLOR = @unpack("V", substr($IMG, $P, 3) . $VIDE);
                        break;
                    case 16:
                        $COLOR = @unpack("n", substr($IMG, $P, 2));
                        $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                        break;
                    case 8:
                        $COLOR = @unpack("n", $VIDE . substr($IMG, $P, 1));
                        $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                        break;
                    case 4:
                        $COLOR = @unpack("n", $VIDE . substr($IMG, floor($P), 1));
                        if (($P * 2) % 2 == 0)
                            $COLOR[1] = ($COLOR[1] >> 4);
                        else
                            $COLOR[1] = ($COLOR[1] & 0x0F);
                        $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                        break;
                    case 1:
                        $COLOR = @unpack("n", $VIDE . substr($IMG, floor($P), 1));
                        if (($P * 8) % 8 == 0)
                            $COLOR[1] = $COLOR[1] >> 7;
                        elseif (($P * 8) % 8 == 1)
                        $COLOR[1] = ($COLOR[1] & 0x40) >> 6;
                        elseif (($P * 8) % 8 == 2)
                        $COLOR[1] = ($COLOR[1] & 0x20) >> 5;
                        elseif (($P * 8) % 8 == 3)
                        $COLOR[1] = ($COLOR[1] & 0x10) >> 4;
                        elseif (($P * 8) % 8 == 4)
                        $COLOR[1] = ($COLOR[1] & 0x8) >> 3;
                        elseif (($P * 8) % 8 == 5)
                        $COLOR[1] = ($COLOR[1] & 0x4) >> 2;
                        elseif (($P * 8) % 8 == 6)
                        $COLOR[1] = ($COLOR[1] & 0x2) >> 1;
                        elseif (($P * 8) % 8 == 7)
                        $COLOR[1] = ($COLOR[1] & 0x1);
                        $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                        break;
                    default:
                        return false;
                        break;
                }
    
                imagesetpixel($res, $X, $Y, $COLOR[1]);
                $X++;
                $P += $BMP['bytes_per_pixel'];
            }
            $Y--;
            $P+=$BMP['decal'];
        }
    
        imageflip($res, IMG_FLIP_VERTICAL);
        return $res;
    }
    
    
    /**
     * 不改版图片大小压缩图片
     * png图片不可压缩, 除非转化成jpg格式的图片
     * 
     */
    public static function image_compress($image_binary, $image_type, $newWidth = null, $newHeight = null, $isDeep = FALSE)
    {
        $_logs = ['$image_type' => $image_type];
        
        $image_type = strtolower($image_type);
        //png图片不能压缩
        if (!in_array($image_type, ['jpg', 'jpeg', 'png']))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getimagesizefromstring fail '.json_encode($_logs));
            return $image_binary;
        }
        
        //图片容量小于10kb不处理
        $size = strlen($image_binary);
        $_logs['$size'] = $size;
        if ($size < 10000)
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' <10kb '.json_encode($_logs));
            return $image_binary;
        }
        
        $image_info = @getimagesizefromstring($image_binary);
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
    
        $time0 = microtime(true);
        $_logs['$time0'] = $time0;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test0 '.json_encode($_logs));
    
        $imageResource = self::imagecreatefromstring($image_binary);
        if(!$imageResource)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $createfunc err '.json_encode(['']));
            return false;
        }
    
        $time1 = microtime(true);
        $_logs['$time1'] = $time1;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test1 '.json_encode($_logs));
         
        //$imageWidth = ImageSX($imageResource);
        //$imageHeight = ImageSY($imageResource);
    
        if ($newWidth && $newHeight)
        {
            $ratio = $imageWidth/$imageHeight;
            if ($newWidth/$newHeight > $ratio) {
                $newWidth = $newHeight*$ratio;
            } else {
                $newHeight = $newWidth/$ratio;
            }
        }
        else
        {
            $newWidth = $imageWidth;
            $newHeight = $imageHeight;
        }
    
        $time2 = microtime(true);
        $_logs['$time2'] = $time2;//1535358445.938
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test2 '.json_encode($_logs));
         
        if ($isDeep)
        {
            $newImageResource = imagecreate($newWidth, $newHeight);
            imagecopyresized($newImageResource, $imageResource, 0, 0, 0, 0, $newWidth, $newHeight, $imageWidth, $imageHeight);
        }
        else
        {
            $newImageResource = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($newImageResource, $imageResource, 0, 0, 0, 0, $newWidth, $newHeight, $imageWidth, $imageHeight);
        }
        
        $time3 = microtime(true);
        $_logs['$time3'] = $time3;//1535358446.803
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test3 '.json_encode($_logs));
    
        ob_start();
        imagejpeg($newImageResource, null, 50);
        $image_binary = ob_get_contents();
        ob_end_clean();
        
        imagedestroy($imageResource);
        imagedestroy($newImageResource);
    
        $time4 = microtime(true);
        $_logs['$time4'] = $time4;//1535358447.251
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test4 '.json_encode($_logs));
    
        $newSize = strlen($image_binary);
        $_logs['$newSize'] = $newSize;
    
        $runtime = $time4 - $time0;
        $_logs['$runtime'] = $runtime;
        
        //压缩比大于1的记日志
        $compressRate = round($newSize / $size, 2);
        $_logs['$compressRate'] = $compressRate;
        if ($compressRate > 1)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' compress rate > 1 '.json_encode($_logs));
        }
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return $image_binary;
    }
    
    /**
     * Renders the CAPTCHA image based on the code using GD library.
     * @param string $code the verification code
     * @return string image contents in PNG format.
     */
    public static function renderImageByGD($code, $width = 120, $height = 50, $backColor = 0xFFFFFF, $foreColor = 0x2040A0, $transparent = false)
    {
        $offset = -2;
        $padding = 2;
        $fontFile = Yii::getAlias('@yii/captcha/SpicyRice.ttf');
    
        $image = imagecreatetruecolor($width, $height);
    
        $backColor = imagecolorallocate(
            $image,
            (int) ($backColor % 0x1000000 / 0x10000),
            (int) ($backColor % 0x10000 / 0x100),
            $backColor % 0x100
        );
        imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, $backColor);
        imagecolordeallocate($image, $backColor);
    
        if ($transparent) {
            imagecolortransparent($image, $backColor);
        }
    
        $foreColor = imagecolorallocate(
            $image,
            (int) ($foreColor % 0x1000000 / 0x10000),
            (int) ($foreColor % 0x10000 / 0x100),
            $foreColor % 0x100
        );
    
        $length = strlen($code);
        $box = imagettfbbox(30, 0, $fontFile, $code);
        $w = $box[4] - $box[0] + $offset * ($length - 1);
        $h = $box[1] - $box[5];
        $scale = min(($width - $padding * 2) / $w, ($height - $padding * 2) / $h);
        $x = 10;
        $y = round($height * 27 / 40);
        for ($i = 0; $i < $length; ++$i) {
            $fontSize = (int) (rand(26, 32) * $scale * 0.8);
            $angle = rand(-10, 10);
            $letter = $code[$i];
            $box = imagettftext($image, $fontSize, $angle, $x, $y, $foreColor, $fontFile, $letter);
            $x = $box[2] + $offset;
        }
    
        imagecolordeallocate($image, $foreColor);
    
        ob_start();
        imagepng($image);
        imagedestroy($image);
    
        return ob_get_clean();
    }
}