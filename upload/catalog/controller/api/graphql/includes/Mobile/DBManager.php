<?php

namespace GQL\Mobile;
use GQL\Helpers;

class DBManager extends Singleton
{
    private $table_name;
    private $ctx;
    public function __construct(&$ctx)
    {
        $this->ctx = $ctx;
        $this->table_name = DB_PREFIX . 'otp_tokens';
    }

    /**
     * Saves a new code or returns last valid one
     * @param string $telephone
     * @param string $code
     * @return string
     */
    public function saveCodeOrGetOldOne($telephone, $code)
    {
        $_code = $this->searchCode($telephone);
        if ($_code) {
            return $_code;
        }

        return $this->insertCode($telephone, $code);
    }

    /**
     * Insert Code in DB to validated against later
     * @param string $telephone
     * @param string $code
     * @return string
     */
    private function insertCode($telephone, $code)
    {
        $this->ctx->db->query(
            "INSERT INTO $this->table_name (telephone, `code`, createdAt) values ({$telephone}, {$code}, NOW())"
        );

        return $code;
    }

    /**
     * Query DB for valid codes for a specific phoneNumber
     * @param string $telephone
     * @return string
     */
    private function searchCode($telephone)
    {
        $result = $this->ctx->db->query(
            "SELECT `code` FROM $this->table_name WHERE telephone= {$telephone} AND is_valid = 1 AND UNIX_TIMESTAMP(createdAt)> ( UNIX_TIMESTAMP(NOW()) - 1000 ) LIMIT 1"
        );

        return $result ? $result['code'] : false;
    }

    /**
     * Marks code as no more valid
     * @param string $telephone
     * @param string $token
     */
    private function setNotValid($telephone, $token)
    {
        $this->ctx->db->query(
            "UPDATE $this->table_name SET is_valid = 0 WHERE telephone= {$telephone} AND `code` = {$token}"
        );
    }

    /**
     * Get message template
     * It gets the one that corresponds to the current language 
     * @param string $message_topic
     * @return string
     */
    public function getMessageTemplate($message_topic)
    {
        $currentLanguageCode = $this->ctx->session->data['language'];
        return Helpers\getSettingByKey($this->ctx,'config_mobile',"config_mobile_{$message_topic}-{$currentLanguageCode}");
    }

    /**
     * Verifies that the code is valid
     * @param string $telephone
     * @param string $token
     * @return boolean
     */
    public function verifyToken($telephone, $token)
    {
        $code = $this->searchCode($telephone);
        if ($code && $code == $token) {
            $this->setNotValid($telephone, $token);
            return true;
        }

        return false;
    }
}
