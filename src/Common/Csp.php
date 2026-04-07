<?php

namespace ipl\Web\Common;

use GuzzleHttp\Psr7\ServerRequest;
use InvalidArgumentException;

/**
 * Represents a Content Security Policy (CSP) header.
 * Methods are additive, and duplicate policies are ignored.
 */
class Csp
{
    /** @var string[] The default source directive */
    protected const DEFAULT_SOURCE_DIRECTIVE = ["'self'"];

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
                continue;
            }
            $result->add($parts[0], $parts[1]);
        }

        return $result;
    }

    /**
     * Add a directive with a policy or a list of policies to the CSP
     *
     * @param string $directive The directive name
     * @param string|string[] $value The policy or list of policies to add
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

            $this->validatePolicy($value);

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
     * @return string[] The policies of the directive or the default-src directive if none is set explicitly
     */
    public function getDirective(string $directive): array
    {
        return $this->directives[$directive] ?? static::DEFAULT_SOURCE_DIRECTIVE;
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
        $directiveStrings = ["default-src " . implode(' ', static::DEFAULT_SOURCE_DIRECTIVE)];
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
     * Validate a policy. Throws an exception if the policy is invalid.
     *
     * @param string $policy The policy to validate
     *
     * @return void
     */
    protected function validatePolicy(string $policy): void
    {
        if ($policy === '*') {
            return;
        }

        if (
            (str_starts_with($policy, "'") && ! str_ends_with($policy, "'"))
            || ! str_starts_with($policy, "'") && str_ends_with($policy, "'")
        ) {
            throw new InvalidArgumentException(
                "Quoted policy must be fully surrounded by single quotes. policy: $policy",
            );
        }

        if (str_starts_with($policy, "'") && str_ends_with($policy, "'")) {
            return;
        }

        // scheme and scheme://*
        if (preg_match('/^[a-z]+:(\/\/\*)?$/', $policy)) {
            return;
        }

        // Reporting names
        if (preg_match('/^[a-zA-Z0-9_-]+$/', $policy)) {
            return;
        }

        $parsedUrl = parse_url($policy);
        if ($parsedUrl === false) {
            throw new InvalidArgumentException("Policy must be a valid URL. policy: $policy");
        }

        if (! isset($parsedUrl['host'])) {
            throw new InvalidArgumentException("Policy URL must specify a host. policy: $policy");
        }

        if (! isset($parsedUrl['scheme'])) {
            throw new InvalidArgumentException("Policy URL must specify a scheme. policy: $policy");
        }

        if (str_starts_with($parsedUrl['host'], '*')) {
            if (! str_starts_with($parsedUrl['host'], '*.')) {
                throw new InvalidArgumentException("Wildcard host must be a full subdomain. policy: $policy");
            }
        } else {
            if (str_contains($parsedUrl['host'], '*')) {
                throw new InvalidArgumentException("Wildcards can only be used at the start of the host. policy: $policy");
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
        $policies = $this->getDirective($directive);

        // 'none' is only supported if it is the only policy.
        // If it is combined with other values, browsers ignore 'none'
        if (count($policies) === 1 && $policies[0] === "'none'") {
            return false;
        }

        if (in_array('*', $policies)) {
            return true;
        }

        $parsedUrl = parse_url($url);
        $scheme = $parsedUrl['scheme'] ?? null;
        if (in_array("'self'", $policies)) {
            $requestUri = ServerRequest::getUriFromGlobals();
            if (
                ($scheme === null || $requestUri->getScheme() === $scheme)
                && $requestUri->getHost() === $parsedUrl['host']
            ) {
                return true;
            }
        }

        foreach ($policies as $policy) {
            if (str_starts_with($policy, "'") && str_ends_with($policy, "'")) {
                continue;
            }

            if ($scheme !== null && ($policy === $scheme . ':' || $policy === $scheme . '://')) {
                return true;
            }

            $parsedPolicyUrl = parse_url($policy);
            if (! isset($parsedPolicyUrl['scheme']) || ! isset($parsedPolicyUrl['host'])) {
                continue;
            }

            $parsedPolicyPath = $parsedPolicyUrl['path'] ?? null;
            $pathIsDirectory = $parsedPolicyPath !== null && str_ends_with($parsedPolicyPath, '/');
            $parsedPath = $parsedUrl['path'] ?? null;
            if (
                ($scheme === null || $parsedPolicyUrl['scheme'] === $scheme)
                && $parsedPolicyUrl['host'] === $parsedUrl['host']
                && ($parsedPolicyPath === null || (
                    $pathIsDirectory && $parsedPath !== null && str_starts_with($parsedPath, $parsedPolicyPath)
                    || $parsedPath === $parsedPolicyPath
                ))
            ) {
                return true;
            }

            // Note: https://*.example.com means https://example.com and https://sub.example.com
            if (
                ($scheme === null || $parsedPolicyUrl['scheme'] === $scheme)
                && str_starts_with($parsedPolicyUrl['host'], '*')
                && (str_ends_with($parsedUrl['host'], substr($parsedPolicyUrl['host'], 2)))
            ) {
                return true;
            }
        }

        return false;
    }
}
