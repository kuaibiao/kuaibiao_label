<?php
namespace common\components;

use Yii;
use yii\base\Component;
use Exception;

/**
 * 加密解密类
 * 生成 Token
 * 
 */
class TokenHandler extends Component
{
	protected $des_tag = null;
	protected $des_key = null;
	protected $time = null;
	protected $timeout = 7200;//token有效期
	protected $params = null;
	protected $connect_tag = '___';
	protected $token = null;
	
	public function __construct($tokenConfig = null)
	{
	    $_logs = ['$tokenConfig' => $tokenConfig];
	    
		$paramTokenConfig = Yii::$app->params['token'];
		$this->des_key = empty($tokenConfig['key']) ? $paramTokenConfig['key'] : $tokenConfig['key'];
		$this->des_tag = empty($tokenConfig['tag']) ? $paramTokenConfig['tag'] : $tokenConfig['tag'];
		if (!empty($tokenConfig['timeout']) && $tokenConfig['timeout'] > 0)
		{
		    $this->setTimeout($tokenConfig['timeout']);
		}
		
		$_logs['this.des_key'] = $this->des_key;
		$_logs['this.des_tag'] = $this->des_tag;
		$_logs['this.timeout'] = $this->timeout;
		Yii::info(__CLASS__.' '.__FUNCTION__.' token object constructed '.json_encode($_logs));
	}
	
	/**
	 * 生产token安全码
	 * @return string
	 */
	public function make()
	{
	    $_logs = [];
	    
		if (!$this->des_key || !$this->des_tag)
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' app_key or app_secret empty ');
			return array('code' => 'token_make_param_fail', 'message' => 'key or tag 为空', 'data' => '');
		}
		
		$dataArr = array(
				$this->des_tag,
				time(),
				$this->timeout,
				count($this->params) > 0 ? serialize($this->params) : ''
				
		);
		$dataStr = implode($this->connect_tag, $dataArr);
		$_logs['this.connect_tag'] = $this->connect_tag;
		$_logs['$dataArr'] = $dataArr;
		$_logs['$dataStr'] = $dataStr;
		try {
		    $token = Yii::$app->security->encryptByKey($dataStr, $this->des_key);
		    $_logs['$token'] = $token;
		}
		catch(Exception $e)
		{
		    $_logs['$e.file'] = $e->getFile();
		    $_logs['$e.line'] = $e->getLine();
		    $_logs['$e.code'] = $e->getCode();
		    $_logs['$e.message'] = $e->getMessage();
		    Yii::error(__CLASS__.' '.__FUNCTION__.' des_encode fail '.json_encode($_logs));
		    
		    return array('code' => 'token_make_fail', 'message' => '生成token失败', 'data' => '');
		}
		
		if (!$token)
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' des_encode fail '.json_encode($_logs));
			return array('code' => 'token_make_fail', 'message' => '生成token失败', 'data' => '');
		}
		
		$this->token = str_replace(array('/','+','='), array('_a','_b','_c'), $token);
		$_logs['this.token'] = $this->token;
		Yii::info(__CLASS__.' '.__FUNCTION__.' generate token success '.json_encode($_logs));
		
		return array('code' => '', 'message' => '', 'data' => true);
	}
	
	/**
	 * 校验token正确性
	 *
	 * @param string $token
	 * @param bool $resetAttributes
	 * @return bool
	 */
	public function check($token)
	{
	    $_logs = ['$token' => $token];
	    
	    if (!$this->des_key || !$this->des_tag)
	    {
	        Yii::error(__CLASS__.' '.__FUNCTION__.' app_key or app_secret empty ');
	        return array('code' => 'token_make_param_fail', 'message' => 'key or tag 为空', 'data' => '');
	    }
	    
	    $token = str_replace(array('_a','_b','_c'), array('/','+','='), $token);
	    
	    try {
    	    $tokenStr = Yii::$app->security->decryptByKey($token, $this->des_key);
    	    $_logs['$tokenStr'] = $tokenStr;
    	    if (!$tokenStr)
    	    {
    	        Yii::error(__CLASS__.' '.__FUNCTION__.' token err '.serialize(array($token, $tokenStr)));
    	        return array('code' => 'token_check_fail', 'message' => '解析错误', 'data' => '');
    	    }
	    }
	    catch (Exception $e)
	    {
	        $_logs['$e.file'] = $e->getFile();
	        $_logs['$e.line'] = $e->getLine();
	        $_logs['$e.code'] = $e->getCode();
	        $_logs['$e.message'] = $e->getMessage();
	        Yii::error(__CLASS__.' '.__FUNCTION__.' check token fail '.json_encode($_logs));
	        
	        return array('code' => 'token_check_fail', 'message' => '解析错误', 'data' => '');
	    }
	    
		if (!strpos($tokenStr, $this->connect_tag))
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' token err '.json_encode($_logs));
			return array('code' => 'token_check_fail', 'message' => '解析错误', 'data' => '');
		}
		
		$tokenArr = explode($this->connect_tag, $tokenStr);
		$_logs['$tokenArr'] = $tokenArr;
		if (count($tokenArr) != 4)
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' token item count err '.json_encode($_logs));
			return array('code' => 'token_check_length_fail', 'message' => '解析错误', 'data' => '');
		}
		
		//还原加密数字
		$tokenArr = array(
				'tag' => $tokenArr[0],
				'time' => $tokenArr[1],
				'timeout' => $tokenArr[2],
				'params' => $tokenArr[3] ? unserialize($tokenArr[3]) : ''
		);
		$_logs['$tokenArr.new'] = $tokenArr;
		
		//判断私钥
		if ($tokenArr['tag'] != $this->des_tag)
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' token tag err '.json_encode($_logs));
			return array('code' => 'token_check_param_fail', 'message' => '解析错误', 'data' => '');
		}
		
		//判断有效期, 未设置不校验
		if ($tokenArr['timeout'] > 0 && time() > ($tokenArr['time'] + $tokenArr['timeout']))
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' token expired '.json_encode($_logs));
			return array('code' => 'token_timeout', 'message' => 'TOKEN已过期', 'data' => '');
		}
		
		//反赋值
		$this->time = $tokenArr['time'];
		$this->timeout = $tokenArr['timeout'];
		$this->params = $tokenArr['params'];
		
		Yii::info(__CLASS__.' '.__FUNCTION__.' check token success '.json_encode($_logs));
		
		return array('code' => '', 'message' => '', 'data' => true);
	}
	
	public function getTime()
	{
		return (int)$this->time;
	}
	
	public function setTimeout($str)
	{
		$this->timeout = $str;
	}
	
	public function getTimeout()
	{
		return $this->timeout;
	}
	
	public function setConnectTag($str)
	{
		$this->connect_tag = $str;
	}
	
	public function addParam($key, $val)
	{
		$this->params[$key] = $val;
	}
	
	public function delParam($key)
	{
		if (isset($this->params[$key]))
		{
			$this->params[$key] = '';
			unset($this->params[$key]);
		}
	}
	
	public function getParams()
	{
		return $this->params;
	}
	
	public function getToken()
	{
		return $this->token;
	}
}