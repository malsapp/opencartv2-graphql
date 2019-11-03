<?php
namespace WCGQL\Mobile;

use WCGQL\Helpers\Singleton;
use WCGQL\Translators\TranslatorsFactory;

class DBManager extends Singleton
{
    private $table_name;
    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'otp_tokens';
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
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO $this->table_name (telephone, `code`, createdAt) values (%s, %s, NOW())",
                $telephone,
                $code
            )
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
        global $wpdb;

        $code = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT `code` FROM $this->table_name WHERE telephone= %s AND is_valid = 1 AND UNIX_TIMESTAMP(createdAt)> ( UNIX_TIMESTAMP(NOW()) - 1000 ) LIMIT 1",
                $telephone
            )
        );

        return $code ? $code : false;
    }

    /**
     * Marks code as no more valid
     * @param string $telephone
     * @param string $token
     */
    private function setNotValid($telephone, $token)
    {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE $this->table_name SET is_valid = 0 WHERE telephone= %s AND `code` = %s",
                $telephone,
                $token
            )
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
        $currentLanguageCode = TranslatorsFactory::get_translator()->get_language()['code'];
        return get_option("{$message_topic}-{$currentLanguageCode}") ?? get_option($message_topic);
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
