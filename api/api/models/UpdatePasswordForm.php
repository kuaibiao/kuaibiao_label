<?php
namespace api\models;

use Yii;
use yii\base\Model;
use common\models\User;

/**
 * Password reset request form
 */
class UpdatePasswordForm extends Model
{
    public $newpassword;
    public $repassword;
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['newpassword', 'required'],
            ['newpassword', 'string', 'min' => 6, 'max' => 18],
            ['newpassword', 'match', 'pattern'=>User::getPasswordRegex(),'message'=> yii::t('app', 'password_format_error')],
             
            ['repassword', 'required'],
            ['repassword', 'compare', 'compareAttribute' => 'newpassword', 'operator' => '===','message'=> yii::t('app', 'passwords_not_consistent')],
        ];
    } 
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'newpassword' => '新密码',
            'repassword'=> '确认密码'
        ];
    } 
    
    /**
     *  update password.
     *
     * @return bool if password was update.
     */
    public function updatePassword()
    {   
        $id = yii::$app->user->id;
        $user = User::findIdentity($id);
        
        $user->setPassword($this->newpassword);
        
        return $user->save(false);
    }
}
