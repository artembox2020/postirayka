<?php

namespace backend\models;

use frontend\services\custom\Debugger;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use common\models\User;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * Create user form.
 */
class UserForm extends Model
{
    const ZERO = 0;

    public $id;
    public $username;
    public $email;
    public $password;
    public $status;
    public $roles;
    public $company_id;
    public $other;
    public $created_at;
    public $is_deleted;

    private $model;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            // ['username', 'trim'],
            ['username', 'required'],
            // ['username', 'match', 'pattern' => '#^[\w_-]+$#i'],
            // ['username', 'unique',
            //     'targetClass' => User::className(),
            //     'filter' => function ($query) {
            //         if (!$this->getModel()->isNewRecord) {
            //             $query->andWhere(['not', ['id' => $this->getModel()->id]]);
            //         }
            //     }
            // ],
            ['username', 'string', 'min' => 3, 'max' => 255],

            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'unique',
                'targetClass' => User::className(),
                'filter' => function ($query) {
                    if (!$this->getModel()->isNewRecord) {
                        $query->andWhere(['not', ['id' => $this->getModel()->id]]);
                    }
                }
            ],
            ['email', 'string', 'max' => 255],

            ['password', 'required', 'on' => 'create'],
            ['password', 'string', 'min' => 6, 'max' => 32],

            ['status', 'integer'],
            ['status', 'in', 'range' => array_keys(User::statuses())],
            ['roles', 'each',
                'rule' => ['in', 'range' => ArrayHelper::getColumn(Yii::$app->authManager->getRoles(), 'name')],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'username' => Yii::t('backend', 'Username'),
            'position' => Yii::t('backend', 'Position'),
            'email' => Yii::t('backend', 'Email'),
            'password' => Yii::t('backend', 'Password'),
            'status' => Yii::t('backend', 'Status'),
            'roles' => Yii::t('backend','Roles')
        ];
    }

    /**
     * @inheritdoc
     */
    public function setModel($model)
    {
        $this->username = $model->username;
        $this->email = $model->email;
        $this->company_id = $model->company_id;
        $this->status = $model->status;
        $this->model = $model;
        $this->roles = ArrayHelper::getColumn(Yii::$app->authManager->getRolesByUser($model->getId()), 'name');
        $this->created_at = $model->created_at;

        return $this->model;
    }

    /**
     * @inheritdoc
     */
    public function getModel()
    {
        if (!$this->model) {
            $this->model = new User();
        }

        return $this->model;
    }

    /**
     * @return bool|void
     * @throws \Exception
     */
    public function save()
    {
        if ($this->validate()) {
            $model = $this->getModel();
            $isNewRecord = $model->getIsNewRecord();

//            Debugger::dd($this->username);
            $model->username = $this->username;
//            Debugger::dd($model->username);
            $model->email = $this->email;
            $model->status = $this->status;
            $model->company_id =$this->company_id;
            $model->other = $this->other;
            $model->is_deleted = self::ZERO;
            $model->deleted_at = self::ZERO;
            if ($this->password) {
                $model->setPassword($this->password);
            }
            $model->generateAuthKey();
            if ($model->save() && $isNewRecord) {
                $model->afterSignup();
            }
            $model->save(false);
            $auth = Yii::$app->authManager;
            $auth->revokeAll($model->getId());

            if ($this->roles && is_array($this->roles)) {
                foreach ($this->roles as $role) {
                    $auth->assign($auth->getRole($role), $model->getId());
                }
            }

            return !$model->hasErrors();
        }

        return;
    }
}
