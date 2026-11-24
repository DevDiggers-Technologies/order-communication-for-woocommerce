# DevDiggers Order Communication for WooCommerce

Order chat for WooCommerce. Customers message you about an order from **My Account > Orders > View**, and your team replies from one shared **Inbox** or the order edit screen.

| | |
|---|---|
| **Version** | 1.1.0 |
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

### AI assistants (MCP)

Ask Claude, ChatGPT, Cursor, VS Code or any MCP client about your order conversations in plain words: "Who is waiting for an answer?", "What did the customer on order 1234 ask?", "Draft replies for the unanswered messages."

- Tools for an inbox overview (awaiting reply now, longest waits, messages and replies for a period), conversation lists with filters and search, one order's full conversation and the settings that decide who can write and who is emailed
- A "Draft replies for waiting customers" prompt: drafts only, nothing is sent
- Optional tools to reply to a customer on an order (the same as replying in the Inbox) and to close or reopen a conversation. They are off until you allow changes, and every change is logged under WooCommerce > Status > Logs
- Managed in DevDiggers Plugins > AI & MCP: one MCP server for every DevDiggers plugin, an on / off switch per plugin and per write tool (write tools start off), a Try panel and an activity log
- Needs WordPress 6.9+ (Abilities API) and the MCP Adapter, which WooCommerce 10.3+ bundles

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
| `ddwcoc_mcp_abilities` | filter | AI assistant (MCP) tools in `includes/integrations/mcp-integration.php`, under the `order-messages/` namespace (`$abilities`) |

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

### 1.1.0

- Added: AI assistants (MCP). Ask Claude, ChatGPT, Cursor, VS Code or any MCP client who is waiting for an answer, what a customer asked on an order, or for an inbox overview, with a prompt that drafts replies for waiting customers (drafts only, nothing is sent).
- Added: Optional AI tools to reply to a customer on an order and to close or reopen a conversation, the same as in the Inbox. They stay off until you turn on Allow changes.
- Added: DevDiggers Plugins > AI & MCP page. Connect Claude, ChatGPT, Claude Code, Codex, Cursor, VS Code or any MCP client by signing in and clicking Allow in WordPress, with nothing to copy. Turn each plugin and each write tool on or off, try tools in the Try panel and check the activity log.
- Improved: Dashboard cards show the previous period's figure and a short note under every number, and say "None in the previous period" instead of a misleading 100% change.
- Improved: Updated the DevDiggers framework to 1.3.0. On edit screens the title bar with Save stays visible while you scroll on desktop.
- Fixed: Order message emails to customers and to the store were not being sent. They are sent again.
- Fixed: Charts and pickers no longer break when a theme or plugin replaces the shared select2 script.

### 1.0.0

- First release.
