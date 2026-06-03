<?php

namespace ipl\Tests\Web\Common;

use Error;
use ipl\Html\Contract\FormElement;
use ipl\Html\Form;
use ipl\Html\FormElement\HiddenElement;
use ipl\Tests\Web\TestCase;
use ipl\Web\Common\CsrfCounterMeasure;
use PHPUnit\Framework\Attributes\DataProvider;

class CsrfCounterMeasureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        unset($_SERVER['HTTP_SEC_FETCH_SITE']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($_SERVER['HTTP_SEC_FETCH_SITE']);
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

    public static function safeHeaderValueProvider(): array
    {
        return [
            'same-origin' => ['same-origin'],
            'none'        => ['none'],
        ];
    }

    public function testAddThrowsForCrossSiteRequest(): void
    {
        $_SERVER['HTTP_SEC_FETCH_SITE'] = 'cross-site';

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Rejecting cross-site request');

        $this->makeForm()->ensureAssembled();
    }

    #[DataProvider('safeHeaderValueProvider')]
    public function testCreateReturnsDummyElementForSafeRequest(string $headerValue): void
    {
        $_SERVER['HTTP_SEC_FETCH_SITE'] = $headerValue;

        $element = $this->makeForm()->callCreate('uniqueId');

        $this->assertInstanceOf(HiddenElement::class, $element);
        $this->assertNotCount(0, $element->getValidators(), 'Dummy element must have at least one validator');
        $element->setValue('garbage');
        $this->assertTrue($element->isValid(), 'Dummy element should accept any value without validation');
    }

    public function testCreateThrowsForCrossSiteRequest(): void
    {
        $_SERVER['HTTP_SEC_FETCH_SITE'] = 'cross-site';

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Rejecting cross-site request');

        $this->makeForm()->callCreate('uniqueId');
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
}
