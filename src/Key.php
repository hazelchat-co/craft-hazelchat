<?php
/**
 * Hazelchat plugin for Craft CMS
 *
 * @copyright 2026 Hazelchat
 * @license MIT; see LICENSE.md
 */

declare(strict_types=1);

namespace hazelchat\craft;

/**
 * The public widget key, the per-site choice and the one script tag, as
 * plain functions with no Craft dependency, so they can be tested with PHP
 * alone. The key is public: it names an agent and grants no access to the
 * Hazelchat account.
 */
final class Key
{
    /** The only address this plugin ever prints. */
    public const SCRIPT_URL = 'https://app.hazelchat.co/widget.js';

    /** Hazelchat counts installations of this plugin by this id. */
    public const SCRIPT_ID = 'hazelchat-craft';

    /** The view's key for the file: the same one always, so one tag per page. */
    public const ASSET_ID = 'hazelchat';

    /** Room for a pasted installation code; the key itself is 64 characters. */
    public const MAX_INPUT = 1000;

    public static function valid(mixed $key): bool
    {
        return \is_string($key) && preg_match('/\A[a-f0-9]{64}\z/', $key) === 1;
    }

    /**
     * An environment variable reference such as $HAZELCHAT_KEY. It is kept
     * as typed and read when a page is rendered, so each environment can
     * use its own code without changing the project config.
     */
    public static function isEnvReference(mixed $value): bool
    {
        return \is_string($value) && preg_match('/\A\$[A-Za-z_][A-Za-z0-9_]*\z/', trim($value)) === 1;
    }

    /**
     * The setting takes the whole installation code or just the key. A bare
     * 64-hex value is the key; otherwise the text must hold exactly one
     * data-agent attribute (outside HTML comments) whose value is 64
     * lowercase hex. Anything else gives null, and nothing is printed.
     */
    public static function fromInput(mixed $value): ?string
    {
        if (!\is_string($value)) {
            return null;
        }
        $value = trim($value);
        if ($value === '' || \strlen($value) > self::MAX_INPUT) {
            return null;
        }
        if (self::valid($value)) {
            return $value;
        }

        // A key that is only mentioned in a comment is not the code.
        $value = preg_replace('/<!--.*?-->/s', '', $value) ?? '';
        if (str_contains($value, '<!--')) {
            return null;
        }

        // Exactly one data-agent attribute; look-alikes such as
        // xdata-agent or data-agent-x are not it, and two are ambiguous.
        $attribute = '(?<![A-Za-z0-9_:-])data-agent(?![A-Za-z0-9_:-])\s*=';
        if (preg_match_all('/' . $attribute . '/i', $value) !== 1) {
            return null;
        }
        if (preg_match('/' . $attribute . '\s*(?:"([a-f0-9]{64})"|\'([a-f0-9]{64})\'|([a-f0-9]{64})(?=[\s>\/]|\z))/', $value, $match) !== 1) {
            return null;
        }

        // One of the three groups (double, single or no quotes) matched.
        $key = implode('', \array_slice($match, 1));

        return self::valid($key) ? $key : null;
    }

    /**
     * What a settings field keeps: '' for an empty field, an environment
     * variable reference as typed, or the key taken from a pasted code.
     * Null means the input is no installation code and must be refused.
     */
    public static function stored(mixed $value): ?string
    {
        if (!\is_string($value)) {
            return null;
        }
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (self::isEnvReference($value)) {
            return $value;
        }

        return self::fromInput($value);
    }

    /**
     * The key for one site. The main code serves every site; a site may
     * have its own code, or be switched off. `$resolve` reads an
     * environment variable reference (Craft's App::parseEnv) and is only
     * called with one.
     *
     * @param array{enabled?: mixed, code?: mixed}|null $site
     * @param callable(string): mixed $resolve
     */
    public static function forSite(mixed $main, ?array $site, callable $resolve): ?string
    {
        if ($site !== null && \array_key_exists('enabled', $site) && !filter_var($site['enabled'], FILTER_VALIDATE_BOOLEAN)) {
            return null;
        }
        $own = $site['code'] ?? '';
        $code = \is_string($own) && trim($own) !== '' ? $own : $main;
        if (!\is_string($code)) {
            return null;
        }
        if (self::isEnvReference($code)) {
            $code = $resolve(trim($code));
        }

        return self::fromInput($code);
    }

    /**
     * The attributes of the script tag, or null when nothing may be printed.
     * No inline code, no data-platform and no address but SCRIPT_URL.
     *
     * @return array<string, string|bool>|null
     */
    public static function attributes(?string $key, bool $requireConsent): ?array
    {
        if (!self::valid($key)) {
            return null;
        }

        $attributes = [
            'id' => self::SCRIPT_ID,
            'data-agent' => $key,
            'defer' => true,
        ];

        if ($requireConsent) {
            $attributes['data-require-consent'] = 'true';
        }

        return $attributes;
    }
}
