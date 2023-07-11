<?php declare(strict_types = 1);

namespace tests;

use EduSharing\CurlResult;
use EduSharing\EduSharingHelperBase;
use Exception;
use JsonException;
use PHPUnit\Framework\TestCase;

class EduSharingHelperBaseTest extends TestCase
{
    public function testConstructorThrowsExceptionOnInvalidCharsInAppId(): void {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The given app id contains invalid characters or symbols');
        new EduSharingHelperBase('test', 'test', 'test*~');
    }

    public function testConstructorTrimsTrailingSlashFromUrl(): void {
        $base = new EduSharingHelperBase('abcde/', 'test', 'test');
        $this->assertEquals('abcde', $base->baseUrl);
    }

    public function testVerifyCompatibilityThrowsExceptionIfCurlResultIsNotOk(): void {
        $mock = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs(['abcde/', 'test', 'test'])
            ->onlyMethods(['handleCurlRequest'])
            ->getMock();
        $mock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult('{"test": "test", "statusCode": "OK"}', 5, ['http_code' => '500'])));
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The edu-sharing version could not be retrieved');
        $mock->verifyCompatibility();
    }

    public function testVerifyCompatibilityThrowsJsonExceptionOnInvalidJson(): void {
        $mock = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs(['abcde/', 'test', 'test'])
            ->onlyMethods(['handleCurlRequest'])
            ->getMock();
        $mock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult('{', 5, ['http_code' => 200])));
        $this->expectException(JsonException::class);
        $mock->verifyCompatibility();
    }

    public function testVerifyCompatibilityThrowsExceptionOnIncompatibleVersion(): void {
        $mock = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs(['abcde/', 'test', 'test'])
            ->onlyMethods(['handleCurlRequest'])
            ->getMock();
        $mock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult(json_encode(['version' => ['repository' => '7.0']]), 0, ['http_code' => 200])));
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The edu-sharing version of the target repository is too low');
        $mock->verifyCompatibility();
    }

    public function testVerifyCompatibilityThrowsNoExceptionOnCompatibleVersion(): void {
        $mock = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs(['abcde/', 'test', 'test'])
            ->onlyMethods(['handleCurlRequest'])
            ->getMock();
        $mock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult(json_encode(['version' => ['repository' => '8.0']]), 0, ['http_code' => 200])));
        try {
            $mock->verifyCompatibility();
            $this->addToAssertionCount(1);
        } catch (Exception $exception) {
            $this->fail('Unexpected exception thrown: ' . $exception->getMessage());
        }
    }
}