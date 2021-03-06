<?php

namespace frontend\services\globals;

use common\models\User;
use frontend\services\custom\Debugger;
use frontend\services\globals\QueryOptimizer;
use Yii;
use yii\di\Instance;
use yii\helpers\ArrayHelper;
use \yii\jui\AutoComplete;
use yii\web\JsExpression;

/**
 * Class Entity
 * @package frontend\services\globals
 */
class Entity implements EntityInterface
{
    /** @var int  */
    const ONE = 1;

    /**
     * @param null $id
     * @param null $instance
     * @return null|Instance
     * @throws \yii\web\NotFoundHttpException
     */
    public function getUnitPertainCompany($id, $instance)
    {
        if (!$unit = $this->tryUnitPertainCompany($id, $instance)) {
         
            throw new \yii\web\NotFoundHttpException(Yii::t('common','Entity not found'));
        }
        
        return $unit;
    }

    /**
     * @param null $instance
     * @return null|array
     * @throws \yii\web\NotFoundHttpException
     */
    public function getUnitsPertainCompany($instance)
    {
        $units = $this->getUnitsQueryPertainCompany($instance)->all();
        $this->checkAccess($units);

        return $units;
    }

    /**
     * @param null $instance
     * @return yii\db\Query
     */
    public function getUnitsQueryPertainCompany($instance)
    {
        $units = $instance::find()->andWhere(['company_id' => $this->getCompanyId()]);

        return $units;
    }

    /**
     * @param $instance
     * @return mixed
     * @throws \yii\web\NotFoundHttpException
     */
    public function getFilteredStatusData($instance)
    {
        $units = $instance::find()
            ->andWhere(['status' => self::ONE, 'company_id' => $this->getCompanyId()])
            ->all();
        $this->checkAccess($units);

        return $units;
    }

    /**
     * @param $unit
     * @param bool $raiseException
     * @return mixed
     * @throws \yii\web\NotFoundHttpException
     */
    public function checkAccess($unit, $raiseException = true)
    {
        if (!$unit) {
            if ($raiseException) {
                
                throw new \yii\web\NotFoundHttpException(Yii::t('common','Entity not found'));
            } else {
                
                return false;
            }
        }

        return $unit;
    }

    /**
     * @return int
     */
    public function getCompanyId()
    {
        global $currentUser;

        if (empty($currentUser)) {
            $query = User::find()->andWhere(['id' => Yii::$app->user->id])->limit(1);
            $currentUser = $query->one();
        }

        return $currentUser->company_id;
    }

    /**
     * Attempts to get unit pertaining to company
     * In case it is not found returns bool(false)
     *
     * @param null $id
     * @param null $instance
     * @return bool|Instance
     * @throws \yii\web\NotFoundHttpException
     */
    public function tryUnitPertainCompany($id, $instance)
    {
        $unit = $instance::findOne(['id' => $id, 'company_id' => $this->getCompanyId()]);

        return $this->checkAccess($unit, false);  
    }
    
    /**
     * Attempts to get units by its ids
     * In case units not found returns bool(false)
     * 
     * @param array $unitIds
     * @param null $instance
     * @return bool|array
     * @throws \yii\web\NotFoundHttpException
     */
    public function tryUnitsPertainCompanyByIds(Array $unitIds, $instance)
    {
        $units = $instance::find()
            ->andWhere(
                [
                    'company_id' => $this->getCompanyId(),
                    'id' => $unitIds
                ])
            ->all();

        return $this->checkAccess($units, false);
    }

    /** 
     * @param int $id
     * @param Instance $instance
     * @return bool|Instance
     */
    public function tryUnit($id, $instance)
    {
        $unit = $instance::findOne(['id' => $id]);

        return $this->checkAccess($unit, false);
    }

    /**
     * @return User
     */
    public function getUser()
    {
        global $currentUser;

        if (empty($currentUser)) {
            $currentUser = User::findOne(Yii::$app->user->id);
        }

        return $currentUser;
    }

    /**
     * Gets all units query pertaining company, e.g. inactive, soft deleted
     * @param null $instance
     * @return yii\db\Query
     */
    public function getAllUnitsQueryPertainCompany($instance)
    {
        $units = $instance::find()->where(['company_id' => $this->getCompanyId()]);

        return $units;
    }
}
