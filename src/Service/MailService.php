<?php

namespace App\Service;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class MailService extends PHPMailer
{

    const EXCEPTIONS = 'exceptions';

    /**
     * @var array
     */
    protected array $settings;

    /**
     * The From email address for the message.
     * @var string
     */
    public $From = '';

    /**
     * The From name of the message.
     * @var string
     */
    public $FromName = '';

    /**
     * AdgeminiMail constructor.
     * @param array $settings
     * @param null $exceptions
     */
    public function __construct(array $settings, $exceptions = null)
    {
        if ($exceptions === null) {
            $exceptions = $settings[self::EXCEPTIONS];
        }
        parent::__construct($exceptions);
        $this->settings = $settings;
        if ($this->settings['isSmtp']) {
            $smtpData = $this->settings['defaults']['smtpData'];
            $this->isSMTP();
            $this->SMTPDebug = $smtpData['smtpDebug'];
            if ($this->SMTPDebug>0) {
                $this->Debugoutput = "error_log";
            }
            $this->Host = $smtpData['mainMailer'] . ';' . $smtpData['backupMailer'];
            $this->SMTPAuth = $smtpData['smtpAuth'];
            $this->Username = $smtpData['username'];
            $this->Password = $smtpData['password'];
            $this->SMTPSecure = $smtpData['smtpSecure'];
            $this->Port = $smtpData['smtpPort'];
        }
    }

    /**
     * @param bool $isError
     * @param bool|null $isHtml
     * @return bool
     * @throws Exception
     */
    public function send(bool $isError = false, ?bool $isHtml = null): bool
    {
        $this->CharSet = 'UTF-8';
        if (empty($this->From)) {
            $this->From = $this->settings['defaults']['From'];
        }
        if (empty($this->FromName)) {
            $this->FromName = $this->settings['defaults']['FromName'];
        }
        if ($isHtml === null) {
            $this->isHTML($this->settings['defaults']['isHtml']);
        }
        if (empty($this->Subject)) {
            $this->Subject = ($isError) ? $this->settings['defaults']['errorSubject'] : 'no subject';
        }
        if (empty($this->getAllRecipientAddresses())) {
            $this->addAddress(
                $this->settings['defaults']['errorAddress'],
                $this->settings['defaults']['errorName']
            );
        }
        return parent::send();
    }

}
