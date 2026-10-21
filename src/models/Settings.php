<?php
/**
 * Hazelchat plugin for Craft CMS
 *
 * @copyright 2026 Hazelchat
 * @license MIT; see LICENSE.md
 */

declare(strict_types=1);

namespace hazelchat\craft\models;

use Craft;
use craft\base\Model;
use craft\helpers\App;
use hazelchat\craft\Key;

/**
 * The plugin's settings, kept in the project config like every plugin's.
 * Only the key of a pasted installation code is kept, or an environment
 * variable reference as typed.
 */
class Settings extends Model
{
    /** The code every site uses: a key, or a reference such as $HAZELCHAT_KEY. */
    public string $installationCode = '';

    /** Adds data-require-consent="true": the chat waits for the consent manager. */
    public bool $waitForConsent = false;

    /**
     * Per site, keyed by the site's UID (the same in every environment):
     * `enabled` (default on) and an own `code` (empty: the main code).
     * Posted form values arrive here before validation, so any shape is
     * possible until validateSites() has run.
     *
     * @var array<array-key, mixed>
     */
    public array $sites = [];

    public function setAttributes($values, $safeOnly = true): void
    {
        // A lightswitch posts '1' or ''.
        if (\is_array($values) && \array_key_exists('waitForConsent', $values)) {
            $values['waitForConsent'] = filter_var($values['waitForConsent'], FILTER_VALIDATE_BOOLEAN);
        }
        parent::setAttributes($values, $safeOnly);
    }

    protected function defineRules(): array
    {
        return [
            [['waitForConsent'], 'boolean'],
            [['installationCode'], 'validateCode'],
            [['sites'], 'validateSites'],
        ];
    }

    public function validateCode(string $attribute): void
    {
        $error = null;
        $this->$attribute = $this->normalize($this->$attribute, $error);
        if ($error !== null) {
            $this->addError($attribute, $error);
        }
    }

    public function validateSites(string $attribute): void
    {
        $sites = [];
        foreach ($this->sites as $uid => $site) {
            if (!\is_string($uid) || !\is_array($site)) {
                continue;
            }
            $error = null;
            $code = $this->normalize($site['code'] ?? '', $error);
            if ($error !== null) {
                $this->addError($attribute, $error);
            }
            $enabled = filter_var($site['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);
            // Only what differs from the default is kept.
            if ($code !== '' || !$enabled) {
                $sites[$uid] = ['enabled' => $enabled, 'code' => $code];
            }
        }
        $this->sites = $sites;
    }

    /** The key for one site, read when a page of that site is rendered. */
    public function keyForSite(string $siteUid): ?string
    {
        $site = $this->sites[$siteUid] ?? null;

        return Key::forSite($this->installationCode, \is_array($site) ? $site : null, static fn(string $name): mixed => App::parseEnv($name));
    }

    private function normalize(mixed $value, ?string &$error): string
    {
        $stored = Key::stored($value);
        if ($stored === null) {
            $error = Craft::t('hazelchat', 'Paste the installation code from Hazelchat or its 64-character key, not an API key.');

            return \is_string($value) ? trim($value) : '';
        }
        // A reference that is set here must hold a code; one that is not
        // set may be meant for another environment.
        if (Key::isEnvReference($stored)) {
            $resolved = App::parseEnv($stored);
            if (\is_string($resolved) && $resolved !== '' && $resolved !== $stored && Key::fromInput($resolved) === null) {
                $error = Craft::t('hazelchat', '{name} does not hold a Hazelchat installation code.', ['name' => $stored]);
            }
        }

        return $stored;
    }
}
