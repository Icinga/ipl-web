<?php

namespace ipl\Web\Common;

use InvalidArgumentException;

/**
 * Represents a Content Security Policy (CSP) header.
 * Methods are additive, and duplicate expressions are ignored.
 */
class Csp
{
    /** @var string[] The expressions for the default-src directive */
    protected const DEFAULT_SOURCE_EXPRESSIONS = ["'self'"];

    /** @var string[] The directives that can be empty */
    protected const POSSIBLE_EMPTY_DIRECTIVES = [
        'sandbox',
    ];

    /** @var string[] The directives that must be empty */
    protected const MANDATORY_EMPTY_DIRECTIVES = [
        'block-all-mixed-content',
        'upgrade-insecure-requests',
    ];

    /**
     * @var array<string, array<string>> The directives and their values
     */
    protected array $directives = [];

    /**
     * @var string|null The first nonce found in the directives.
     * Note: This assumes there will only ever be one nonce.
     */
    protected ?string $nonce = null;

    /**
     * Create a new CSP by merging multiple CSPs.
     *
     * @param Csp ...$csps The CSPs to merge
     *
     * @return static A new CSP containing all directives from the input CSPs
     */
    public static function merge(Csp ...$csps): static
    {
        $result = new static();
        foreach ($csps as $csp) {
            foreach ($csp->directives as $directive => $values) {
                if ($directive === 'default-src') {
                    continue;
                }
                $result->add($directive, $values);
            }
        }
        return $result;
    }

    /**
     * Only a subset of directives can be empty. Allowing them to be empty does not mean they cannot have a value,
     * only that it can be omitted.
     * @param string $directive The directive name
     *
     * @return bool
     */
    protected function canDirectiveBeEmpty(string $directive): bool
    {
        return in_array($directive, static::POSSIBLE_EMPTY_DIRECTIVES, true)
            || in_array($directive, static::MANDATORY_EMPTY_DIRECTIVES, true);
    }

    /**
     * Create a new CSP from a string
     *
     * @param string $header The CSP header string
     *
     * @return static A new CSP containing all directives from the input header
     */
    public static function fromString(string $header): static
    {
        $header = str_replace("\r\n", ' ', $header);
        $header = str_replace("\n", ' ', $header);
        $result = new static();
        foreach (explode(';', $header) as $directive) {
            $directive = trim($directive);
            if (empty($directive)) {
                continue;
            }
            $parts = explode(' ', $directive, 2);
            $name = $parts[0];
            if (count($parts) == 1) {
                if (! $result->canDirectiveBeEmpty($name)) {
                    throw new InvalidArgumentException(
                        "Directives must contain the directive name and at least one expression. Directive: $directive"
                    );
                }
                $result->add($name, null);
            } else {
                $result->add($parts[0], $parts[1]);
            }
        }

        return $result;
    }

    /**
     * Add a directive with a expression or a list of expressions to the CSP
     *
     * @param string $directive The directive name
     * @param string|string[]|null $value The expression or list of expressions to add
     *
     * @return $this
     */
    public function add(string $directive, string|array|null $value): static
    {
        if ($directive === "default-src") {
            throw new InvalidArgumentException("Changing default-src is forbidden.");
        }

        if (! preg_match('/^[a-z\-]+$/', $directive)) {
            throw new InvalidArgumentException(
                "Directive names contain only lowercase letters and '-'. Directive: $directive",
            );
        }

        if ($value !== null && in_array($directive, static::MANDATORY_EMPTY_DIRECTIVES, true)) {
            throw new InvalidArgumentException(
                "Directive $directive can not have a value."
            );
        }

        if ($value == null) {
            if (! $this->canDirectiveBeEmpty($directive)) {
                throw new InvalidArgumentException(
                    "Directive $directive can not be empty."
                );
            }
            $this->directives[$directive] ??= [];
        } elseif (is_string($value)) {
            $value = trim($value);

            if (str_contains($value, ' ')) {
                return $this->add($directive, explode(' ', $value));
            }

            if (empty($value)) {
                return $this;
            }

            $this->validateExpression($value);

            if (in_array($value, $this->directives[$directive] ?? [])) {
                return $this;
            }

            $this->directives[$directive] ??= [];

            $this->directives[$directive][] = $value;

            if (
                $this->nonce === null
                && str_starts_with($value, "'nonce-")
                && str_ends_with($value, "'")
            ) {
                $nonce = substr($value, 7, -1);
                if (empty($nonce)) {
                    throw new InvalidArgumentException("Nonce must have a value.");
                }

                $this->nonce = $nonce;
            }
        } else {
            foreach ($value as $v) {
                $this->add($directive, $v);
            }
        }

        return $this;
    }

    /**
     * @return string|null The first nonce found in the directives.
     */
    public function getNonce(): ?string
    {
        return $this->nonce;
    }

    /**
     * Get the values of a directive
     *
     * @param string $directive The directive name
     *
     * @return string[] The expressions of the directive or the default-src directive if none is set explicitly
     */
    public function getDirective(string $directive): array
    {
        return $this->directives[$directive] ?? static::DEFAULT_SOURCE_EXPRESSIONS;
    }

    /**
     * Get all directives
     *
     * @return array<string, array<string>>
     */
    public function getDirectives(): array
    {
        return $this->directives;
    }

    /**
     * Get the fully formated CSP header string.
     * This can be used directly in the Content-Security-Policy header.
     *
     * @return string The CSP header string
     */
    public function getHeader(): string
    {
        $directiveStrings = ["default-src " . implode(' ', static::DEFAULT_SOURCE_EXPRESSIONS)];
        foreach ($this->directives as $directive => $expressions) {
            $directiveStrings[] = implode(' ', array_merge([$directive], $expressions));
        }
        return implode('; ', $directiveStrings);
    }

    public function __toString(): string
    {
        return $this->getHeader();
    }

    public function isEmpty(): bool
    {
        return empty($this->directives);
    }

    /**
     * Validate an expression. Throws an exception if the expression is invalid.
     *
     * @param string $expression The expression to validate
     *
     * @return void
     */
    protected function validateExpression(string $expression): void
    {
        if ($expression === '') {
            throw new InvalidArgumentException("Expression must not be empty.");
        }

        if ($expression === '*') {
            return;
        }

        if (
            (str_starts_with($expression, "'") && ! str_ends_with($expression, "'"))
            || ! str_starts_with($expression, "'") && str_ends_with($expression, "'")
        ) {
            throw new InvalidArgumentException(
                "Quoted expression must be fully surrounded by single quotes. Expression: $expression",
            );
        }

        if (str_starts_with($expression, "'") && str_ends_with($expression, "'")) {
            return;
        }

        // scheme: and scheme://*
        if (preg_match('/^[a-z]+:(\/\/\*)?$/', $expression)) {
            return;
        }

        // Reporting names
        if (preg_match('/^[a-zA-Z0-9_-]+$/', $expression)) {
            return;
        }

        $parsedUrl = parse_url($expression);
        if ($parsedUrl === false) {
            throw new InvalidArgumentException("Expression must be a valid URL. Expression: $expression");
        }

        if (! isset($parsedUrl['host'])) {
            throw new InvalidArgumentException("Expression URL must specify a host. Expression: $expression");
        }

        if (! isset($parsedUrl['scheme'])) {
            throw new InvalidArgumentException("Expression URL must specify a scheme. Expression: $expression");
        }

        if (str_starts_with($parsedUrl['host'], '*')) {
            if (! str_starts_with($parsedUrl['host'], '*.')) {
                throw new InvalidArgumentException("Wildcard host must be a full subdomain. Expression: $expression");
            }
        } else {
            if (str_contains($parsedUrl['host'], '*')) {
                throw new InvalidArgumentException(
                    "Wildcards can only be used at the start of the host. Expression: $expression",
                );
            }
        }
    }
}
