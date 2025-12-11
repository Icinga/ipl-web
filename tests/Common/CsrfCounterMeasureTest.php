<?php

namespace ipl\Tests\Web\Common;

use Error;
use ipl\Html\Contract\FormElement;
use ipl\Html\Form;
use ipl\Html\FormElement\HiddenElement;
use ipl\Tests\Web\TestCase;
use ipl\Web\Common\CsrfCounterMeasure;

class CsrfCounterMeasureTest extends TestCase
{
    public function testTokenCreation()
    {
        $token = $this->createElement();

        $this->assertInstanceOf(HiddenElement::class, $token);
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
}
