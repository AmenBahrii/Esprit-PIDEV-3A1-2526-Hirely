<?php

namespace App\Tests\Onboarding;

use App\Onboarding\PublicUrlConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class PublicUrlConfigurationTest extends TestCase
{
    public function testAutoModeUsesTheCurrentRequestHostForQrUrls(): void
    {
        $request = Request::create('https://hirely.test/admin/plans');
        $configuration = new PublicUrlConfiguration('auto');

        self::assertFalse($configuration->isUsingConfiguredPublicBaseUrl());
        self::assertSame('https://hirely.test', $configuration->resolveBaseUrl($request));
    }

    public function testConfiguredHostIsNormalizedForQrUrls(): void
    {
        $request = Request::create('http://localhost:8000/admin/plans');
        $configuration = new PublicUrlConfiguration('hirely-demo.local/');

        self::assertTrue($configuration->isUsingConfiguredPublicBaseUrl());
        self::assertSame('https://hirely-demo.local', $configuration->resolveBaseUrl($request));
    }
}
