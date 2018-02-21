<?php

/**
 * @link https://github.com/dakiquang/yiiReCaptcha
 * @copyright Copyright (c) 2015 CeresSolutions
 * @license http://opensource.org/licenses/MIT MIT
 */


/**
 * ReCaptchaValidator class file
 * Description of Recaptcha
 *
 * @author QUANG Dang <dkquang@ceresolutions.com>
 * @link http://ceresolutions.com/
 * @copyright 2015 Ceres Solutions LLC
 */

class ReCaptchaValidator extends CValidator
{
    const SITE_VERIFY_URL        = 'https://www.google.com/recaptcha/api/siteverify';
    const CAPTCHA_RESPONSE_FIELD = 'g-recaptcha-response';

    public $secret;

    /**
     * Constructor (CValidator does not have an init() function)
     */
    public function __construct()
    {
        // note: validator has no parent::__construct()
        $this->init();
    }

    public function init()
    {
        if (empty($this->secret)) {
            if (!empty(Yii::app()->reCaptcha->secret)) {
                $this->secret = Yii::app()->reCaptcha->secret;
            } else {
                throw new InvalidConfigException('Required `secret` param isn\'t set.');
            }
        }
        if ($this->message === null || empty($this->message)) {
            $this->message = Yii::t('yii', ' Please click on the checkbox to continue.');
        }
    }

    /**
     * Validate recaptcha
     * @param CModel $object the data object being validated
     * @param string $attribute the name of the attribute to be validated.
     * @return mixed
     * @throws CException
     */
    protected function validateAttribute($object, $attribute)
    {
        // get input value
        $value = $object->$attribute;
        if (empty($value)) {
            if (!($value = Yii::app()->request->getParam(self::CAPTCHA_RESPONSE_FIELD))) {
                $message = $this->message;
                $this->addError($object, $attribute, $message);
                return;
            }
        }

        $client = new GuzzleHttp\Client();
        $response = $client->post(
            self::SITE_VERIFY_URL,
            ['body'=> [
                'secret'   => $this->secret,
                'response' => $value,
                'remoteip' => Yii::app()->request->getUserHostAddress(),
            ]]);
        $body = json_decode((string)$response->getBody());

        if (!isset($body->success)) {
            throw new CException('Invalid recaptcha verify response.');
        }
        if (!$body->success) {
            $message = $this->message;
            $this->addError($object, $attribute, $message);
        }
    }

    /**
     *
     * Validate recaptcha
     * @param CModel $object the data object being validated
     * @param string $attribute the name of the attribute to be validated.
     * @return string
     */
    public function clientValidateAttribute($object, $attribute)
    {
        $message = $this->message !== null ? $this->message : Yii::t('yii', 'Please click on the checkbox to continue.');
        return "(function(messages){if(!grecaptcha.getResponse()){messages.push('{$message}');}})(messages);";
    }
}