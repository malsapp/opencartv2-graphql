<?php

namespace GQL\Mobile\Providers;

use GQL\Helpers\User;
use GQL\Mobile\DBManager;

abstract class MobileDriver
{
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
            $user = User::instance()->getUserByMobile($telephone);
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
        $code = DBManager::instance()->saveCodeOrGetOldOne($telephone, $code);
        $message_template = DBManager::instance()->getMessageTemplate('message_template_otp');

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
        $user = User::instance()->getUserByMobile($telephone);
        if (!$user) {
            $response['errors'][] = [
                'code' => 'NOTEXISTS',
                'title' => 'Not Exists',
                'content' => 'Phone Number does not exist!',
            ];
            return $response;
        }
        $forgotPassURL = wc_lostpassword_url();
        $message_template = DBManager::instance()->getMessageTemplate('message_template_forgot');
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
        return DBManager::instance()->verifyToken($telephone, $token);
    }
}
