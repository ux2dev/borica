<?php

declare(strict_types=1);

use Ux2Dev\Borica\Certificate\CertificateGenerator;
use Ux2Dev\Borica\Enum\Environment;

test('generate-certificate command prompts for all fields', function () {
    $tempDir = sys_get_temp_dir() . '/borica_test_' . uniqid();

    $this->artisan('borica:generate-certificate')
        ->expectsQuestion('Terminal ID (8 alphanumeric characters)', 'V1800001')
        ->expectsChoice('Environment', 'development', ['development', 'production'])
        ->expectsQuestion('Domain (without protocol, e.g. "merchantdomain.bg")', 'merchantdomain.bg')
        ->expectsQuestion('Organization name', 'Test Org')
        ->expectsQuestion('City', 'Sofia')
        ->expectsQuestion('State/Region', 'Sofia')
        ->expectsQuestion('Email', 'test@example.com')
        ->expectsQuestion('Country code (2-letter ISO)', 'BG')
        ->expectsQuestion('Output directory', $tempDir)
        ->assertExitCode(0);

    expect(is_dir($tempDir))->toBeTrue();

    $files = glob($tempDir . '/*');
    expect($files)->toHaveCount(2);

    // Clean up
    foreach ($files as $file) {
        unlink($file);
    }
    rmdir($tempDir);
});

test('generate-certificate with --merchant flag pre-fills terminal', function () {
    $tempDir = sys_get_temp_dir() . '/borica_test_' . uniqid();

    $this->artisan('borica:generate-certificate', ['--merchant' => 'default'])
        ->expectsChoice('Environment', 'development', ['development', 'production'])
        ->expectsQuestion('Domain (without protocol, e.g. "merchantdomain.bg")', 'merchantdomain.bg')
        ->expectsQuestion('Organization name', 'Test Org')
        ->expectsQuestion('City', 'Sofia')
        ->expectsQuestion('State/Region', 'Sofia')
        ->expectsQuestion('Email', 'test@example.com')
        ->expectsQuestion('Country code (2-letter ISO)', 'BG')
        ->expectsQuestion('Output directory', $tempDir)
        ->assertExitCode(0);

    $files = glob($tempDir . '/*');
    expect($files)->toHaveCount(2);

    // Clean up
    foreach ($files as $file) {
        unlink($file);
    }
    rmdir($tempDir);
});

test('generate-certificate validates terminal ID', function () {
    $this->artisan('borica:generate-certificate')
        ->expectsQuestion('Terminal ID (8 alphanumeric characters)', 'BAD')
        ->expectsOutput('Terminal ID must be exactly 8 alphanumeric characters')
        ->assertExitCode(1);
});
