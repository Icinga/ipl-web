define(["../notjQuery"], function ($) {

    "use strict";

    class CopyToClipboard {
        constructor(button)
        {
            $(button).on('click', null, this.onClick, this);
        }

        onClick(event)
        {
            let button = event.currentTarget;
            let clipboardSource = button.dataset.clipboardSource;
            let copyText;

            if (clipboardSource) {
                if (clipboardSource === 'parent') {
                    copyText = button.parentElement.innerText;
                } else {
                    let el = document.getElementById(clipboardSource);
                    if (! el) {
                        throw new Error('Clipboard source element with id  "' + clipboardSource + '" is not defined');
                    } else {
                        copyText = el.innerText;
                    }
                }
            } else {
                throw new Error('Clipboard source is required but not provided');
            }

            try {
                navigator.clipboard.writeText(copyText).then(() => {
                    let previousHtml = button.innerHTML;
                    button.innerHTML = button.dataset.copiedLabel;

                    button.classList.add('copied');
                    // after 1 second, reset it.
                    setTimeout(() => {
                        button.classList.remove('copied');

                        button.innerHTML = previousHtml;
                    }, 1000);
                }).catch((err) => {
                    console.error('Failed to copy: ', err);
                });
            } catch (err) {
                console.error('Copy to clipboard requires HTTPS connection: ', err);
            }

            event.stopPropagation();
            event.preventDefault();
        }
    }

    return CopyToClipboard;
});
