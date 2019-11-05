<?php

namespace GQL\Mobile\Providers;

use GQL\Mobile\DBManager;

abstract class MobileDriver
{
    private $ctx;

    public function __construct(&$ctx)
    {
        $this->ctx = $ctx;
    }
    /**
     * Send OTP in SMS
     * @param array $to
     * @param array $options
     * @return array
     */
    public function sendOTP($to, $options)
    {
        $response = [
            'data' => [],
            'errors' => []
        ];
        $telephone = $to['country_code'] . $to['phone_number'];
        if ($options['purpose'] == "register") {
            $user = (new DBManager($this->ctx))->getUserByMobile($telephone);
            if (isset($user) && !empty($user)) {
                $response['errors'][] = [
                    'code' => 'EXISTS',
                    'title' => 'exists',
                    'content' => 'Phone Number is already registered!',
                ];
                return $response;
            }
        }
        $code = rand(1000, 9999);
        

        $code = (new DBManager($this->ctx))->saveCodeOrGetOldOne($telephone, $code);
        $message_template = (new DBManager($this->ctx))->getMessageTemplate('message_template_otp')['value'];

        $text = urlencode(str_replace("SHOPZOTPCODE", $code, $message_template));
        $response = array_merge($response, $this->sendSMS($to, $text));

        return $response;
    }

    /**
     * Sends Forgot Message as SMS
     * @param array $to
     */
    public function sendForgetPassword($to, $options)
    {
        $response = [
            'data' => [],
            'errors' => []
        ];
        $telephone = $to['country_code'] . $to['phone_number'];
        $user = (new DBManager($this->ctx))->getUserByMobile($telephone);
        if (!$user) {
            $response['errors'][] = [
                'code' => 'NOTEXISTS',
                'title' => 'Not Exists',
                'content' => 'Phone Number does not exist!',
            ];
            return $response;
        }

        $forgotPassURL = $this->ctx->url->link('account/forgotten', '', true);
        $message_template = (new DBManager($this->ctx))->getMessageTemplate('message_template_forgot')['value'];
        $text = urlencode(strftime('%Y-%m-%d %H-%M-%S', time()) . ": " . str_replace("SHOPZFORGOT", $forgotPassURL, $message_template));

        $isSent = $this->sendSMS($to, $text);
        if (!$isSent) {
            $response['errors'][] = [
                'code' => 'INVALID',
                'title' => 'Invalid',
                'content' => 'Phone Number is invalid!',
            ];
        }
        return $response;
    }

    /**
     * Verify Mobile Token that was sent to phone
     * @param array $to
     * @param string $token
     * @return boolean
     */
    public function verifyOTP($to, $token)
    {
        $telephone = $to['country_code'] . $to['phone_number'];
        return (new DBManager($this->ctx))->verifyToken($telephone, $token);
    }
}
