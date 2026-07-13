<?php

namespace ipl\Tests\Web\Common;

use Error;
use ipl\Html\Contract\FormElement;
use ipl\Html\Form;
use ipl\Html\FormElement\HiddenElement;
use ipl\Stdlib\Contract\Validator;
use ipl\Tests\Web\TestCase;
use ipl\Web\Common\CsrfCounterMeasure;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class CsrfCounterMeasureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        unset($_SERVER['HTTP_SEC_FETCH_SITE'], $_SERVER['REQUEST_METHOD']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($_SERVER['HTTP_SEC_FETCH_SITE'], $_SERVER['REQUEST_METHOD']);
    }

    public function testTokenCreation()
    {
        $token = $this->createElement();

        $this->assertInstanceOf(HiddenElement::class, $token);
        $this->assertNotCount(0, $token->getValidators(), 'Token element must have at least one validator');
        $this->assertMatchesRegularExpression(
            '/ value="[^"]+\|[^"]+"/',
            (string) $token,
            'The value is not rendered or does not contain a seed and a hash'
        );
    }

    public function testMissingToken()
    {
        $token = $this->createElement();

        $this->assertNull($token->getValue(), 'The default value must only be set after the form is rendered');

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Invalid CSRF token provided');

        $token->isValid();
    }

    public function testValidToken()
    {
        $token = $this->createElement();

        $this->assertSame(1, preg_match('/ value="([^"]+)"/', (string) $token, $matches));

        $token->setValue($matches[1]);
        $this->assertTrue($token->isValid(), 'Token should be valid with the default value');
    }

    public function testInvalidToken()
    {
        $token = $this->createElement();

        $token->setValue('invalid');

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Invalid CSRF token provided');

        $token->isValid();
    }

    public function testUnsafeCrossSiteRequestIsRejected(): void
    {
        $_SERVER['HTTP_SEC_FETCH_SITE'] = 'cross-site';

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Rejecting cross-site request');

        $this->makeForm()->handleRequest($this->requestMock('POST'));
    }

    public function testCreateReturnsDummyElementForSafeRequest(): void
    {
        $_SERVER['HTTP_SEC_FETCH_SITE'] = 'same-origin';

        $element = $this->makeForm()->callCreate('uniqueId');
        $this->assertInstanceOf(HiddenElement::class, $element);

        $validatorMock = $this->createMock(Validator::class);
        $validatorMock->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $element->getValidators()->add($validatorMock);

        $this->assertTrue($element->isValid(), 'Dummy element must be successfully validated');
    }

    public function testCreateReturnsElementWithTokenForIndistinguishableRequests(): void
    {
        $_SERVER['HTTP_SEC_FETCH_SITE'] = 'none';

        $element = $this->makeForm()->callCreate('uniqueId');
        $this->assertInstanceOf(HiddenElement::class, $element);

        $element->setValue(base64_encode('seed') . '|' . hash('sha3-256', 'uniqueIdseed'));

        $validatorMock = $this->createMock(Validator::class);
        $validatorMock->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $element->getValidators()->add($validatorMock);

        $this->assertTrue($element->isValid(), 'Dummy element must be successfully validated');
    }

    public static function safeMethodProvider(): array
    {
        return [
            'GET'     => ['GET'],
            'HEAD'    => ['HEAD'],
            'OPTIONS' => ['OPTIONS'],
            'TRACE'   => ['TRACE'],
        ];
    }

    #[DataProvider('safeMethodProvider')]
    public function testSafeCrossSiteRequestIsNotValidated(string $method): void
    {
        $_SERVER['HTTP_SEC_FETCH_SITE'] = 'cross-site';

        $flag = false;

        $form = $this->makeForm();
        $form->on(Form::ON_REQUEST, function () use (&$flag) {
            $flag = true;
        });
        $form->handleRequest($this->requestMock($method));

        $this->assertTrue($flag, 'Form did not emit ON_REQUEST event for method ' . $method);
    }

    public function testValidationThrowsForCrossSiteRequests(): void
    {
        $_SERVER['HTTP_SEC_FETCH_SITE'] = 'cross-site';

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Rejecting cross-site request');

        $this->makeForm()->isValid();
    }

    private function createElement(): FormElement
    {
        $form = new class extends Form {
            use CsrfCounterMeasure;

            protected function assemble()
            {
                $this->addCsrfCounterMeasure();
            }
        };

        return $form->setCsrfCounterMeasureId('uniqueId')
            ->ensureAssembled()
            ->getElement('CSRFToken');
    }

    private function makeForm()
    {
        return new class extends Form {
            use CsrfCounterMeasure;

            public function callCreate(string $id): FormElement
            {
                return $this->createCsrfCounterMeasure($id);
            }

            protected function assemble(): void
            {
                $this->addCsrfCounterMeasure('uniqueId');
            }
        };
    }

    private function requestMock(string $method): ServerRequestInterface
    {
        $mock = $this->createMock(ServerRequestInterface::class);
        $mock->method('getMethod')->willReturn($method);
        $mock->method('getParsedBody')->willReturn([]);
        $mock->method('getUploadedFiles')->willReturn([]);
        $mock->method('getUri')->willReturn($this->createConfiguredMock(
            UriInterface::class,
            ['getQuery' => '']
        ));

        return $mock;
    }
}
