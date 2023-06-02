<?php

declare(strict_types=1);

namespace PayonePayment\Components\RedirectHandler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Symfony\Component\Routing\RouterInterface;

/**
 * @covers \PayonePayment\Components\RedirectHandler\RedirectHandler
 */
class RedirectHandlerTest extends TestCase
{
    use KernelTestBehaviour;

    public function testItEncodesUrlWithoutDatabase(): void
    {
        $connection = $this->createMock(Connection::class);
        $router = $this->getContainer()->get('router.default');

        $connection->expects(static::once())->method('insert')->with(
            'payone_payment_redirect',
            static::callback(
                static function (array $parameters): bool {
                    return $parameters['hash'] === 'MWFiMDRkYTZhZmI2NTZmMGFhZmE3NmJjNjJmZWQ2YTQ2ODgyZDU5MTJkMDUwYjI5ZDQyN2VhODJiMmUwYjIwYQ=='
                        && $parameters['url'] === 'the-url';
                }
            )
        );

        $redirectHandler = new RedirectHandler(
            $connection,
            $router,
            $this->getContainer()->getParameter('env.app_secret')
        );

        $url = $redirectHandler->encode('the-url');

        static::assertStringEndsWith(
            '/payone/redirect?hash=MWFiMDRkYTZhZmI2NTZmMGFhZmE3NmJjNjJmZWQ2YTQ2ODgyZDU5MTJkMDUwYjI5ZDQyN2VhODJiMmUwYjIwYQ%3D%3D',
            $url
        );
    }

    public function testItEncodesUrlWithDatabase(): array
    {
        $connection = $this->getContainer()->get(Connection::class);
        $router = $this->getContainer()->get('router.default');

        $redirectHandler = new RedirectHandler(
            $connection,
            $router,
            $this->getContainer()->getParameter('env.app_secret')
        );

        $originalUrl = 'the-url';
        $redirectUrl = $redirectHandler->encode($originalUrl);

        static::assertStringEndsWith(
            '/payone/redirect?hash=MWFiMDRkYTZhZmI2NTZmMGFhZmE3NmJjNjJmZWQ2YTQ2ODgyZDU5MTJkMDUwYjI5ZDQyN2VhODJiMmUwYjIwYQ%3D%3D',
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
        $router = $this->getContainer()->get('router.default');

        $redirectHandler = new RedirectHandler(
            $connection,
            $router,
            $this->getContainer()->getParameter('env.app_secret')
        );

        $originalUrl = $redirectHandler->decode($data['hash']);

        static::assertSame(
            $data['originalUrl'],
            $originalUrl
        );
    }

    public function testItThrowsExceptionOnEncodingWithMissingSecret(): void
    {
        $connection = $this->createMock(Connection::class);
        $router = $this->getContainer()->get('router.default');

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
        $router = $this->createStub(RouterInterface::class);

        $connection->method('fetchOne')->willReturn(false);

        $redirectHandler = new RedirectHandler(
            $connection,
            $router,
            $this->getContainer()->getParameter('env.app_secret')
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
        $router = $this->getContainer()->get('router.default');

        $redirectHandler = new RedirectHandler(
            $connection,
            $router,
            $this->getContainer()->getParameter('env.app_secret')
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
        if (method_exists($connection, 'fetchOne')) {
            return $connection->fetchOne($query, $params);
        }

        /** @noinspection PhpDeprecationInspection */
        return $connection->fetchColumn($query, $params);
    }
}
