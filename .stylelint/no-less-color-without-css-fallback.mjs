import stylelint from "stylelint";
import valueParser from "postcss-value-parser";

const ruleName = "custom/no-less-color-without-css-fallback";

const messages = stylelint.utils.ruleMessages(ruleName, {
    rejected: (variable) =>
        `LESS variable "${variable}" must be used as fallback in var(--${variable.slice(1)}, ${variable})`,
});

const colorOnlyProps = new Set([
    "color",
    "background-color",
    "border-color",
    "border-top-color",
    "border-right-color",
    "border-bottom-color",
    "border-left-color",
    "outline-color",
    "text-decoration-color",
    "column-rule-color",
    "caret-color",
    "stop-color",
    "flood-color",
    "lighting-color",
    "fill",
    "stroke",
    "scrollbar-color",
]);

const shorthandColorProps = new Set([
    "background",
    "border",
    "border-top",
    "border-right",
    "border-bottom",
    "border-left",
    "outline",
    "text-decoration",
    "column-rule",
    "border-block",
    "border-inline",
    "border-start",
    "border-end",
    "box-shadow",
    "text-shadow",
    "filter",
    "drop-shadow", // used inside `filter`
]);

function getMixinArgs(rule) {
    if (!rule || !rule.selector) return [];
    if (!rule.selector.startsWith(".") || !rule.selector.includes("(")) return [];

    const argsMatch = rule.selector.match(/\(([^)]*)\)/);
    if (!argsMatch) return [];

    return argsMatch[1]
        .split(",")
        .map((arg) => arg.trim())
        .filter((arg) => arg.startsWith("@"));
}

const rule = (enabled, _options, _context) => {
    return (root, result) => {
        if (!enabled) return;

        root.walkDecls((decl) => {
            const prop = decl.prop;
            const isColorOnly = colorOnlyProps.has(prop);
            const isShorthand = shorthandColorProps.has(prop);

            if (!isColorOnly && !isShorthand) return;

            const parsed = valueParser(decl.value);

            // Gather all LESS variables used
            const lessVars = [];
            parsed.walk((node) => {
                if (node.type === "word" && node.value.startsWith("@")) {
                    lessVars.push(node.value);
                }
            });

            if (lessVars.length === 0) return;

            // Get mixin arguments
            let parentRule = decl.parent;
            while (parentRule && parentRule.type !== "rule") {
                parentRule = parentRule.parent;
            }
            const mixinArgs = getMixinArgs(parentRule);

            // Decide which LESS variables to check
            const varsToCheck = isColorOnly
                ? lessVars
                : [lessVars[lessVars.length - 1]];

            for (const full of varsToCheck) {
                if (mixinArgs.includes(full)) continue;

                const name = full.slice(1);
                const expected = `var(--${name}, ${full})`;
                const alreadyCompliant = new RegExp(
                    `var\\(\\s*--${name}\\s*,\\s*${full.replace(/[-/\\^$*+?.()|[\]{}]/g, "\\$&")}\\s*\\)`
                );
                if (alreadyCompliant.test(decl.value)) continue;

                stylelint.utils.report({
                    message: messages.rejected(full),
                    node: decl,
                    result,
                    ruleName,
                    word: full,
                    fix: () => {
                        const escapedVar = full.replace(/[-/\\^$*+?.()|[\]{}]/g, "\\$&");
                        const exactVar = new RegExp(escapedVar, "g");
                        decl.value = decl.value.replace(exactVar, expected);
                    },
                });
            }
        });
    };
};

rule.meta = {
    fixable: true
}

export default {
    ruleName,
    rule
};
