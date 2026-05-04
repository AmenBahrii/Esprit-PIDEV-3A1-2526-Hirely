<?php

namespace App\Tests\Onboarding;

use App\Onboarding\AttachmentUploadConfiguration;
use PHPUnit\Framework\TestCase;

final class AttachmentUploadConfigurationTest extends TestCase
{
    public function testCloudinaryUploadIsDisabledWhenRequiredValuesAreMissing(): void
    {
        $configuration = new AttachmentUploadConfiguration('dtkb3tazw', '');

        self::assertFalse($configuration->isEnabled());
        self::assertSame([
            'enabled' => false,
            'cloud_name' => 'dtkb3tazw',
            'unsigned_preset' => '',
        ], $configuration->toViewData());
    }

    public function testCloudinaryUploadViewDataCanBePassedToTwigAndJavascript(): void
    {
        $configuration = new AttachmentUploadConfiguration('dtkb3tazw', 'hirely_unsigned');

        self::assertTrue($configuration->isEnabled());
        self::assertSame('dtkb3tazw', $configuration->getCloudName());
        self::assertSame('hirely_unsigned', $configuration->getUnsignedPreset());
        self::assertSame([
            'enabled' => true,
            'cloud_name' => 'dtkb3tazw',
            'unsigned_preset' => 'hirely_unsigned',
        ], $configuration->toViewData());
    }
}
