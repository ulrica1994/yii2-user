<?php

namespace nkostadinov\user\controllers;

use nkostadinov\user\helpers\Event;
use nkostadinov\user\models\Token;
use nkostadinov\user\Module;
use Yii;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;

class RegistrationController extends BaseController
{
    /** Event is triggered before signing the user. Triggered with \nkostadinov\user\events\ModelEvent. */
    const EVENT_BEFORE_SIGNUP = 'nkostadinov.user.beforeSignup';
    /** Event is triggered after signing the user. Triggered with \nkostadinov\user\events\ModelEvent. */
    const EVENT_AFTER_SIGNUP = 'nkostadinov.user.afterSignup';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                ],
            ],
        ];
    }

    public function actionConfirm($code)
    {
        $token = Token::findByCode($code, Token::TYPE_CONFIRMATION);
        if ($token->user->confirm($token)) {
            Yii::$app->session->setFlash('success', Yii::t('Your account was successfuly confirmed!', Module::I18N_CATEGORY));
        } else {
            Yii::$app->session->setFlash('warning', Yii::t('Error while confirming your account!', Module::I18N_CATEGORY));
        }
        
        return $this->render($this->module->confirmView);
    }

    public function actionSignup()
    {
        if (!$this->module->allowRegistration)
            throw new NotFoundHttpException(Yii::t(Module::I18N_CATEGORY, 'Registration disabled!'));

        $model = Yii::createObject(Yii::$app->user->registerForm);

        $event = Event::createModelEvent($model);
        $this->trigger(self::EVENT_BEFORE_SIGNUP, $event);

        if ($model->load(Yii::$app->request->post()) && $model->signup()) {
            $this->trigger(self::EVENT_AFTER_SIGNUP, $event);
            if(Yii::$app->user->enableConfirmation)
                return $this->renderContent(Yii::t(Module::I18N_CATEGORY, 'Confirmation mail has been sent to {0}.', [$model->email]));

            return $this->goHome();
        }

        return $this->render($this->module->registerView, [
            'model' => $model,
        ]);
    }
}
