<?php
/**
 * 项目处理类
 * 
 * 
 */

namespace common\components;

use Yii;
use yii\base\Component;
use common\models\StatResult;

class StatisticsHandler extends Component
{
	public static function circleCount($label)
	{
		$_logs = [];

		$type = StatResult::TYPE_CIRCLE_COUNT;
		
		$action = 0;
		if (!empty($label['modifiedBy']) || !empty($label['mBy']))
		{
			$action = StatResult::ACTION_EDIT;
		}
		elseif (!empty($label['createBy']) || !empty($label['cBy']))
		{
			$action = StatResult::ACTION_ADD;
		}
	}
}