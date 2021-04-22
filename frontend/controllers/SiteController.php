<?php

namespace frontend\controllers;

use common\models\User;
use DateTime;
use frontend\services\custom\Debugger;
use phpDocumentor\Reflection\Types\Integer;
use Yii;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\Json;
use yii\web\Controller;
use frontend\models\ContactForm;
use frontend\models\Base;
use frontend\models\Devices;
use frontend\models\ImeiDataSummarySearch;
use frontend\models\Zlog;
use frontend\models\Com;
use frontend\models\Org;
use common\models\UserSearch;
use vova07\fileapi\actions\UploadAction as FileAPIUpload;

/**
 * Class SiteController.
 */
class SiteController extends Controller
{
    const CELL_HEIGHT = 39;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['login', 'logout', 'signup'],
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['login', 'signup'],
                        'roles' => ['?'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['logout'],
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'frontend\services\error\db_connection\DbError',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
            ],
            'fileapi-upload' => [
                'class' => FileAPIUpload::className(),
                'path' => '@storage/tmp',
            ],
        ];
    }
    
     /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (Yii::$app->user->can('customer')) {
            $this->layout = '@frontend/modules/account/views/layouts/customer';
        }

        return parent::beforeAction($action);
    }

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        if (empty(Yii::$app->user) || empty( $user = User::findOne(Yii::$app->user->id))) {
             return $this->redirect('account/sign-in/login');
        }

        $searchModel = new UserSearch();

        if (!empty($user->company)) {
            $dataProvider = $searchModel->searchEmployees(Yii::$app->request->queryParams);
            $users = $user->company->users;
            $model = $user->company;
            $balanceHolders = $model->balanceHolders;
            $balanceHoldersData = [];
            foreach ($balanceHolders as $balanceHolder) {
                $balanceHoldersData[$balanceHolder->id] = $balanceHolder->getBalanceHolderData(self::CELL_HEIGHT);
            }
        } else {
            if (Yii::$app->user->can('customer')) {

                return $this->redirect(['account/customer/index', 'id' => Yii::$app->user->id]);
            }

            return $this->render('welcome', ['user' => $user]);
        }

        return $this->render('index', [
            'model' => $model,
            'users' => $users,
            'balanceHolders' => $balanceHolders,
            'balanceHoldersData' => $balanceHoldersData,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    /**
     * Displays contact page.
     *
     * @return mixed
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail(Yii::$app->params['adminEmail'])) {
                Yii::$app->session->setFlash('success', Yii::t('frontend', 'Thank you for contacting us. We will respond to you as soon as possible.'));
            } else {
                Yii::$app->session->setFlash('error', Yii::t('frontend', 'There was an error sending your message.'));
            }

            return $this->refresh();
        } else {
            return $this->render('contact', ['model' => $model]);
        }
    }

    /**
     * Displays monitoring page.
     *
     * @return mixed
     */
    public function actionMntr()
    {
//        if (Yii::$app->user->can('mntr')) {

        if (Yii::$app->user->can('administrator')) {
            $devices = Devices::find()->limit(10)->all();
        } else {
            $org = Org::get_org_name(Yii::$app->user->id);
            $brand = $org['name_org'];
            $devices = Devices::find()
                ->limit(10)
                ->where("`organization` = '" . $org['name_org'] . "'")
                ->all();
        }

        return $this->render('mntr', ['devices' => $devices]);
//        }
    }

    /**
     * Displays manage devices.
     *
     * @return mixed
     */
    public function actionDevices()
    {
//        if (Yii::$app->user->can('devices')) {
        $devices = Devices::find()->all();
        return $this->render('devices', ['devices' => $devices]);
//        }
    }

    /**
     * Displays add device.
     *
     * @return mixed
     */
    public function actionDevice_add()
    {
//        if (Yii::$app->user->can('device_add')) {
        return $this->render('adddevice');
//        }
    }

    /**
     * Action save device.
     *
     * @return mixed
     */
    public function actionSavedevice()
    {
        $id_dev = $this->my_strip_text(Yii::$app->request->get('id_dev', ""));
        $name = $this->my_strip_text(Yii::$app->request->get('name', ""));
        $organization = $this->my_strip_text(Yii::$app->request->get('organization', ""));
        $city = $this->my_strip_text(Yii::$app->request->get('city', ""));
        $adress = $this->my_strip_text(Yii::$app->request->get('adress', ""));
        $name_cont = $this->my_strip_text(Yii::$app->request->get('name_cont', ""));
        $tel_cont = $this->my_strip_text(Yii::$app->request->get('tel_cont', ""));
        $operator = $this->my_strip_text(Yii::$app->request->get('operator', ""));
        $n_operator = $this->my_strip_text(Yii::$app->request->get('n_operator', ""));
        $kp = $this->my_strip_text(Yii::$app->request->get('kp', ""));
        $kps = $this->my_strip_text(Yii::$app->request->get('kps', ""));
        $balans = $this->my_strip_text(Yii::$app->request->get('balans', ""));

        if ($id_dev != '' AND $name != '') {

            $dev = Devices::find()->where(['id' => $id_dev])->one();
            if (isset($id_dev)) {

                $device = new Devices();
                $device->id_dev = $id_dev;
                $device->name = $name;
                $device->organization = $organization;
                $device->city = $city;
                $device->adress = $adress;
                $device->name_cont = $name_cont;
                $device->tel_cont = $tel_cont;
                $device->operator = $operator;
                $device->n_operator = $n_operator;
                $device->kp = $kp;
                $device->balans = $balans;
                $device->kps = $kps;
                if ($device->save()) {
                    echo 'Готово';
                } else {
                    echo 'no';
                }
            } else {
                echo Yii::t('DashboardModule.base', 'Such a code already exists');
            }
        }
    }

    /**
     * Action add device.
     *
     * @return mixed
     */
    public function actionAdd_dev()
    {

        $id = $this->my_strip_text(Yii::$app->request->get('id', ""));
        $id_dev = $this->my_strip_text(Yii::$app->request->get('id_dev', ""));
        $name = $this->my_strip_text(Yii::$app->request->get('name', ""));
        $organization = $this->my_strip_text(Yii::$app->request->get('organization', ""));
        $city = $this->my_strip_text(Yii::$app->request->get('city', ""));
        $adress = $this->my_strip_text(Yii::$app->request->get('adress', ""));
        $name_cont = $this->my_strip_text(Yii::$app->request->get('name_cont', ""));
        $tel_cont = $this->my_strip_text(Yii::$app->request->get('tel_cont', ""));
        $operator = $this->my_strip_text(Yii::$app->request->get('operator', ""));
        $n_operator = $this->my_strip_text(Yii::$app->request->get('n_operator', ""));
        $kp = $this->my_strip_text(Yii::$app->request->get('kp', ""));
        $kps = $this->my_strip_text(Yii::$app->request->get('kps', ""));
        $balans = $this->my_strip_text(Yii::$app->request->get('balans', ""));

        if ($id_dev != '' AND $name != '') {

            $device = Devices::find()->where(['id' => $id])->one();

            if (isset($device['id'])) {
                $device['id_dev'] = $id_dev;
                $device['name'] = $name;
                $device['organization'] = $organization;
                $device['city'] = $city;
                $device['adress'] = $adress;
                $device['name_cont'] = $name_cont;
                $device['tel_cont'] = $tel_cont;
                $device['operator'] = $operator;
                $device['n_operator'] = $n_operator;
                $device['kp'] = $kp;
                $device['kps'] = $kps;
                $device['balans'] = $balans;

                if ($device->save()) {
                    echo 'Готово';
                } else {
                    echo Yii::t('DashboardModule.base', 'Error :(');
                }
            } else {

            }
        }
    }

    /**
     * Action edit device.
     *
     * @return mixed
     */
    public function actionDev()
    {
        if (Yii::$app->user->can('editTechData')) {
            $id = $this->my_strip_text(Yii::$app->request->get('id', ''));
            if ($id != '') {
                $device = Devices::find()->where(['id' => $id])->all();
                if (isset($device[0]['id'])) {
                    return $this->render('edit_device', ['device' => $device]);
                }
            }
        }
    }

    /**
     * Action delete device.
     *
     * @return mixed
     */
    public function actionDeldev()
    {
        if (Yii::$app->user->can('editTechData')) {
            $id = Yii::$app->request->get('id', "");

            if ($id != '' and $id != '0') {
                $dev = Devices::get_by_id($id);

                $this->redirect('/site/devices');
            }
        }
    }

    /**
     * Displays zurnal.
     *
     * @return mixed
     */
    public function actionZurnal()
    {
//        if (Yii::$app->user->can('zurnal')) {
        $dateFrom = strtotime(date('Y-m-01'));
        $dateTo = strtotime(date('Y-m-t')) + 86399;

        if (isset($_GET['dateFrom']) and $_GET['dateFrom'] != '' and isset($_GET['dateTo']) and $_GET['dateTo'] != '') {
            $df = strtotime($_GET['dateFrom']);
            $dt = strtotime($_GET['dateTo']);
            if ($df <= $dt) {
                $dateFrom = $df;
                $dateTo = $dt + 86399;
            }
        }

        return $this->render('zurnal', ['dateFrom' => $dateFrom, 'dateTo' => $dateTo]);
//        }
    }

    /**
     * Displays dlogs.
     *
     * @return mixed
     */
    public function actionDlogs()
    {
//        if (Yii::$app->user->can('dlogs')) {
        $imei = Yii::$app->request->post('imei', "");
        $type = Yii::$app->request->post('type', "");

        $dataProvider = Zlog::get_for_log($imei, $type);

        return $this->render('dlogs', ['dataProvider' => $dataProvider]);
//        }
    }

    /**
     * Action add command.
     *
     * @return mixed
     */
    public function actionAddcom()
    {
        if (Yii::$app->user->can('editTechData')) {
            $imei = Yii::$app->request->get('imei', "");
            $comand = Yii::$app->request->get('comand', "");

            if ($imei != '' and $comand != '') {
                $com = new Com();
                $com->imei = $imei;
                $com->comand = $comand;
                $com->status = '0';
                $com->save();
            }
        }
    }

    /**
     * Displays wait page.
     *
     * @return mixed
     */
    public function actionWait()
    {
        return $this->render('wait');
    }

    /**
     * Action strip text.
     *
     * @return mixed
     */
    public function my_strip_text($text)
    {
        $text = strip_tags($text);
        $text = addslashes($text);
        $text = str_replace('>', ' ', $text);
        $text = str_replace('<', ' ', $text);

        return $text;
    }

    /**
     * Action Organization manage
     *
     *
     * @return <type>
     */

    public function actionOrg()
    {
        if (Yii::$app->user->can('viewCompanyData')) {
            $org = Org::find()->all();

            return $this->render('Allorg', ['org' => $org]);
        }
    }

    /**
     * Action Add Organization
     *
     *
     * @return <type>
     */
    public function actionOrgadd()
    {
        if (Yii::$app->user->can('editCompanyData')) {
            $org = new Org();
            if (Yii::$app->request->post()) {

                $post = Yii::$app->request->post();
                if ($post['Org']['name_org'] != '') {

                    $org['name_org'] = $post['Org']['name_org'];
                    $org['desc'] = $post['Org']['desc'];
                    $org['logo_path'] = $post['Org']['logo_path'];
                    if ($org->save()) {
                        return $this->redirect(['/site/org']);
                    }
                }
            }
            return $this->render('addorg', ['org' => $org]);
        }
    }

    /**
     * Action Save Organization
     *
     *
     * @return <type>
     */
    public function actionSaveorg()
    {

        $name = Yii::$app->request->get('organization', "");

        if ($name != '') {
            $org = new Org();
            $org->name_org = $name;
            $org->save();
        }
    }

    /**
     * Action View Organization
     *
     *
     * @return <type>
     */
    public function actionVieworg()
    {
        if (Yii::$app->user->can('viewCompanyData')) {
            $id = $this->my_strip_text(Yii::$app->request->get('id', ''));
            $model = Org::findOne($id);
            $org_name = $model['name_org'];

            $devices = Devices::find()->where(['organization' => $org_name])->all();

            return $this->render('view_org', [
                'model' => $model,
                'devices' => $devices,
            ]);
        }
    }

    /**
     * Action Edit Organization
     *
     *
     * @return <type>
     */
    public function actionEditorg()
    {
        if (Yii::$app->user->can('editCompanyData')) {
            $id = $this->my_strip_text(Yii::$app->request->get('id', ''));
            if ($id != '') {
                $org = Org::find()->where(['id' => $id])->all();
                if (isset($org[0]['id'])) {
                    if (Yii::$app->request->post()) {
                        $post = Yii::$app->request->post();
                        if ($id != '' AND $post['Org']['name_org'] != '') {

                            $org = Org::find()->where(['id' => $id])->one();

                            if (isset($org['id'])) {

                                $org['name_org'] = $post['Org']['name_org'];
                                $org['desc'] = $post['Org']['desc'];
                                $org['logo_path'] = $post['Org']['logo_path'];
                                if ($org->save()) {
                                    return $this->redirect(['/site/org']);
                                }

                            }
                        }
                    }
                    return $this->render('edit_org', ['org' => $org]);
                }
            }

        }
    }

    /**
     * accessDenied
     */
    private function accessDenied()
    {
        return Yii::$app->session->setFlash(
            'AccessDenied',
            Yii::t('frontend', 'Access denied')
        );
    }

    /**
     * Action Delete organization
     *
     *
     * @return <type>
     */
    public function actionDelorg()
    {
        if (Yii::$app->user->can('editCompanyData')) {
            $id = Yii::$app->request->get('id', "");

            if ($id != '' and $id != '0') {
                $org = Org::get_by_id($id);
                $this->redirect('/frontend/site/org');
            }
        }
    }

    const HOUR = 1;
    const FORMAT = 'Y-m-d H:i:s';
    const FILE = '../services/error/db_connection/file/data.json';
    public $mail = [
        'Serhii' => 'monstrpro@gmail.com',
//        'Dmitro' => 'dmytro.v.kovtun@gmail.com',
//        'Sasha' => 'sashabardash@gmail.com',
//        'Info' => 'info@postirayka.com.ua'
    ];
    /**
     * Если разница между ошибками один час то записывает в файл data.json дату ошибки
     * и отправляет сообщение на почту
     * @param $message
     * @throws \Exception
     */
    public function actionTest($message): void
    {
        if ($this->diffHour(date(self::FORMAT), $this->getDateFromFile()) > self::HOUR) {
//            $this->save();
            $this->senderMessages($this->mail, $message);
        }
    }

    public function save(): void
    {
        $dateArray = Json::decode(file_get_contents(self::FILE), $asArray = true);
        $dateArray[] = ['date' => date(self::FORMAT)];
        file_put_contents(self::FILE, Json::encode($dateArray));

        unset($dateArray);

        echo 'save' . '<br>';
    }

    /**
     * Вернёт последнюю дату ошибки из файла data.json
     * Если массива нет (пустой файл) создаст массив и запишет дату первой ошибки
     * @return string|$this->save
     */
    public function getDateFromFile(): string
    {
        if (!is_array($this->getArrayDate())) {
            $this->save();
        }

        $array = $this->getArrayDate();
        $value = array_pop($array);

        return $value->date;
    }

    /**
     * Вернёт разницу между двумя датами, число - в часах
     *
     * @param $current_time
     * @param $time_from_file
     * @return int
     * @throws \Exception
     */
    public function diffHour(string $current_time, string $time_from_file): int
    {
        $currentDate = new DateTime($current_time);

        return $currentDate->diff(new DateTime($time_from_file))->h;
    }

    /**
     * Вернёт массив дат (дата "ошибки" исключения базы данных)
     * @return array
     */
    public function getArrayDate(): ?array
    {
        return json_decode(file_get_contents(self::FILE));
    }

    public function pushMessage($mail, $name, $message): void
    {
        Yii::$app->mailer->compose('db-error', [
            'textBody' => $message,
        ])
            ->setFrom(env('ADMIN_EMAIL'))
            ->setTo($mail)
            ->setSubject('Hello, ' . $name . '!')
            ->setTextBody('')
            ->send();
    }

    public function senderMessages(array $mails, string $message): void
    {
        foreach ($mails as $name => $email) {
            $this->pushMessage($email, $name, $message);
        }
    }
}
