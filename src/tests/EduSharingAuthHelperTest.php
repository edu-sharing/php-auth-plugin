<?php declare(strict_types=1);

namespace tests;

use EduSharingApiClient\AppAuthException;
use EduSharingApiClient\CurlResult;
use EduSharingApiClient\EduSharingAuthHelper;
use EduSharingApiClient\EduSharingHelperBase;
use Exception;
use JsonException;
use PHPUnit\Framework\TestCase;

/**
 * Class EduSharingAuthHelperTest
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 */
class EduSharingAuthHelperTest extends TestCase
{
    /**
     * Function testGetTicketAuthenticationInfoReturnsDecodedDataFromCurlIfAllOk
     *
     * @return void
     */
    public function testGetTicketAuthenticationInfoReturnsDecodedDataFromCurlIfAllOk(): void {
        $url      = 'https://www.test.de';
        $baseMock = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs([$url, 'pkey123', 'myappid'])
            ->onlyMethods(['handleCurlRequest'])
            ->getMock();
        $baseMock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult('{"test": "test", "statusCode": "OK"}', 5, ['test' => 'hello'])));
        $authHelper = new EduSharingAuthHelper($baseMock);
        try {
            $result = $authHelper->getTicketAuthenticationInfo('test');
            $this->assertArrayHasKey('test', $result);
        } catch (Exception $exception) {
            $this->fail('Exception thrown. Message: ' . $exception->getMessage());
        }
    }

    public function testGetTicketAuthenticationInfoThrowsExceptionOnEmptyCurlResult(): void {
        $url      = 'https://www.test.de';
        $baseMock = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs([$url, '123', '123'])
            ->onlyMethods(['handleCurlRequest'])
            ->getMock();
        $baseMock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult('', 5, ['test' => 'hello'])));
        $authHelper = new EduSharingAuthHelper($baseMock);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No answer from repository. Possibly a timeout while trying to connect to ' . $url);
        $authHelper->getTicketAuthenticationInfo('test');
    }

    /**
     * Function testGetTicketAuthenticationInfoThrowsJsonExceptionOnInvalidJsonResponse
     *
     * @return void
     */
    public function testGetTicketAuthenticationInfoThrowsJsonExceptionOnInvalidJsonResponse(): void {
        $url      = 'https://www.test.de';
        $baseMock = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs([$url, '123', '123'])
            ->onlyMethods(['handleCurlRequest'])
            ->getMock();
        $baseMock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult('{', 5, ['test' => 'hello'])));
        $authHelper = new EduSharingAuthHelper($baseMock);
        $this->expectException(JsonException::class);
        $authHelper->getTicketAuthenticationInfo('test');
    }

    /**
     * Function testGetTicketAuthenticationInfoThrowsExceptionIfStatusCodeIsNotOk
     *
     * @return void
     */
    public function testGetTicketAuthenticationInfoThrowsExceptionIfStatusCodeIsNotOk(): void {
        $url      = 'https://www.test.de';
        $baseMock = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs([$url, '123', '123'])
            ->onlyMethods(['handleCurlRequest'])
            ->getMock();
        $baseMock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult('{"test": "test", "statusCode": "NOT_OK"}', 5, ['test' => 'hello'])));
        $authHelper = new EduSharingAuthHelper($baseMock);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The given ticket is not valid anymore');
        $authHelper->getTicketAuthenticationInfo('test');
    }

    /**
     * Function testGetTicketForUserThrowsExceptionOnEmptyCurlResult
     *
     * @return void
     * @throws AppAuthException
     */
    public function testGetTicketForUserThrowsExceptionOnEmptyCurlResult(): void {
        $url      = 'https://www.test.de';
        $baseMock = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs([$url, '123', '123'])
            ->onlyMethods(['handleCurlRequest', 'sign'])
            ->getMock();
        $baseMock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult('', 5, ['http_code' => '500'])));
        $baseMock->expects($this->once())
            ->method('sign')
            ->will($this->returnValue('test'));
        $authHelper = new EduSharingAuthHelper($baseMock);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('edu-sharing ticket could not be retrieved: HTTP-Code ' . '500' . ': ' . 'No answer from repository. Possibly a timeout while trying to connect to "' . $url . '"');
        $authHelper->getTicketForUser('test');
    }

    /**
     * Function testGetTicketForUserThrowsJsonExceptionOnInvalidJson
     *
     * @return void
     */
    public function testGetTicketForUserThrowsJsonExceptionOnInvalidJson(): void {
        $url      = 'https://www.test.de';
        $baseMock = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs([$url, '123', '123'])
            ->onlyMethods(['handleCurlRequest', 'sign'])
            ->getMock();
        $baseMock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult('{', 5, ['http_code' => '500'])));
        $baseMock->expects($this->once())
            ->method('sign')
            ->will($this->returnValue('test'));
        $authHelper = new EduSharingAuthHelper($baseMock);
        $this->expectException(JsonException::class);
        $authHelper->getTicketForUser('test');
    }

    /**
     * Function testGetTicketForUserThrowsAppAuthExceptionOnBadResponse
     *
     * @return void
     */
    public function testGetTicketForUserThrowsAppAuthExceptionOnBadResponse(): void {
        $url      = 'https://www.test.de';
        $baseMock = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs([$url, '123', '123'])
            ->onlyMethods(['handleCurlRequest', 'sign'])
            ->getMock();
        $baseMock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult('{"message": "testMessage"}', 5, ['http_code' => '500'])));
        $baseMock->expects($this->once())
            ->method('sign')
            ->will($this->returnValue('test'));
        $authHelper = new EduSharingAuthHelper($baseMock);
        $this->expectException(AppAuthException::class);
        $this->expectExceptionMessage('testMessage');
        $authHelper->getTicketForUser('test');
    }

    /**
     * Function testGetTicketForUserReturnsReturnsTicketDataIfUserIdMatchesExactly
     *
     * @return void
     */
    public function testGetTicketForUserReturnsReturnsTicketDataIfUserIdMatchesExactly(): void {
        $url      = 'https://www.test.de';
        $baseMock = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs([$url, '123', '123'])
            ->onlyMethods(['handleCurlRequest', 'sign'])
            ->getMock();
        $baseMock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult('{"ticket": "testTicket", "userId": "testUserId"}', 0, ['http_code' => '200'])));
        $baseMock->expects($this->once())
            ->method('sign')
            ->will($this->returnValue('test'));
        $authHelper = new EduSharingAuthHelper($baseMock);
        try {
            $this->assertEquals('testTicket', $authHelper->getTicketForUser('testUserId'));
        } catch (Exception $exception) {
            $this->fail('Exception thrown. Message: ' . $exception->getMessage());
        }
    }

    /**
     * Function testGetTicketForUserReturnsReturnsTicketDataIfUserIdIsEmail
     *
     * @return void
     */
    public function testGetTicketForUserReturnsReturnsTicketDataIfUserIdIsEmail(): void {
        $url      = 'https://www.test.de';
        $baseMock = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs([$url, '123', '123'])
            ->onlyMethods(['handleCurlRequest', 'sign'])
            ->getMock();
        $baseMock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult('{"ticket": "testTicket", "userId": "testUserId@test"}', 0, ['http_code' => '200'])));
        $baseMock->expects($this->once())
            ->method('sign')
            ->will($this->returnValue('test'));
        $authHelper = new EduSharingAuthHelper($baseMock);
        try {
            $this->assertEquals('testTicket', $authHelper->getTicketForUser('testUserId'));
        } catch (Exception $exception) {
            $this->fail('Exception thrown. Message: ' . $exception->getMessage());
        }
    }
}
