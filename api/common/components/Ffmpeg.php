<?php
/**
 * ffmpeg音视频处理
 * 
 */

namespace common\components;

use Yii;
use yii\base\Component;
use common\helpers\FormatHelper;
class Ffmpeg extends Component
{
    var $ffmpeg = 'ffmpeg';
    var $source = '';
    var $startTime = null;
    var $duration = null;
    var $outputFile = null;
    var $command = '';
    
    public function setFfmpeg($ffmpeg)
    {
        $this->ffmpeg = $ffmpeg;
    }
    
    /**
     * 源文件
     * 
     * -i xx.mp3
     * -f s16le -v 16 -y -ar 16000 -ac 2 -i xxx.pcm
     * 
     * @param unknown $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }
    
    public function setStartTime($startTime = null)
    {
        $this->startTime = $startTime;
    }
    
    public function setDuration($duration = null)
    {
        $this->duration = $duration;
    }

    public function setSuffixName($suffixName = null)
    {
        $this->suffixName = $suffixName;
    }
    
    /**
     * 输出到文件
     * 
     * @param unknown $fileName
     */
    public function getOutputFile($file)
    {
        $this->outputFile = $file;
    }
    
    public function run()
    {
        ob_start();
        passthru($this->command);
        $output = ob_get_contents();
        ob_end_clean();
        
        preg_match('/ffmpeg version/', $output, $matches);
        $_logs['$matches'] = $matches;
        $_logs['$output_exit'] = $output;
        if (!$matches)
        {
            var_dump('ffmpeg not install');
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'  ffmpeg not install  '.json_encode($_logs));
            return false;
        }

        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'  output error  '.json_encode($_logs));

        return $output;
    }
    
    
    public function getCommand()
    {
        $cmd = [];
        $cmd[] = $this->ffmpeg;
        
        if ($this->startTime !== null)
        {
            $cmd[] = '-ss '.$this->startTime;
        }
        
        if ($this->duration !== null)
        {
            $cmd[] = '-t '.$this->duration;
        }
        
        $cmd[] = $this->outputFile;
        
        return implode(' ', $cmd);
    }

    public function WavCutToWav($output_dir){
        if (!file_exists($output_dir))
        {
            @mkdir($output_dir, 0777, true);
            if (!file_exists($output_dir))
            {
                var_dump('mkdir fail');
                var_dump($output_dir);
                return false;
            }
        }
        $sourceInfo = pathinfo($this->source);
        if (!$sourceInfo)
        {
            var_dump('$sourceInfo empty'.$this->source);
            return false;
        }

        $cmd = [];
        $cmd[] = $this->ffmpeg;
        $cmd[] = '-i '.escapeshellarg($this->source);
        $cmd[] = '-t '.$this->duration;
        $cmd[] = '-ss '.$this->startTime;
        $cmd[] = escapeshellarg($output_dir.'/'.$sourceInfo['filename']).$this->suffixName;

        $this->command = implode(' ', $cmd);
        //var_dump($this->command);die;
        $this->run();

        return true;
    }

    /*
     * 抽取视频关键保存成图片
     * @param string $output_dir
     * @return boolean
     */
    public function videoKeysToImages($output_dir){
        if (!file_exists($output_dir))
        {
            @mkdir($output_dir, 0777, true);
            if (!file_exists($output_dir))
            {
                var_dump('mkdir fail');
                var_dump($output_dir);
                return false;
            }
        }
        $sourceInfo = pathinfo($this->source);
        if (!$sourceInfo)
        {
            var_dump('$sourceInfo empty'.$this->source);
            return false;
        }
        $cmd = [];
        $cmd[] = $this->ffmpeg;
        $cmd[] = '-i '.escapeshellarg($this->source);
        //$cmd[] = '-vf ';
        //$cmd[] = 'select="eq(pict_type\,I)"';
        //$cmd[] = '-vsync 2';
        //$cmd[] = '-s 160x90';
        $cmd[] = '-f image2';
        $cmd[] = ' '.escapeshellarg($output_dir.'/'.$sourceInfo['basename']).'_%d.jpg';

        $this->command = implode(' ', $cmd);
        $this->run();

        return true;
    }
    /**
     * 从视频抽取图片
     * 
     * @param string $output_dir
     * @param number $second
     * @param number $frame_count
     * @return boolean|number
     */
    public function videoCutToImages($output_dir, $second = 1, $frame_count = 1)
    {
        $_logs = ['$output_dir' => $output_dir, '$second' => $second, '$frame_count' => $frame_count];
        
        if ($second < 1)
        {
            $second = 1;
        }
        
        if ($frame_count < 1)
        {
            $frame_count = 1;
        }
        
        if (!file_exists($output_dir))
        {
            @mkdir($output_dir, 0777, true);
            if (!file_exists($output_dir))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mkdir fail '.serialize($_logs));
                return false;
            }
        }

        $timelong = $this->getVideoTimeLong();
        if ($timelong === false)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getVideoTimeLong fail '.serialize($_logs));
            return false;
        }
        $_logs['$timelong'] = $timelong;

        $timelong = FormatHelper::strtomicrotime($timelong);
        if ($timelong < 1)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' timelong < 1 '.serialize($_logs));
            return false;
        }
        $_logs['$timelong.1'] = $timelong;
        $count = ceil($timelong / $second);
        $_logs['$count'] = $count;
        
        $sourceInfo = pathinfo($this->source);
        $_logs['$sourceInfo'] = $sourceInfo;
        if (!$sourceInfo)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $sourceInfo empty '.serialize($_logs));
            return false;
        }
    
        for ($i=0;$i < $count;$i++)
        {
            $cmd = [];
            $cmd[] = $this->ffmpeg;
            $cmd[] = '-i '.escapeshellarg($this->source);
            //$cmd[] = '-r '.$frame_count;//每秒抽取帧数
            $cmd[] = '-ss '.($i*$second);
            $cmd[] = '-t '.$second;
            $cmd[] = '-vframes '.$frame_count;//抽取的张数
            //$cmd[] = '-g n ';//参数为设置 gop_size，I帧间隔,每隔多少个包抽取一帧
            $cmd[] = '-q:v 2';//设置图片质量为高清
            $cmd[] = '-f image2';
            $cmd[] = ' '.escapeshellarg($output_dir.'/'.$sourceInfo['basename']).'_'.$i.'_%d.jpg';

            $this->command = implode(' ', $cmd);
            //var_dump($this->command);
            $_logs['command'] = $this->command;
    
            $this->run();
            
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' run '.serialize($_logs));
        }
    
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.serialize($_logs));
        return $count;
    }
    
    public function videoCutToImagesQuick($output_dir, $frame_count = 1)
    {
        $_logs = ['$output_dir' => $output_dir, '$frame_count' => $frame_count];
    
        if ($frame_count < 1)
        {
            $frame_count = 1;
        }
    
        if (!file_exists($output_dir))
        {
            @mkdir($output_dir, 0777, true);
            if (!file_exists($output_dir))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mkdir fail '.serialize($_logs));
                return false;
            }
        }
    
        $sourceInfo = pathinfo($this->source);
        $_logs['$sourceInfo'] = $sourceInfo;
        if (!$sourceInfo)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $sourceInfo empty '.serialize($_logs));
            return false;
        }
    
        //for ($i=0;$i < $count;$i++)
        //{
            $cmd = [];
            $cmd[] = $this->ffmpeg;
            $cmd[] = '-i '.escapeshellarg($this->source);
            $cmd[] = '-r '.$frame_count;//每秒抽取帧数
            //$cmd[] = '-ss '.($i*$second);
            //$cmd[] = '-t '.$second;
            //$cmd[] = '-vframes '.$frame_count;//抽取的张数
            //$cmd[] = '-g n ';//参数为设置 gop_size，I帧间隔,每隔多少个包抽取一帧
            $cmd[] = '-q:v 2';//设置图片质量为高清
            $cmd[] = '-f image2';
            $cmd[] = ' '.escapeshellarg($output_dir.'/'.$sourceInfo['basename']).'_%d.jpg';
    
            $this->command = implode(' ', $cmd);
            //var_dump($this->command);
            $_logs['command'] = $this->command;
            
            $this->run();
    
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' run '.serialize($_logs));
        //}
    
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.serialize($_logs));
        return true;
    }
    
    /**
     * 此方式只适用于导出格式为ts的视频
     * 不支持mp4的格式, 因截取的时间不精确
     * 
     * @param unknown $output_dir
     * @param number $segment_time
     * @return Ambigous <boolean, string>
     */
    public function videoCutToVideos($output_dir, $segment_time = 13)
    {
        $cmd = [];
        $cmd[] = $this->ffmpeg;
        $cmd[] = '-i '.escapeshellarg($this->source);
        $cmd[] = '-f segment -segment_time '.$segment_time.' ';
        //$cmd[] = '-vcodec copy -acodec copy';
        $cmd[] = ' '.escapeshellarg($output_dir.'/%d.ts');
    
        $this->command = implode(' ', $cmd);
        var_dump($this->command);
        
        return $this->run();
    }
    
    /**
     * 从视频切割为短视频
     * 采用指定时间方式, 较精准
     * 
     * @param unknown $output_dir
     * @param number $segment_time
     * @return boolean|number
     */
    public function videoCutToVideosFormatMp4($output_dir, $segment_time = 13)
    {
        $timelong = $this->getVideoTimeLong();
        $timelong = FormatHelper::strtomicrotime($timelong);
        if ($timelong < 1)
        {
            return false;
        }
        $count = ceil($timelong / $segment_time);
        
        for ($i=0;$i < $count;$i++)
        {
            $cmd = [];
            $cmd[] = $this->ffmpeg;
            $cmd[] = '-i '.escapeshellarg($this->source);
            $cmd[] = '-ss '.$i*$segment_time.' -t '.$segment_time.' ';
            $cmd[] = '-vcodec copy';
            $cmd[] = ' '.escapeshellarg($output_dir.'/'.$i.'.mp4');
            
            $this->command = implode(' ', $cmd);
            var_dump($this->command);
            
            $this->run();
        }
        
        return $count;
    }
    
    /**
     * 获得视频文件的总时长
     * 返回结果示例: 00:00:52.20
     * 
     */
    public function getVideoTimeLong()
    {
        $cmd = [];
        $cmd[] = $this->ffmpeg;
        $cmd[] = '-i '.escapeshellarg($this->source);
        $cmd[] = "2>&1";
        
        $this->command = implode(' ', $cmd);
        $_logs['command'] = $this->command;
        
        $output_str = $this->run();
        $_logs['$output_str'] = $output_str;
        if ($output_str === false)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' run fail '.serialize($_logs));
            return false;
        }

        //Duration: 00:25:45.20, bitrate: 256 kb/s
        preg_match('/Duration:\s+(\d+):(\d+):(\d+).(\d+),/', $output_str, $matches);
        $_logs['$matches'] = $matches;
        if (!$matches)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' preg_match fail '.serialize($_logs));
            return false;
        }

        $timelong = sprintf('%s:%s:%s.%s', $matches[1], $matches[2], $matches[3], $matches[4]);
        $_logs['$timelong'] = $timelong;

        $_logs['$cmd'] = $cmd;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.serialize($_logs));

        return $timelong;
    }
    
    
    
}