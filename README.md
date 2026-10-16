# DevDiggers Order Communication for WooCommerce

Order chat for WooCommerce. Customers message you about an order from **My Account > Orders > View**, and your team replies from one shared **Inbox** or the order edit screen.

| | |
|---|---|
| **Version** | 1.0.0 |
| **Requires** | WordPress 6.5+, WooCommerce 9.0+, PHP 7.4+ |
| **Tested up to** | WordPress 7.1, WooCommerce 11.2 |
| **License** | GPLv3 or later |
| **WordPress.org listing** | [`readme.txt`](readme.txt) |
| **Pro** | [devdiggers.com/product/woocommerce-order-communication](https://devdiggers.com/product/woocommerce-order-communication/) |

## Features

- A private conversation on every WooCommerce order, in My Account and on the order edit screen
- Shared Inbox with search, Awaiting Reply and Closed filters, and the order beside the chat
- Live updates, read receipts, close and reopen conversations
- Store and customer email notifications through the WooCommerce mailer
- Unread counts on the menu, the Inbox tab, the orders list and My Account orders
- Allowed order statuses, chat colors, a volume dashboard and a setup wizard
- HPOS compatible

## Free vs Pro

| Feature | Free | Pro |
|---|:---:|:---:|
| Order chat in My Account and on the order screen | Yes | Yes |
| Shared Inbox with search and filters | Yes | Yes |
| Live updates and read receipts | Yes | Yes |
| Store and customer email notifications | Yes | Yes |
| Unread counts and Messages columns | Yes | Yes |
| Allowed order statuses and chat colors | Yes | Yes |
| Dashboard: message volume and activity chart | Yes | Yes |
| Photo and document attachments | No | Yes |
| Saved replies | No | Yes |
| Private team notes | No | Yes |
| Auto-reply and order status messages | No | Yes |
| Business hours | No | Yes |
| Daily message limits | No | Yes |
| Email subject, heading and body editor | No | Yes |
| Communications list, bulk actions, CSV export, transcripts | No | Yes |
| Response time and reply rate analytics | No | Yes |

When Pro is active, this plugin stops loading at `plugins_loaded`. Both use the same option keys and message data, so upgrading keeps every conversation and setting.

## Configuration

**Order Communication > Configuration**

- **General**: turn conversations on and choose the order statuses customers can write on.
- **Chat**: bubble and button colors, and how often an open chat checks for new messages.
- **Emails**: turn the store and customer notifications on or off, and set the store recipients.

## Data

- Messages are WordPress comments of type `ddwcoc_order_message` on the order ID, kept out of the core comment screens, widgets and feeds.
- Comment meta: `_ddwcoc_sender` (`customer`, `staff`, `system`), `_ddwcoc_message_status` (`unread`, `read`, `replied`).
- Order meta: `_ddwcoc_conversation_status`, `_ddwcoc_customer_last_seen`, `_ddwcoc_staff_last_seen`.
- Options: `_ddwcoc_*`. No custom tables, no cron, no outbound requests from the plugin itself.

## Hooks

| Hook | Type | When |
|---|---|---|
| `ddwcoc_loaded` | action | The plugin instance is created |
| `ddwcoc_message_added` | action | A message is saved (`$comment_id, $order, $args`) |
| `ddwcoc_conversation_status_changed` | action | A conversation is closed or reopened (`$order, $closed`) |
| `ddwcoc_message_placeholders` | filter | Placeholder replacements used in emails (`$replacements, $order`) |

## AJAX

All actions require a logged-in user and the `ddwcoc-chat` nonce. Access is checked per order: staff need `manage_woocommerce` or `edit_shop_orders`, customers must own the order.

- `ddwcoc_send_message`
- `ddwcoc_fetch_messages`
- `ddwcoc_set_conversation_status` (staff only)

## Build from source

```bash
npm install
npm run build
php bin/test-free-boundary.php
```

`src/` holds the readable JavaScript and LESS, and `assets/` holds the compiled files. `bin/test-free-boundary.php` fails if any Pro file, setting, hook or bundle string comes back into the free build.

## Support

- [Documentation](https://docs.devdiggers.com/woocommerce-order-communication/)
- [Support forum](https://wordpress.org/support/plugin/devdiggers-order-communication-for-woocommerce/)

## Changelog

### 1.0.0

- First release.
