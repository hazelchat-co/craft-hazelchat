# Hazelchat for Craft CMS

Adds your [Hazelchat](https://hazelchat.co/?utm_source=craft&utm_medium=plugin&utm_campaign=readme) AI chat agent to a Craft site from one pasted installation code. The agent answers visitors from the pages you approved, shows its sources and passes on their enquiries.

- Craft CMS 5, PHP 8.2 or later.
- Needs a Hazelchat account. The Free plan covers one website and stays free.
- Hosted service: visitors' browsers load the chat from `app.hazelchat.co`, and Hazelchat processes their questions and any enquiry details they send ([privacy notice](https://hazelchat.co/privacy/), [terms](https://hazelchat.co/terms/)). The plugin itself sends nothing.

## Install

1. In Hazelchat, add your website and copy its installation code ([create a free account](https://app.hazelchat.co/signup?utm_source=craft&utm_medium=plugin&utm_campaign=readme-signup)).
2. Install the plugin from the Plugin Store in your control panel, or run:

   ```bash
   composer require hazelchat/craft-hazelchat
   php craft plugin/install hazelchat
   ```

3. Open **Settings → Plugins → Hazelchat**, paste the installation code and save.
4. Back in Hazelchat, click **Publish and switch on**. Hazelchat finds the chat on your live page and verifies the website.

If you pasted a Hazelchat script into your templates before, remove it so the chat loads once.

### Environment variables and project config

The settings live in the project config like every plugin's. Instead of the code you can enter an environment variable such as `$HAZELCHAT_KEY` and set `HAZELCHAT_KEY` in each environment's `.env` to the installation code or its key. Where `allowAdminChanges` is off (usually production), set the plugin up in development and deploy the project config.

## What it does

- Every page Craft renders from your site templates gets one tag before `</body>`:
  `<script id="hazelchat-craft" src="https://app.hazelchat.co/widget.js" data-agent="<key>" defer></script>`
  Your layout needs the `</body>` tag (Craft adds the scripts there).
- **Installation code** takes the pasted code, just its 64-character key, or an environment variable. Only the key is saved, and only a valid key ever reaches a page; anything else prints nothing.
- **Wait for my consent manager** (off) adds `data-require-consent="true"`: the chat starts only once your consent manager allows it. Only the chat waits: browsers still download `widget.js` from `app.hazelchat.co` straight away, so the visitor's IP address reaches Hazelchat before they agree. This is by design. If your site must prevent that too, use a consent tool that can block the script itself. See [Visitor consent](https://hazelchat.co/integrations/craft-cms/#consent).
- The script tag carries the id `hazelchat-craft`. When Hazelchat checks your website, it reads this id to record that the chat was installed with this plugin, and it counts the plugin's installations that way.
- **More** (with more than one site): every site uses the main code; switch the chat off for a site, or give it its own code.
- Nothing in the control panel, in previews, on action requests or in the console. No database tables, no requests from Craft to Hazelchat, no telemetry.

## Remove

Uninstall the plugin under **Settings → Plugins**, or clear the installation code. The chat no longer appears on newly rendered pages; clear any static or template caches.

## Deutsch

Hazelchat fügt Ihren KI-Chat-Agenten mit einem einzigen Installationscode in Ihre Craft-Site ein (Craft CMS 5). Installieren Sie das Plugin aus dem Plug-in-Store oder mit `composer require hazelchat/craft-hazelchat` und `php craft plugin/install hazelchat`, fügen Sie unter **Einstellungen → Plug-ins → Hazelchat** den Code aus Hazelchat ein (oder eine Umgebungsvariable wie `$HAZELCHAT_KEY`) und speichern Sie. Die Browser Ihrer Besucher laden den Chat von `app.hazelchat.co`; deren Fragen und Anfragedaten verarbeitet Hazelchat. Wartet der Chat auf Ihren Consent-Manager, lädt der Browser `widget.js` trotzdem sofort, und die IP-Adresse des Besuchers erreicht Hazelchat schon vor der Einwilligung; nur der Chat wartet. Über die ID `hazelchat-craft` im Skript-Tag zählt Hazelchat die Installationen dieses Plugins. Sie brauchen ein Hazelchat-Konto; der Free-Tarif gilt für eine Website und bleibt kostenlos.

## Licence

MIT. Craft is a trademark of Pixel & Tonic. Hazelchat is not affiliated with or endorsed by it.
