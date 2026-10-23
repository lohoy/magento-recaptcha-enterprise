<?php

declare(strict_types=1);

namespace Lohoy\ReCaptchaEnterprise\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CredentialsSource implements OptionSourceInterface
{
    public const PATH   = 'path';
    public const JSON   = 'json';

    public function toOptionArray(): array
    {
        return [
            ['value' => self::PATH, 'label' => __('File path on server')],
            ['value' => self::JSON, 'label' => __('Encrypted JSON')],
        ];
    }
}
