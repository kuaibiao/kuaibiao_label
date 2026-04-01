<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;
use common\components\aiHandler\AiHandler;
use common\models\Setting;
use common\components\ProjectHandler;
use common\models\Data;
use common\models\DataResult;
use common\models\Project;

/**
 * 
 */
class TestController extends Controller
{
    // php yii test/test
    public function actionTest()
    {
        var_dump(time());
        
    }
    
    // /usr/local/php/bin/php yii test/jsondecode
    public function actionJsondecode()
    {
        $str = '{"data":[{"type":"rect","id":"c81770a9-e6d5-4fe3-bbff-54003ca79e1b","points":[{"x":0.884668,"y":0.642632},{"x":0.90019,"y":0.642632},{"x":0.90019,"y":0.705853},{"x":0.884668,"y":0.705853}],"strokeWidth":2,"label":["ignore no","Pedestrian","\u4e25\u91cd\u906e\u632130-70%"],"code":["","\u76f4\u7acb\u884c\u8d70\u7684\u884c\u4eba",""],"category":["ignore","\u7c7b\u522b","\u906e\u6321\u9009\u62e9"],"color":"#ffff00","cBy":"10089","cTime":1539503288,"minWidth":3,"minHeight":3,"maxWidth":0,"maxHeight":0,"angle":0,"editable":true},{"type":"rect","id":"c22d84d7-093f-4db8-96be-bf94310ae777","points":[{"x":0.784086,"y":0.642757},{"x":0.809284,"y":0.642757},{"x":0.809284,"y":0.711229},{"x":0.784086,"y":0.711229}],"strokeWidth":2,"label":["ignore no","Cyclist","\u4e25\u91cd\u906e\u632130-70%"],"code":["","\u9a91\u81ea\u884c\u8f66\u3001\u6469\u6258\u8f66\u3001\u4e09\u8f6e\u8f66\u7684\u4eba",""],"category":["ignore","\u7c7b\u522b","\u906e\u6321\u9009\u62e9"],"color":"#ffff00","cBy":"10089","cTime":1539503293,"minWidth":3,"minHeight":3,"maxWidth":0,"maxHeight":0,"angle":0,"editable":true},{"type":"rect","id":"3beed794-7595-4ff7-9547-d847093fd3e5","points":[{"x":0.713672,"y":0.647294},{"x":0.728359,"y":0.647294},{"x":0.728359,"y":0.706212},{"x":0.713672,"y":0.706212}],"strokeWidth":2,"label":["ignore no","Pedestrian","\u4e25\u91cd\u906e\u632130-70%"],"code":["","\u76f4\u7acb\u884c\u8d70\u7684\u884c\u4eba",""],"category":["ignore","\u7c7b\u522b","\u906e\u6321\u9009\u62e9"],"color":"#ffff00","cBy":"10089","cTime":1539503298,"minWidth":3,"minHeight":3,"maxWidth":0,"maxHeight":0,"angle":0,"editable":true},{"type":"rect","id":"36540c3f-2861-4817-890c-5fdbce673c1d","points":[{"x":0.734654,"y":0.647294},{"x":0.748642,"y":0.647294},{"x":0.748642,"y":0.707324},{"x":0.734654,"y":0.707324}],"strokeWidth":2,"label":["ignore no","Pedestrian","\u65e0\u906e\u6321"],"code":["","\u76f4\u7acb\u884c\u8d70\u7684\u884c\u4eba",""],"category":["ignore","\u7c7b\u522b","\u906e\u6321\u9009\u62e9"],"color":"#ffff00","cBy":"10089","cTime":1539503308,"minWidth":3,"minHeight":3,"maxWidth":0,"maxHeight":0,"angle":0,"editable":true},{"type":"rect","id":"a1f30a2b-b1fc-4f82-a6a7-346591f619d4","points":[{"x":0.750741,"y":0.637289},{"x":0.762631,"y":0.637289},{"x":0.762631,"y":0.700654},{"x":0.750741,"y":0.700654}],"strokeWidth":2,"label":["ignore no","Pedestrian","\u4e25\u91cd\u906e\u632130-70%"],"code":["","\u76f4\u7acb\u884c\u8d70\u7684\u884c\u4eba",""],"category":["ignore","\u7c7b\u522b","\u906e\u6321\u9009\u62e9"],"color":"#ffff00","cBy":"10089","cTime":1539503313,"minWidth":3,"minHeight":3,"maxWidth":0,"maxHeight":0,"angle":0,"editable":true}],"is_difficult":0}';
        var_dump(Functions::json_decode_all1($str));
    }
    
    // php ./yii test/ai
    public function actionAi()
    {
        $_logs = [];
        
        /**
         * 
         *  'project_id' => '811'
    'task_id' => '1366'
    'data_id' => '53'
    'op' => 'aimodel'
    'aimodel_name' => 'image/BaiduMultiObjectDetect'
    
         * @var string $dataId
         */
        
        $projectId = 811;
        $dataId = '53';
        
        $project = Project::find()
        ->where(['id' => $projectId])
        ->andWhere(['in', 'status', [Project::STATUS_WORKING, Project::STATUS_PAUSED, Project::STATUS_STOPPED, Project::STATUS_FINISH]])
        ->asArray()->limit(1)->one();
        
        Data::setTable($project['table_suffix']);
        DataResult::setTable($project['table_suffix']);
        
        $data = Data::find()->where(['id' => $dataId])->asArray()->limit(1)->one();
        $dataResult = DataResult::find()->where(['data_id' => $dataId])->asArray()->limit(1)->one();
        
        $item = $data;
        $item['dataResult'] = $dataResult;
        
        $class = 'image/BaiduMultiObjectDetect';
        $action = 'run';
        $params = [
            'item' => $item
        ];
        $item['dataResult']['data'] = ['/dog.jpg'];
        
        //var_dump($params);
        
        $aiResult = AiHandler::run($class, $action, $params);
        var_dump($aiResult);
        
    }
    
    // /usr/local/php/bin/php yii test/t1
    public function actionT1()
    {
        
        $configs = '[{"type":"layout","column0":{"span":18,"children":[{"type":"task-file-placeholder","header":"图片文件占位符: ","tips":"","id":"b07f7e11-5844-4786-a14c-ae3f3be6dc4a","anchor":"image_url"}]},"column1":{"span":6,"children":[{"type":"tag","subType":"single","header":"请选择合适的标签：","tips":"为标注对象选择所属类型","data":[{"text":"horse","shortValue":"","color":"#ffff00","minWidth":"","minHeight":"","maxWidth":"","maxHeight":"","isRequired":0},{"text":"bird","shortValue":"","color":"#ffff00","minWidth":"","minHeight":"","maxWidth":"","maxHeight":"","isRequired":0}],"tagIsRequired":"0","tagIsUnique":0,"id":"u6up3j37i2l","deepLevel":1,"tagIsSearchAble":true,"pointPositionNoLimit":false,"pointTagShapeType":[],"tagGroupOpen":false,"tagLayoutType":"list"}]},"id":"a40010f7-f663-41be-848e-dd3c42c4103c","ratio":3,"scene":"edit"}]';
        
        $aa = ProjectHandler::fetchLabelFromTemplate($configs);
        var_dump($aa);
        
    }
    
}