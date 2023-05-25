<?php

namespace ipl\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;

/**
 * Copy to clipboard button
 */
class CopyToClipboard extends BaseHtmlElement
{
    use Translation;

    protected $tag = 'button';

    protected $defaultAttributes = ['type' => 'button'];

    /**
     * Create a copy to clipboard button
     *
     * Creates a copy to clipboard button, which when clicked copies the text from the html element identified by
     * the target ID. If the target ID is not mentioned then the text from the parent html element is copied.
     */
    private function __construct()
    {
        $this->addAttributes(
            [
                'class'                 => 'copy-to-clipboard',
                'data-icinga-clipboard' => true,
                'data-copied-label'     => $this->translate('Copied'),
                'title'                 => $this->translate('Copy to clipboard'),
            ]
        );
    }

    /**
     * Method to attach the copy to clipboard button to the given source Html element
     *
     * If the source has target ID then it is attached as a sibling else it is attached as a child to the given source
     * Html element
     *
     * @param BaseHtmlElement $source
     *
     * @return BaseHtmlElement
     */
    public static function attachTo(BaseHtmlElement $source): BaseHtmlElement
    {
        $button = new static();
        $clipboardWrapper = new HtmlElement(
            'div',
            Attributes::create(['class' => 'clipboard-wrapper'])
        );

        if ($source->hasAttribute('id')) {
            $button->addAttributes(['data-clipboard-source' => $source->getAttribute('id')->getValue()]);
        } else {
            $button->addAttributes(['data-clipboard-source' => 'parent']);
        }

        $clipboardWrapper->addHtml($source, $button);

        return $clipboardWrapper;
    }

    public function assemble(): void
    {
        $this->setContent(new Icon('clone'));
    }
}
