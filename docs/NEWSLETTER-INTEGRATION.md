# Newsletter integration

The public newsletter form is owned by the `hecmedia` React/Next.js app. This
repository owns the server-to-server bridge that reuses the API key already
managed by **Mailchimp for WordPress**.

## Request flow

```text
browser -> hecmedia /api/newsletter/subscribe
        -> POST /wp-json/hectv/v1/newsletter/subscribe -> reCAPTCHA verification
        -> Mailchimp for WordPress -> Newsletter Master audience
        -> Mailchimp double-opt-in email
```

The browser and the legacy HEC Media Lambda@Edge runtime never receive the
reCAPTCHA secret or Mailchimp API key. HEC Media performs same-origin payload
validation and forwards the browser CAPTCHA token. WordPress validates names,
email, explicit consent, reCAPTCHA success, and the expected HEC Media hostname
before contacting Mailchimp.

New members are created with `status=pending`. A pending or subscribed member
returns the same non-enumerating accepted response without another Mailchimp
write, so an API retry does not repeatedly trigger confirmation mail or reveal
whether an address is already on the list.

## Runtime configuration

Names only; values belong in the approved secret/configuration stores.

| Variable | Required | Purpose |
|---|---:|---|
| `HECTV_RECAPTCHA_SECRET_KEY` | production | Server-side reCAPTCHA verification secret |
| `HECTV_RECAPTCHA_ALLOWED_HOSTS` | no | Comma-separated token hostnames; defaults to `hecmedia.org,www.hecmedia.org` |
| `HECTV_NEWSLETTER_LIST_NAME` | no | Audience name; defaults to `Newsletter Master` |
| `HECTV_NEWSLETTER_LIST_ID` | no | Explicit audience-ID recovery override |
| `HECTV_NEWSLETTER_ALLOW_NON_PRODUCTION` | local only | Explicitly permits a controlled non-production integration test |

`HECTV_DISABLE_OUTBOUND=1` always wins. Delivery otherwise requires
`HECTV_ENVIRONMENT=production`, unless the local-only override is explicitly
enabled.

Public staging remains read-only and outbound-disabled. It must not receive the
production signing secret and cannot write a test subscriber.

## Deployment order

1. Add `HECTV_RECAPTCHA_SECRET_KEY` to the approved WordPress production secret
   before applying the task-definition change; the endpoint fails closed while
   it is absent.
2. Deploy the WordPress endpoint.
3. Verify the WordPress Mailchimp connection still exposes exactly one
   `Newsletter Master` audience.
4. Deploy the HEC Media adapter with only the public reCAPTCHA site key. The
   current Lambda@Edge stack cannot receive runtime secrets and must never bake
   one into its JavaScript artifact.
5. Submit one approved test address and confirm that Mailchimp records a
   pending member and sends the double-opt-in email.

## Plugin compatibility boundary

The deployed bundle currently contains Mailchimp for WordPress 4.3.3 because
the production compatibility path is pinned to PHP 7.1. The available plugin
release 4.13.1 requires PHP 7.4, so it cannot be included in the current
lift-and-shift safely. The owned endpoint checks the small provider interface it
uses and fails closed if that contract is unavailable. Upgrade MC4WP after the
separate PHP runtime modernization.

## Verification

```shell
php tests/hectv-newsletter-api.php
```

The test uses a fake provider and never contacts Mailchimp.
