<?php

namespace ipl\Web\Common;

use GuzzleHttp\Psr7\ServerRequest;
use InvalidArgumentException;

/**
 * Represents a Content Security Policy (CSP) header.
 * Methods are additive, and duplicate expressions are ignored.
 */
class Csp
{
    /** @var string[] The expressions for the default-src directive */
    protected const DEFAULT_SOURCE_EXPRESSIONS = ["'self'"];

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
     * Create a new CSP from a string
     *
     * @param string $header The CSP header string
     *
     * @return static A new CSP containing all directives from the input header
     */
    public static function fromString(string $header): static
    {
        $header = trim($header);
        $header = str_replace("\r\n", ' ', $header);
        $header = str_replace("\n", ' ', $header);
        $result = new static();
        foreach (explode(';', $header) as $directive) {
            $directive = trim($directive);
            if (empty($directive)) {
                continue;
            }
            $parts = explode(' ', $directive, 2);
            if (count($parts) < 2) {
                throw new InvalidArgumentException(
                    "Directives must contain the directive name and at least one expression."
                );
            }
            $result->add($parts[0], $parts[1]);
        }

        return $result;
    }

    /**
     * Add a directive with a expression or a list of expressions to the CSP
     *
     * @param string $directive The directive name
     * @param string|string[] $value The expression or list of expressions to add
     *
     * @return $this
     */
    public function add(string $directive, string|array $value): static
    {
        if ($directive === "default-src") {
            throw new InvalidArgumentException("Changing default-src is forbidden.");
        }

        if (! preg_match('/^[a-z\-]+$/', $directive)) {
            throw new InvalidArgumentException(
                "Directive names contain only lowercase letters and '-'. Directive: $directive",
            );
        }

        if (is_string($value)) {
            $value = trim($value);

            if (str_contains($value, ' ')) {
                return $this->add($directive, explode(' ', $value));
            }

            if (empty($value)) {
                return $this;
            }

            if (! isset($this->directives[$directive])) {
                $this->directives[$directive] = [];
            }

            if (in_array($value, $this->directives[$directive])) {
                return $this;
            }

            $this->validateExpression($value);

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
        foreach ($this->directives as $directive => $values) {
            $directiveStrings[] = sprintf('%s %s', $directive, implode(' ', $values));
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

    /**
     * Evaluates a URL against a CSP directive.
     * Returns true if the URL is allowed by the directive.
     * This method only checks the URL's scheme and host and path. Nonce and hash are not checked because they can't be
     * represented inside a URL.
     *
     * @param string $directive The CSP directive to evaluate the URL against
     * @param string $url The URL to evaluate
     *
     * @return bool
     */
    public function evaluateUrl(string $directive, string $url): bool
    {
        $parsedUrl = parse_url($url);

        if (! isset($parsedUrl['host'])) {
            throw new InvalidArgumentException("URL must specify a host. URL: $url");
        }

        $expressions = $this->getDirective($directive);

        // 'none' is only supported if it is the only expression.
        // If it is combined with other values, browsers ignore 'none'
        if (count($expressions) === 1 && $expressions[0] === "'none'") {
            return false;
        }

        if (in_array('*', $expressions)) {
            return true;
        }

        $scheme = $parsedUrl['scheme'] ?? null;
        if (in_array("'self'", $expressions)) {
            $requestUri = ServerRequest::getUriFromGlobals();
            if (
                ($scheme === null || $requestUri->getScheme() === $scheme)
                && $requestUri->getHost() === $parsedUrl['host']
            ) {
                return true;
            }
        }

        foreach ($expressions as $expression) {
            if (str_starts_with($expression, "'") && str_ends_with($expression, "'")) {
                continue;
            }

            if ($scheme !== null && ($expression === $scheme . ':' || $expression === $scheme . '://*')) {
                return true;
            }

            $parsedExpressionUrl = parse_url($expression);
            if (! isset($parsedExpressionUrl['scheme']) || ! isset($parsedExpressionUrl['host'])) {
                continue;
            }

            $parsedExpressionPath = $parsedExpressionUrl['path'] ?? null;
            $pathIsDirectory = $parsedExpressionPath !== null && str_ends_with($parsedExpressionPath, '/');
            $parsedPath = $parsedUrl['path'] ?? null;
            if (
                ($scheme === null || $parsedExpressionUrl['scheme'] === $scheme)
                && $parsedExpressionUrl['host'] === $parsedUrl['host']
                && ($parsedExpressionPath === null || (
                    $pathIsDirectory && $parsedPath !== null && str_starts_with($parsedPath, $parsedExpressionPath)
                    || $parsedPath === $parsedExpressionPath
                ))
            ) {
                return true;
            }

            // Note: https://*.example.com means https://example.com and https://sub.example.com
            if (
                ($scheme === null || $parsedExpressionUrl['scheme'] === $scheme)
                && str_starts_with($parsedExpressionUrl['host'], '*')
                && (str_ends_with($parsedUrl['host'], substr($parsedExpressionUrl['host'], 2)))
            ) {
                return true;
            }
        }

        return false;
    }
}
