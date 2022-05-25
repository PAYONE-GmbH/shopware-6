<?php

declare(strict_types=1);

namespace PayonePayment\Test\Components;

use Doctrine\DBAL\Connection;
use PayonePayment\Components\RedirectHandler\RedirectHandler;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Symfony\Component\Routing\RouterInterface;

class RedirectHandlerTest extends TestCase
{
    use KernelTestBehaviour;
    /*
    - Für manche Zahlungsarten muss eine URL an das Zahlungsportal übergeben werden, auf die dieses dann nach der Zahlung weiterleitet
    - Original sieht diese URL z.B. so aus: /payment/finalize-transaction?_sw_payment_token=<token>&state=success
    - Das ist aber nicht die URL, die an das Zahlungsportal übergeben wird
    - In der "encode" Funktion des RedirectHandler wird
        - anhand der originalen URL ein Hash gebildet
        - der Hash und die originale URL in einer Datenbank Tabelle gespeichert
        - eine allgemeine URL zurückgegeben, die den Hash als Parameter enthält
    - Diese allgemeine URL sieht zum Beispiel so aus: /payone/redirect?hash=<hash>
    - Wenn vom Zahlungsportal auf diese allgemeine URL weitergeleitet wird, wird der Hash an die "decode" Funktion gegeben und diese sucht sich
      anhand des Hashes die originale URL aus der Datenbank
    - Danach wird wiederum auf die originale URL weitergeleitet
    - Dann gibt es noch eine Cleanup Funktion, die regelmäßig von einem Cronjob aufgerufen wird
    - Diese löscht alle Einträge aus der Datenbank Tabelle, die älter als 7 Tage sind
     */

    private string $appSecret;

    protected function setUp(): void
    {
        $this->appSecret = getenv('APP_SECRET');
    }

    protected function tearDown(): void
    {
        putenv('APP_SECRET=' . $this->appSecret);
    }

    public function testEncoding(): void
    {
        $connection = $this->createMock(Connection::class);
        $router = $this->getContainer()->get('router.default');

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
        $this->assertSame(
            'http://localhost/payone/redirect?hash=MWFiMDRkYTZhZmI2NTZmMGFhZmE3NmJjNjJmZWQ2YTQ2ODgyZDU5MTJkMDUwYjI5ZDQyN2VhODJiMmUwYjIwYQ%3D%3D',
            $url
        );
    }

    public function testExceptionOnMissingSecret(): void
    {
        putenv('APP_SECRET=');

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

    public function testMissingUrlOnDecode(): void
    {
        $connection = $this->createStub(Connection::class);
        $router = $this->createStub(RouterInterface::class);

        $connection->method('fetchOne')->willReturn(false);

        $redirectHandler = new RedirectHandler(
            $connection,
            $router
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('no matching url for hash found');
        $redirectHandler->decode('the-hash');
    }

    public function testCleanup(): void
    {

    }
}
