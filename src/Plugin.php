<?php
/**
 * Hazelchat plugin for Craft CMS
 *
 * @copyright 2026 Hazelchat
 * @license MIT; see LICENSE.md
 */

declare(strict_types=1);

namespace hazelchat\craft;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\events\TemplateEvent;
use craft\web\Request;
use craft\web\View;
use hazelchat\craft\models\Settings;
use yii\base\Event;

/**
 * Adds the Hazelchat chat to the pages of a Craft site: one script from
 * app.hazelchat.co with the site's public widget key, rendered by Craft
 * before </body>.
 *
 * Nothing is sent from Craft to Hazelchat, nothing is stored but the
 * plugin's settings, and the control panel, previews and console never
 * load the chat.
 *
 * @method static Plugin getInstance()
 * @method Settings getSettings()
 */
class Plugin extends BasePlugin
{
    public string $schemaVersion = '1.0.0';

    public bool $hasCpSettings = true;

    public function init(): void
    {
        parent::init();

        $request = Craft::$app->getRequest();
        if (!$request instanceof Request || !$request->getIsSiteRequest()) {
            return;
        }

        Event::on(View::class, View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE, function(TemplateEvent $event): void {
            $this->addScript($event);
        });
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('hazelchat/_settings.twig', [
            'settings' => $this->getSettings(),
            'sites' => Craft::$app->getSites()->getAllSites(),
            'scriptUrl' => Key::SCRIPT_URL,
        ]);
    }

    private function addScript(TemplateEvent $event): void
    {
        if ($event->templateMode !== View::TEMPLATE_MODE_SITE) {
            return;
        }
        $request = Craft::$app->getRequest();
        // Previews open inside the control panel, and action requests are
        // not pages a visitor reads.
        if (!$request instanceof Request || $request->getIsPreview() || $request->getIsLivePreview() || $request->getIsActionRequest()) {
            return;
        }

        $settings = $this->getSettings();
        $attributes = Key::attributes(
            $settings->keyForSite(Craft::$app->getSites()->getCurrentSite()->uid),
            $settings->waitForConsent
        );
        if ($attributes === null) {
            return;
        }

        // Rendered with the page's other end-of-body scripts. An absolute
        // address gets no timestamp, so it stays exactly the one Hazelchat's
        // website check looks for, and the fixed key keeps it to one tag.
        // Yii renders every other option as an attribute of the tag; Craft's
        // type for the options names only the ones it reads itself.
        /** @phpstan-ignore argument.type */
        Craft::$app->getView()->registerJsFile(Key::SCRIPT_URL, ['position' => View::POS_END] + $attributes, Key::ASSET_ID);
    }
}
