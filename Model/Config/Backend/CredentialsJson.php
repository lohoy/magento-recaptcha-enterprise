<?php

declare(strict_types=1);

namespace Lohoy\ReCaptchaEnterprise\Model\Config\Backend;

use Magento\Config\Model\Config\Backend\Encrypted;
use Magento\Framework\Exception\LocalizedException;

/**
 * Stores the Service Account JSON encrypted, rejecting invalid JSON so a typo can't break every protected form.
 */
class CredentialsJson extends Encrypted
{
    public function beforeSave()
    {
        $value = trim((string) $this->getValue());

        // '******' is the obscured placeholder: the stored value is kept as is
        if ($value !== '' && !preg_match('/^\*+$/', $value) && !is_array(json_decode($value, true))) {
            throw new LocalizedException(__('Google Credentials JSON is not a valid JSON document.'));
        }

        return parent::beforeSave();
    }
}
