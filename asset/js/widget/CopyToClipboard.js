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
            let clipboardSource = button.parentElement.querySelector("[data-clipboard-source]");
            let copyText;

            if (clipboardSource) {
                copyText = clipboardSource.innerText;
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
