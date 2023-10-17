<?php declare(strict_types=1);

namespace tests;

use EduSharing\CurlResult;
use EduSharing\EduSharingHelperBase;
use EduSharing\EduSharingNodeHelper;
use EduSharing\EduSharingNodeHelperConfig;
use EduSharing\NodeDeletedException;
use EduSharing\UrlHandling;
use EduSharing\Usage;
use EduSharing\UsageDeletedException;
use Exception;
use JsonException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class EduSharingNodeHelperTest
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 */
class EduSharingNodeHelperTest extends TestCase
{
    /**
     * Function testCreateUsageThrowsJsonExceptionOnInvalidJsonReturn
     *
     * @return void
     */
    public function testCreateUsageThrowsJsonExceptionOnInvalidJsonReturn(): void {
        $mock = $this->getMockForJsonCheck();
        $this->expectException(JsonException::class);
        $mock->createUsage('ticket', 'container', 'resource', 'node', 'nodeVersion');
    }

    /**
     * Function testCreateUsageReturnsInitializedUsageOnSuccessfulCurl
     *
     * @return void
     */
    public function testCreateUsageReturnsInitializedUsageOnSuccessfulCurl(): void {
        $url      = 'https://www.test.de';
        $baseMock = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs([$url, 'pkey123', 'myappid'])
            ->onlyMethods(['handleCurlRequest'])
            ->getMock();
        $baseMock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult('{"parentNodeId": "parent", "nodeId": "node"}', 0, ['test' => 'hello', 'http_code' => '200'])));
        $urlHandling  = new UrlHandling(true, 'https://endpoint.net');
        $helperConfig = new EduSharingNodeHelperConfig($urlHandling);
        $mock         = $this->getMockBuilder(EduSharingNodeHelper::class)
            ->setConstructorArgs([$baseMock, $helperConfig])
            ->onlyMethods(['getSignatureHeaders'])
            ->getMock();
        $mock->expects($this->once())
            ->method('getSignatureHeaders')
            ->will($this->returnValue([]));
        $result = $mock->createUsage('ticket', 'container', 'resource', 'node', 'nodeVersion');
        $this->assertEquals('parent', $result->nodeId);
        $this->assertEquals('nodeVersion', $result->nodeVersion);
        $this->assertEquals('container', $result->containerId);
        $this->assertEquals('resource', $result->resourceId);
        $this->assertEquals('node', $result->usageId);
    }

    /**
     * Function testCreateUsageThrowsExceptionOnFailedCreation
     *
     * @return void
     */
    public function testCreateUsageThrowsExceptionOnFailedCreation(): void {
        $mock = $this->getMockForFailedCurlTest();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('creating usage failed');
        $this->expectExceptionMessage('500');
        $this->expectExceptionMessage('myMessage');
        $this->expectExceptionMessage('myError');
        $mock->createUsage('ticket', 'container', 'resource', 'node', 'nodeVersion');
    }

    /**
     * Function testGetUsageIdByParametersThrowsJsonExceptionOnInvalidJsonResponse
     *
     * @return void
     */
    public function testGetUsageIdByParametersThrowsJsonExceptionOnInvalidJsonResponse(): void {
        $mock = $this->getMockForJsonCheck();
        $this->expectException(JsonException::class);
        $mock->getUsageIdByParameters('ticket', 'node', 'container', 'resource');
    }

    /**
     * Function testGetUsageIdByParametersThrowsExceptionOnFailedCurl
     *
     * @return void
     */
    public function testGetUsageIdByParametersThrowsExceptionOnFailedCurl(): void {
        $mock = $this->getMockForFailedCurlTest();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('fetching usage list for course failed');
        $this->expectExceptionMessage('500');
        $this->expectExceptionMessage('myMessage');
        $this->expectExceptionMessage('myError');
        $mock->getUsageIdByParameters('ticket', 'node', 'container', 'resource');
    }

    /**
     * Function testGetUsageIdByParameterReturnsNullIfNoMatchingUsageIsFound
     *
     * @return void
     */
    public function testGetUsageIdByParameterReturnsNullIfNoMatchingUsageIsFound(): void {
        $url       = 'https://www.test.de';
        $usageData = [
            'usages' => [
                [
                    'appId'      => 'nomatch',
                    'courseId'   => 'nomatch',
                    'resourceId' => 'nomatch'
                ]
            ]
        ];
        $baseMock  = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs([$url, 'pkey123', 'myappid'])
            ->onlyMethods(['handleCurlRequest'])
            ->getMock();
        $baseMock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult(json_encode($usageData), 0, ['test' => 'hello', 'http_code' => '200'])));
        $urlHandling  = new UrlHandling(true, 'https://endpoint.net');
        $helperConfig = new EduSharingNodeHelperConfig($urlHandling);
        $mock         = $this->getMockBuilder(EduSharingNodeHelper::class)
            ->setConstructorArgs([$baseMock, $helperConfig])
            ->onlyMethods(['getSignatureHeaders'])
            ->getMock();
        $mock->expects($this->once())
            ->method('getSignatureHeaders')
            ->will($this->returnValue([]));
        $this->assertEquals(null, $mock->getUsageIdByParameters('ticket', 'node', 'container', 'resource'));
    }

    /**
     * Function testGetUsageIdByParameterReturnsNodeIdIfMatchingUsageIsFound
     *
     * @return void
     */
    public function testGetUsageIdByParameterReturnsNodeIdIfMatchingUsageIsFound(): void {
        $url       = 'https://www.test.de';
        $usageData = [
            'usages' => [
                [
                    'appId'      => 'myappid',
                    'courseId'   => 'container',
                    'resourceId' => 'resource',
                    'nodeId'     => 'success'
                ]
            ]
        ];
        $baseMock  = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs([$url, 'pkey123', 'myappid'])
            ->onlyMethods(['handleCurlRequest'])
            ->getMock();
        $baseMock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult(json_encode($usageData), 0, ['test' => 'hello', 'http_code' => '200'])));
        $urlHandling  = new UrlHandling(true, 'https://endpoint.net');
        $helperConfig = new EduSharingNodeHelperConfig($urlHandling);
        $mock         = $this->getMockBuilder(EduSharingNodeHelper::class)
            ->setConstructorArgs([$baseMock, $helperConfig])
            ->onlyMethods(['getSignatureHeaders'])
            ->getMock();
        $mock->expects($this->once())
            ->method('getSignatureHeaders')
            ->will($this->returnValue([]));
        $this->assertEquals('success', $mock->getUsageIdByParameters('ticket', 'node', 'container', 'resource'));
    }

    /**
     * Function testGetUsageIdByParameterReturnsNullIfMatchingUsageIsFoundButNoNodeIdProvided
     *
     * @return void
     */
    public function testGetUsageIdByParameterReturnsNullIfMatchingUsageIsFoundButNoNodeIdProvided(): void {
        $url       = 'https://www.test.de';
        $usageData = [
            'usages' => [
                [
                    'appId'      => 'myappid',
                    'courseId'   => 'container',
                    'resourceId' => 'resource'
                ]
            ]
        ];
        $baseMock  = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs([$url, 'pkey123', 'myappid'])
            ->onlyMethods(['handleCurlRequest'])
            ->getMock();
        $baseMock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult(json_encode($usageData), 0, ['test' => 'hello', 'http_code' => '200'])));
        $urlHandling  = new UrlHandling(true, 'https://endpoint.net');
        $helperConfig = new EduSharingNodeHelperConfig($urlHandling);
        $mock         = $this->getMockBuilder(EduSharingNodeHelper::class)
            ->setConstructorArgs([$baseMock, $helperConfig])
            ->onlyMethods(['getSignatureHeaders'])
            ->getMock();
        $mock->expects($this->once())
            ->method('getSignatureHeaders')
            ->will($this->returnValue([]));
        $this->assertEquals(null, $mock->getUsageIdByParameters('ticket', 'node', 'container', 'resource'));
    }

    /**
     * Function testGetNodeByUsageThrowsJsonExceptionOnInvalidJsonResponse
     *
     * @return void
     */
    public function testGetNodeByUsageThrowsJsonExceptionOnInvalidJsonResponse(): void {
        $mock = $this->getMockForJsonCheck();
        $this->expectException(JsonException::class);
        $mock->getNodeByUsage(new Usage('nodeId', 'nodeVersion', 'containerId', 'resourceId', 'usageId'));
    }

    /**
     * Function testGetNodeByUsageReturnsDecodedDataOnSuccessfulCall
     *
     * @return void
     */
    public function testGetNodeByUsageReturnsDecodedDataOnSuccessfulCall(): void {
        $url      = 'https://www.test.de';
        $baseMock = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs([$url, 'pkey123', 'myappid'])
            ->onlyMethods(['handleCurlRequest'])
            ->getMock();
        $baseMock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult('{"testData": "success"}', 0, ['test' => 'hello', 'http_code' => '200'])));
        $urlHandling  = new UrlHandling(true, 'https://endpoint.net');
        $helperConfig = new EduSharingNodeHelperConfig($urlHandling);
        $mock         = $this->getMockBuilder(EduSharingNodeHelper::class)
            ->setConstructorArgs([$baseMock, $helperConfig])
            ->onlyMethods(['getSignatureHeaders'])
            ->getMock();
        $mock->expects($this->once())
            ->method('getSignatureHeaders')
            ->will($this->returnValue([]));
        $result = $mock->getNodeByUsage(new Usage('nodeId', 'nodeVersion', 'containerId', 'resourceId', 'usageId'));
        $this->assertArrayHasKey('testData', $result);
        $this->assertEquals('success', $result['testData']);
    }

    /**
     * Function testGetNodeByUsageThrowsUsageDeletedExceptionOnErrorCode403
     *
     * @return void
     */
    public function testGetNodeByUsageThrowsUsageDeletedExceptionOnErrorCode403(): void {
        $url      = 'https://www.test.de';
        $baseMock = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs([$url, 'pkey123', 'myappid'])
            ->onlyMethods(['handleCurlRequest'])
            ->getMock();
        $baseMock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult('{"testData": "success"}', 0, ['test' => 'hello', 'http_code' => '403'])));
        $urlHandling  = new UrlHandling(true, 'https://endpoint.net');
        $helperConfig = new EduSharingNodeHelperConfig($urlHandling);
        $mock         = $this->getMockBuilder(EduSharingNodeHelper::class)
            ->setConstructorArgs([$baseMock, $helperConfig])
            ->onlyMethods(['getSignatureHeaders'])
            ->getMock();
        $mock->expects($this->once())
            ->method('getSignatureHeaders')
            ->will($this->returnValue([]));
        $this->expectException(UsageDeletedException::class);
        $this->expectExceptionMessage('the given usage is deleted and the requested node is not public');
        $mock->getNodeByUsage(new Usage('nodeId', 'nodeVersion', 'containerId', 'resourceId', 'usageId'));
    }

    /**
     * Function testGetNodeByUsageThrowsNodeDeletedExceptionOnErrorCode404
     *
     * @return void
     */
    public function testGetNodeByUsageThrowsNodeDeletedExceptionOnErrorCode404(): void {
        $url      = 'https://www.test.de';
        $baseMock = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs([$url, 'pkey123', 'myappid'])
            ->onlyMethods(['handleCurlRequest'])
            ->getMock();
        $baseMock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult('{"testData": "success", "error": "error", "message":"message"}', 0, ['test' => 'hello', 'http_code' => 404])));
        $urlHandling  = new UrlHandling(true, 'https://endpoint.net');
        $helperConfig = new EduSharingNodeHelperConfig($urlHandling);
        $mock         = $this->getMockBuilder(EduSharingNodeHelper::class)
            ->setConstructorArgs([$baseMock, $helperConfig])
            ->onlyMethods(['getSignatureHeaders'])
            ->getMock();
        $mock->expects($this->once())
            ->method('getSignatureHeaders')
            ->will($this->returnValue([]));
        $this->expectException(NodeDeletedException::class);
        $this->expectExceptionMessage('the given node is already deleted');
        $mock->getNodeByUsage(new Usage('nodeId', 'nodeVersion', 'containerId', 'resourceId', 'usageId'));
    }

    /**
     * Function testGetNodeByUsageThrowsExceptionOnErrorCodeOtherThan403Or404
     *
     * @return void
     */
    public function testGetNodeByUsageThrowsExceptionOnErrorCodeOtherThan403Or404(): void {
        $url      = 'https://www.test.de';
        $baseMock = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs([$url, 'pkey123', 'myappid'])
            ->onlyMethods(['handleCurlRequest'])
            ->getMock();
        $baseMock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult('{"testData": "success", "error": "error", "message":"message"}', 0, ['test' => 'hello', 'http_code' => 418])));
        $urlHandling  = new UrlHandling(true, 'https://endpoint.net');
        $helperConfig = new EduSharingNodeHelperConfig($urlHandling);
        $mock         = $this->getMockBuilder(EduSharingNodeHelper::class)
            ->setConstructorArgs([$baseMock, $helperConfig])
            ->onlyMethods(['getSignatureHeaders'])
            ->getMock();
        $mock->expects($this->once())
            ->method('getSignatureHeaders')
            ->will($this->returnValue([]));
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('fetching node by usage failed');
        $mock->getNodeByUsage(new Usage('nodeId', 'nodeVersion', 'containerId', 'resourceId', 'usageId'));
    }

    /**
     * Function testDeleteUsageThrowsJsonExceptionOnInvalidJsonResponse
     *
     * @return void
     */
    public function testDeleteUsageThrowsJsonExceptionOnInvalidJsonResponse(): void {
        $mock = $this->getMockForJsonCheck();
        $this->expectException(JsonException::class);
        $mock->deleteUsage('nodeId', 'usageId');
    }

    /**
     * Function testDeleteUsageReturnsVoidOnSuccessfulCall
     *
     * @return void
     */
    public function testDeleteUsageReturnsVoidOnSuccessfulCall(): void {
        $url      = 'https://www.test.de';
        $baseMock = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs([$url, 'pkey123', 'myappid'])
            ->onlyMethods(['handleCurlRequest'])
            ->getMock();
        $baseMock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult('{"testData": "deleteSuccess"}', 0, ['test' => 'hello', 'http_code' => '200'])));
        $urlHandling  = new UrlHandling(true, 'https://endpoint.net');
        $helperConfig = new EduSharingNodeHelperConfig($urlHandling);
        $mock         = $this->getMockBuilder(EduSharingNodeHelper::class)
            ->setConstructorArgs([$baseMock, $helperConfig])
            ->onlyMethods(['getSignatureHeaders'])
            ->getMock();
        $mock->expects($this->once())
            ->method('getSignatureHeaders')
            ->will($this->returnValue([]));
        try {
            $mock->deleteUsage('nodeId', 'usageId');
            $this->addToAssertionCount(1);
        } catch (Exception $exception) {
            $this->fail('Unexpected exception thrown: ' . $exception->getMessage());
        }
    }

    /**
     * Function testDeleteUsageThrowsUsageDeletedExceptionOnErrorCode404
     *
     * @return void
     */
    public function testDeleteUsageThrowsUsageDeletedExceptionOnErrorCode404(): void {
        $url      = 'https://www.test.de';
        $baseMock = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs([$url, 'pkey123', 'myappid'])
            ->onlyMethods(['handleCurlRequest'])
            ->getMock();
        $baseMock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult('{"testData": "deleteSuccess", "error": "error", "message":"message"}', 1, ['test' => 'hello', 'http_code' => '404'])));
        $urlHandling  = new UrlHandling(true, 'https://endpoint.net');
        $helperConfig = new EduSharingNodeHelperConfig($urlHandling);
        $mock         = $this->getMockBuilder(EduSharingNodeHelper::class)
            ->setConstructorArgs([$baseMock, $helperConfig])
            ->onlyMethods(['getSignatureHeaders'])
            ->getMock();
        $mock->expects($this->once())
            ->method('getSignatureHeaders')
            ->will($this->returnValue([]));
        $this->expectException(UsageDeletedException::class);
        $this->expectExceptionMessage('the given usage is already deleted or does not exist');
        $mock->deleteUsage('nodeId', 'usageId');
    }

    /**
     * Function testDeleteUsageThrowsExceptionOnErrorCodeOtherThan404
     *
     * @return void
     */
    public function testDeleteUsageThrowsExceptionOnErrorCodeOtherThan404(): void {
        $url      = 'https://www.test.de';
        $baseMock = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs([$url, 'pkey123', 'myappid'])
            ->onlyMethods(['handleCurlRequest'])
            ->getMock();
        $baseMock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult('{"testData": "deleteSuccess", "error": "error", "message": "message"}', 1, ['test' => 'hello', 'http_code' => '418'])));
        $urlHandling  = new UrlHandling(true, 'https://endpoint.net');
        $helperConfig = new EduSharingNodeHelperConfig($urlHandling);
        $mock         = $this->getMockBuilder(EduSharingNodeHelper::class)
            ->setConstructorArgs([$baseMock, $helperConfig])
            ->onlyMethods(['getSignatureHeaders'])
            ->getMock();
        $mock->expects($this->once())
            ->method('getSignatureHeaders')
            ->will($this->returnValue([]));
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('deleting usage failed');
        $mock->deleteUsage('nodeId', 'usageId');
    }

    /**
     * Function getMockForJsonCheck
     *
     * @return MockObject
     */
    private function getMockForJsonCheck(): MockObject {
        $url      = 'https://www.test.de';
        $baseMock = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs([$url, 'pkey123', 'myappid'])
            ->onlyMethods(['handleCurlRequest'])
            ->getMock();
        $baseMock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult('{', 0, ['test' => 'hello', 'http_code' => '200'])));
        $urlHandling  = new UrlHandling(true, 'https://endpoint.net');
        $helperConfig = new EduSharingNodeHelperConfig($urlHandling);
        $mock         = $this->getMockBuilder(EduSharingNodeHelper::class)
            ->setConstructorArgs([$baseMock, $helperConfig])
            ->onlyMethods(['getSignatureHeaders'])
            ->getMock();
        $mock->expects($this->once())
            ->method('getSignatureHeaders')
            ->will($this->returnValue([]));
        return $mock;
    }

    /**
     * Function getMockForFailedCurlTest
     *
     * @return MockObject
     */
    private function getMockForFailedCurlTest(): MockObject {
        $url      = 'https://www.test.de';
        $baseMock = $this->getMockBuilder(EduSharingHelperBase::class)
            ->setConstructorArgs([$url, 'pkey123', 'myappid'])
            ->onlyMethods(['handleCurlRequest'])
            ->getMock();
        $baseMock->expects($this->once())
            ->method('handleCurlRequest')
            ->will($this->returnValue(new CurlResult('{"error": "myError", "message": "myMessage"}', 2, ['test' => 'hello', 'http_code' => '500'])));
        $urlHandling  = new UrlHandling(true, 'https://endpoint.net');
        $helperConfig = new EduSharingNodeHelperConfig($urlHandling);
        $mock         = $this->getMockBuilder(EduSharingNodeHelper::class)
            ->setConstructorArgs([$baseMock, $helperConfig])
            ->onlyMethods(['getSignatureHeaders'])
            ->getMock();
        $mock->expects($this->once())
            ->method('getSignatureHeaders')
            ->will($this->returnValue([]));
        return $mock;
    }
}
