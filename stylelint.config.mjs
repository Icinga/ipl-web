import noLessColorRule from './.stylelint/no-less-color-without-css-fallback.mjs';

/** @type {import('stylelint').Config} */
export default {
    extends: ["stylelint-config-standard-less"],
    plugins: [noLessColorRule],
    rules: {
        'custom/no-less-color-without-css-fallback': true,

        "at-rule-empty-line-before": null,
        "comment-empty-line-before": null,
        "custom-property-empty-line-before": null,
        "declaration-empty-line-before": null,
        "rule-empty-line-before": null,
        "font-family-no-missing-generic-family-keyword": null,
        "number-max-precision": null,

        // Ignored for now, re-enable when we have a better understanding of the implications
        "no-descending-specificity": null,
        "no-duplicate-selectors": null,
    }
};
