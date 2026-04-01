# yii2-chinesepinyin
yii2版, 可以把汉子转为拼音

# 安装方法

1.命令安装
php composer.phar require diszz/yii2-chinesepinyin *
或
composer require diszz/yii2-chinesepinyin *



# 代码中使用

``` php
	$Pinyin = new ChinesePinyin();
        echo '带声调的汉语拼音';
        echo $Pinyin->TransformWithTone("带声调的汉语拼音");
        echo '<br/>';
        echo '无声调的汉语拼音';
        echo $Pinyin->TransformWithoutTone("无声调的汉语拼音");
        echo '<br/>';
        echo '首字母只包括汉字BuHanPinYin';   
        echo $Pinyin->TransformUcwordsOnlyChar("首字母只包括汉字BuHanPinYin");
        echo '<br/>';      
        echo '首字母和其他字符如B区32号'; 
        echo $Pinyin->TransformUcwords("首字母和其他字符如B区32号");

```


