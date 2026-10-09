<?php

namespace ipl\Web\FormElement;

use ipl\Html\FormElement\FormElements;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A term input
 *
 * For backwards compatibility with TermInputs inside fieldsets, {@see static::ON_ENRICH} is emitted before rendering
 * in case the input has been auto-submitted, but {@see static::prepareMultipartUpdate()} was never called.
 *
 * @deprecated Use {@see TermInputElement}, or actually, use {@see FormElements::createElement()} instead
 */
class TermInput extends TermInputElement
{
    /** @var bool Whether updates for a multipart response have been prepared */
    private bool $multipartUpdatePrepared = false;

    public function prepareMultipartUpdate(ServerRequestInterface $request): array
    {
        $this->multipartUpdatePrepared = true;

        return parent::prepareMultipartUpdate($request);
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();

        if ($this->hasBeenAutoSubmitted() && ! $this->multipartUpdatePrepared) {
            $this->emit(static::ON_ENRICH, [$this->getTerms()]);
        }
    }
}
