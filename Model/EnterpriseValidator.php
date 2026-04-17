<?php

declare(strict_types=1);

namespace Lohoy\ReCaptchaEnterprise\Model;

use Google\ApiCore\ApiException;
use Google\Cloud\RecaptchaEnterprise\V1\Assessment;
use Google\Cloud\RecaptchaEnterprise\V1\Client\RecaptchaEnterpriseServiceClient;
use Google\Cloud\RecaptchaEnterprise\V1\CreateAssessmentRequest;
use Google\Cloud\RecaptchaEnterprise\V1\Event;
use Lohoy\ReCaptchaEnterprise\Model\Config\EnterpriseConfig;
use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigInterface;
use Magento\ReCaptchaValidationApi\Api\ValidatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Validates a reCAPTCHA v3 token against the Google Cloud reCAPTCHA Enterprise API.
 */
class EnterpriseValidator implements ValidatorInterface
{
    private const ERROR_INVALID_TOKEN      = 'recaptcha-enterprise-invalid-token';
    private const ERROR_SCORE_TOO_LOW      = 'recaptcha-enterprise-score-threshold-not-met';
    private const ERROR_API_EXCEPTION      = 'recaptcha-enterprise-api-exception';
    private const ERROR_MISSING_CONFIG     = 'recaptcha-enterprise-missing-config';

    public function __construct(
        private readonly ValidationResultFactory $validationResultFactory,
        private readonly EnterpriseConfig        $enterpriseConfig,
        private readonly LoggerInterface         $logger
    ) {}

    /**
     * @inheritdoc
     */
    public function isValid(
        string $reCaptchaResponse,
        ValidationConfigInterface $validationConfig
    ): ValidationResult {

        if (!$this->enterpriseConfig->isConfigured()) {
            $this->logger->critical('[RecaptchaEnterprise] Google Project ID or Site Key is not configured.');
            return $this->validationResultFactory->create([
                'errors' => [self::ERROR_MISSING_CONFIG => 'reCAPTCHA Enterprise is not configured properly.'],
            ]);
        }

        $clientOptions = [];
        $credentialsFile = $this->enterpriseConfig->getCredentialsFile();
        if ($credentialsFile !== '') {
            $clientOptions['credentials'] = $credentialsFile;
        }

        try {
            $client = new RecaptchaEnterpriseServiceClient($clientOptions);
        } catch (\Exception $e) {
            $this->logger->critical('[RecaptchaEnterprise] Failed to create client: ' . $e->getMessage());
            return $this->validationResultFactory->create([
                'errors' => [self::ERROR_API_EXCEPTION => 'reCAPTCHA Enterprise client could not be initialized.'],
            ]);
        }

        $event = (new Event())
            ->setToken($reCaptchaResponse)
            ->setSiteKey($this->enterpriseConfig->getSiteKey())
            ->setUserIpAddress($validationConfig->getRemoteIp());

        $projectName = RecaptchaEnterpriseServiceClient::projectName(
            $this->enterpriseConfig->getProjectId()
        );

        $request = (new CreateAssessmentRequest())
            ->setParent($projectName)
            ->setAssessment((new Assessment())->setEvent($event));

        try {
            $assessment = $client->createAssessment($request);
        } catch (ApiException $e) {
            $this->logger->error('[RecaptchaEnterprise] API call failed: ' . $e->getMessage(), [
                'code'    => $e->getCode(),
                'project' => $this->enterpriseConfig->getProjectId(),
            ]);
            return $this->validationResultFactory->create([
                'errors' => [self::ERROR_API_EXCEPTION => 'reCAPTCHA Enterprise API error: ' . $e->getMessage()],
            ]);
        } finally {
            $client->close();
        }

        $tokenProperties = $assessment->getTokenProperties();
        if ($tokenProperties === null || !$tokenProperties->getValid()) {
            $invalidReason = $tokenProperties?->getInvalidReason() ?? 'UNKNOWN';
            $this->logger->warning('[RecaptchaEnterprise] Invalid token', ['reason' => $invalidReason]);
            return $this->validationResultFactory->create([
                'errors' => [
                    self::ERROR_INVALID_TOKEN => 'reCAPTCHA token is invalid (reason: ' . $invalidReason . ').',
                ],
            ]);
        }

        $riskAnalysis = $assessment->getRiskAnalysis();
        $score        = $riskAnalysis !== null ? $riskAnalysis->getScore() : 0.0;

        $scoreThreshold = $this->getScoreThreshold($validationConfig);
        if ($score < $scoreThreshold) {
            $this->logger->warning('[RecaptchaEnterprise] Score below threshold', [
                'score'     => $score,
                'threshold' => $scoreThreshold,
            ]);
            return $this->validationResultFactory->create([
                'errors' => [
                    self::ERROR_SCORE_TOO_LOW => sprintf(
                        'reCAPTCHA score %.2f is below the minimum threshold %.2f.',
                        $score,
                        $scoreThreshold
                    ),
                ],
            ]);
        }

        return $this->validationResultFactory->create(['errors' => []]);
    }

    /**
     * Reads the score threshold from the ValidationConfig extension attributes,
     */
    private function getScoreThreshold(ValidationConfigInterface $validationConfig): float
    {
        $extensionAttributes = $validationConfig->getExtensionAttributes();
        if ($extensionAttributes !== null && $extensionAttributes->getScoreThreshold() !== null) {
            return (float) $extensionAttributes->getScoreThreshold();
        }

        return 0.5;
    }
}
