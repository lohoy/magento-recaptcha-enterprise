<?php

declare(strict_types=1);

namespace Lohoy\ReCaptchaEnterprise\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class EnterpriseConfig
{
    private const XML_PATH_PROJECT_ID        = 'recaptcha_frontend/type_recaptcha_v3/google_project_id';
    private const XML_PATH_CREDENTIALS_FILE  = 'recaptcha_frontend/type_recaptcha_v3/google_credentials_file';
    private const XML_PATH_PUBLIC_KEY        = 'recaptcha_frontend/type_recaptcha_v3/public_key';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {}

    /**
     * Returns the Google Cloud Project ID.
     */
    public function getProjectId(): string
    {
        return trim(
            (string) $this->scopeConfig->getValue(self::XML_PATH_PROJECT_ID, ScopeInterface::SCOPE_WEBSITE)
        );
    }

    /**
     * Returns the path to the GCP credentials JSON file.
     * Falls back to the GOOGLE_APPLICATION_CREDENTIALS env var if empty.
     */
    public function getCredentialsFile(): string
    {
        $configValue = trim(
            (string) $this->scopeConfig->getValue(self::XML_PATH_CREDENTIALS_FILE, ScopeInterface::SCOPE_WEBSITE)
        );

        if ($configValue !== '') {
            return $configValue;
        }

        return (string) (getenv('GOOGLE_APPLICATION_CREDENTIALS') ?: '');
    }

    /**
     * Returns the reCAPTCHA Enterprise site key (public key).
     * Required by the Enterprise API to validate that the token was issued for this key.
     */
    public function getSiteKey(): string
    {
        return trim(
            (string) $this->scopeConfig->getValue(self::XML_PATH_PUBLIC_KEY, ScopeInterface::SCOPE_WEBSITE)
        );
    }

    /**
     * Returns true if the module has the minimum required configuration.
     */
    public function isConfigured(): bool
    {
        return $this->getProjectId() !== '' && $this->getSiteKey() !== '';
    }
}
