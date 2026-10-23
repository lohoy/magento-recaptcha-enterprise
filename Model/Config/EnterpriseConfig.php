<?php

declare(strict_types=1);

namespace Lohoy\ReCaptchaEnterprise\Model\Config;

use Lohoy\ReCaptchaEnterprise\Model\Config\Source\CredentialsSource;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class EnterpriseConfig
{
    private const XML_PATH_PROJECT_ID        = 'recaptcha_frontend/type_recaptcha_v3/google_project_id';
    private const XML_PATH_CREDENTIALS_SOURCE = 'recaptcha_frontend/type_recaptcha_v3/google_credentials_source';
    private const XML_PATH_CREDENTIALS_FILE  = 'recaptcha_frontend/type_recaptcha_v3/google_credentials_file';
    private const XML_PATH_CREDENTIALS_JSON  = 'recaptcha_frontend/type_recaptcha_v3/google_credentials_json';
    private const XML_PATH_PUBLIC_KEY        = 'recaptcha_frontend/type_recaptcha_v3/public_key';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface   $encryptor
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
     * Returns the GCP credentials: decoded JSON array, or path to the JSON file.
     * Falls back to the GOOGLE_APPLICATION_CREDENTIALS env var if empty.
     *
     * @throws \JsonException if the stored JSON cannot be decoded
     */
    public function getCredentials(): array|string
    {
        $source = $this->scopeConfig->getValue(self::XML_PATH_CREDENTIALS_SOURCE, ScopeInterface::SCOPE_WEBSITE);

        if ($source === CredentialsSource::JSON) {
            $encrypted = (string) $this->scopeConfig->getValue(self::XML_PATH_CREDENTIALS_JSON, ScopeInterface::SCOPE_WEBSITE);
            $json = trim($this->encryptor->decrypt($encrypted));
            if ($json !== '') {
                return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            }
        } else {
            $configValue = trim(
                (string) $this->scopeConfig->getValue(self::XML_PATH_CREDENTIALS_FILE, ScopeInterface::SCOPE_WEBSITE)
            );
            if ($configValue !== '') {
                return $configValue;
            }
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
