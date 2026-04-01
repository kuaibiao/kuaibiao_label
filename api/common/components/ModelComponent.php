<?php
/**
 * 公共模型类
 * 
 */

namespace common\components;

use Yii;
use yii\db\ActiveRecord;
use yii\base\InvalidConfigException;

class ModelComponent extends ActiveRecord
{
    /**
     * 生成分表后缀
     * 
     */
    public static function generateSuffix()
    {
        return date('ym');
    }
    
    /**
     * 获取原始表名
     * 
     */
    public static function getOriginalTable()
    {
        //--------------------------
        
        //获取原始表名
        $oriTableName = static::tableName();
        if (strrpos($oriTableName, '_'))
        {
            $suffixOld = substr(strrchr($oriTableName, '_'), 1);
            if ($suffixOld && is_numeric($suffixOld))
            {
                $oriTableName = substr($oriTableName, 0, strrpos($oriTableName, '_'));
                $_logs['$tableNameOri'] = $oriTableName;
                Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' suffix exist '.json_encode($_logs));
            }
        }
        $_logs['$oriTableName'] = $oriTableName;
        
        return $oriTableName;
    }
    
    public static function setTable($suffix)
    {
        $_logs = ['$suffix' => $suffix];
    
        //--------------------------
    
        $oriTableName = self::getOriginalTable();
        $_logs['$oriTableName'] = $oriTableName;
    
        $newTableName = $oriTableName. '_' . $suffix;
        $_logs['$newTableName'] = $newTableName;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' new $tableName '.json_encode($_logs));
    
        //--------------------------
    
        $tableSchema = static::getDb()->getSchema()->getTableSchema($newTableName);
    
        //表不存在, 则初始化表结构
        if (!$tableSchema)
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $tableSchema not exist '.json_encode($_logs));
    
            $oriTableSchema = static::getDb()->getSchema()->getTableSchema($oriTableName);
            if (!$oriTableSchema)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' ori table not exist '.json_encode($_logs));
                throw new InvalidConfigException('The table does not exist: ' . $oriTableSchema);
            }
    
            $sql = sprintf("CREATE TABLE IF NOT EXISTS %s (LIKE %s);", $newTableName, $oriTableName);
            $_logs['$sql'] = $sql;
    
            static::getDb()->createCommand($sql)->execute();
    
            //刷新表结构缓存
            static::getDb()->getSchema()->refreshTableSchema($newTableName);
    
            Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' table init succ '.json_encode($_logs));
        }
        //--------------------------
    
        //拼接新表名
        static::$tableName = $newTableName;
        $_logs['$tableNameNew'] = static::$tableName;
        Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
    
        return static::$tableName;
    }
    
    /**
     * 获取所有的表
     */
    public static function getAllSuffixes()
    {
        $tableName = self::getOriginalTable();
        $sql = sprintf("SHOW TABLES LIKE '%s';", $tableName.'%');
        $_logs['$sql'] = $sql;
    
        $tablenames = static::getDb()->createCommand($sql)->queryColumn();
        $_logs['$tablenames'] = $tablenames;
    
        $suffixes = [];
        if ($tablenames && is_array($tablenames))
        {
            foreach ($tablenames as $tablename)
            {
                if (preg_match('/^'.$tableName.'_(\d+)$/', $tablename, $matches))
                {
                    if ($matches && $matches[1])
                    {
                        $suffixes[] = $matches[1];
                    }
                }
            }
        }
        $_logs['$suffixes'] = $suffixes;
        Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
    
        return $suffixes;
    }
    
}