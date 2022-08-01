<?php

declare(strict_types=1);

namespace PayonePayment\Components\RedirectHandler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Symfony\Component\Routing\RouterInterface;

class RedirectHandlerTest extends TestCase
{
    use KernelTestBehaviour;

    private string $appSecret;

    protected function setUp(): void
    {
        $this->appSecret = getenv('APP_SECRET');
    }

    protected function tearDown(): void
    {
        putenv('APP_SECRET=' . $this->appSecret);
    }

    public function testItEncodesUrlWithoutDatabase(): void
    {
        $connection = $this->createMock(Connection::class);
        $router     = $this->getContainer()->get('router.default');

        $connection->expects($this->once())->method('insert')->with(
            'payone_payment_redirect',
            $this->callback(
                static function (array $parameters): bool {
                    return $parameters['hash'] === 'MWFiMDRkYTZhZmI2NTZmMGFhZmE3NmJjNjJmZWQ2YTQ2ODgyZDU5MTJkMDUwYjI5ZDQyN2VhODJiMmUwYjIwYQ==' &&
                        $parameters['url'] === 'the-url';
                }
            )
        );

        $redirectHandler = new RedirectHandler(
            $connection,
            $router
        );

        $url = $redirectHandler->encode('the-url');

        static::assertSame(
            'http://localhost/payone/redirect?hash=MWFiMDRkYTZhZmI2NTZmMGFhZmE3NmJjNjJmZWQ2YTQ2ODgyZDU5MTJkMDUwYjI5ZDQyN2VhODJiMmUwYjIwYQ%3D%3D',
            $url
        );
    }

    public function testItEncodesUrlWithDatabase(): array
    {
        $connection = $this->getContainer()->get(Connection::class);
        $router     = $this->getContainer()->get('router.default');

        $redirectHandler = new RedirectHandler(
            $connection,
            $router
        );

        $originalUrl = 'the-url';
        $redirectUrl = $redirectHandler->encode($originalUrl);

        $this->assertSame(
            'http://localhost/payone/redirect?hash=MWFiMDRkYTZhZmI2NTZmMGFhZmE3NmJjNjJmZWQ2YTQ2ODgyZDU5MTJkMDUwYjI5ZDQyN2VhODJiMmUwYjIwYQ%3D%3D',
            $redirectUrl
        );

        $hash = 'MWFiMDRkYTZhZmI2NTZmMGFhZmE3NmJjNjJmZWQ2YTQ2ODgyZDU5MTJkMDUwYjI5ZDQyN2VhODJiMmUwYjIwYQ==';
        $query = 'SELECT url FROM payone_payment_redirect WHERE hash = ?';
        $foundOriginalUrl = $this->fetchOne($connection, $query, [$hash]);

        static::assertSame($originalUrl, $foundOriginalUrl);

        return ['hash' => $hash, 'originalUrl' => $originalUrl];
    }

    /**
     * @depends testItEncodesUrlWithDatabase
     */
    public function testItDecodesHashWithDatabase(array $data): void
    {
        $connection = $this->getContainer()->get(Connection::class);
        $router     = $this->getContainer()->get('router.default');

        $redirectHandler = new RedirectHandler(
            $connection,
            $router
        );

        $originalUrl = $redirectHandler->decode($data['hash']);

        static::assertSame(
            $data['originalUrl'],
            $originalUrl
        );
    }

    public function testItThrowsExceptionOnEncodingWithMissingSecret(): void
    {
        putenv('APP_SECRET=');

        $connection = $this->createMock(Connection::class);
        $router     = $this->getContainer()->get('router.default');

        $redirectHandler = new RedirectHandler(
            $connection,
            $router
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('empty app secret');

        $redirectHandler->encode('the-url');
    }

    public function testItThrowsExceptionOnDecodingWithMissingUrl(): void
    {
        $connection = $this->createStub(Connection::class);
        $router     = $this->createStub(RouterInterface::class);

        $connection->method('fetchOne')->willReturn(false);

        $redirectHandler = new RedirectHandler(
            $connection,
            $router
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('no matching url for hash found');

        $redirectHandler->decode('the-hash');
    }

    /**
     * @depends testItEncodesUrlWithDatabase
     */
    public function testItCleansUpOldUrls(): void
    {
        $connection = $this->getContainer()->get(Connection::class);
        $router     = $this->getContainer()->get('router.default');

        $redirectHandler = new RedirectHandler(
            $connection,
            $router
        );

        $redirectHandler->encode('the-url-2');

        $countQuery = 'SELECT COUNT(*) FROM payone_payment_redirect';
        $redirectCount = (int) $this->fetchOne($connection, $countQuery);

        static::assertSame(2, $redirectCount);

        $connection->executeStatement(
            'UPDATE payone_payment_redirect SET created_at = ? WHERE url = ?',
            [new \DateTime('-8 day'), 'the-url-2'],
            [Types::DATETIME_MUTABLE, Types::STRING]
        );

        $redirectHandler->cleanup();

        $redirectCount = (int) $this->fetchOne($connection, $countQuery);

        static::assertSame(1, $redirectCount);
    }

    private function fetchOne(Connection $connection, string $query, array $params = [])
    {
        // Shopware >= x
        if (method_exists($connection, 'fetchOne')) {
            return $connection->fetchOne($query, $params);
        }

        /** @noinspection PhpDeprecationInspection */
        return $connection->fetchColumn($query, $params);
    }
}
