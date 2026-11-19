# duke-yin-helper

 A WordPress plugin that powers DukeYin theme.

## Blocks

Three blocks live under the **Duke Yin Helper** inserter category, so they can be
inserted from the "+" inserter instead of typing shortcodes by hand:

| Block | Name | What it does |
|---|---|---|
| Alert Box | `dukeyin/alert` | Bootstrap alert, server rendered |
| Progress Bar | `dukeyin/progress` | Bootstrap progress bar, server rendered |
| Subtitle | `dukeyin/headline` | Writes the `_headline` post meta; editor-only |

### Subtitle 鈥?`dukeyin/headline`

The post subtitle. Unlike the other two, this block renders **nothing** in the post
content: its value is stored in the `_headline` post meta, which the theme prints
under the post title:

```php
<?php echo get_post_meta( get_the_ID(), '_headline', true ); ?>
```

The storage format is unchanged 鈥?a single string under `_headline` 鈥?so every theme
template keeps working as-is. The block attribute uses `source: 'meta'`, which makes
the editor read and write that meta field through the REST API; `show_in_rest` must
stay enabled on the meta registration for this to work.

| Attribute    | Type   | Source   | Description |
|--------------|--------|----------|-------------|
| `blockValue` | string | `meta`   | Bound to `_headline`. |

The block is placed at the top of the editor by the post-type template
(`$post_type_object->template` in [`includes/meta-block.php`](includes/meta-block.php)),
which is what makes it appear automatically without being inserted. `save()` returns
`null`, so nothing is duplicated into the body.

#### Which post types get it

Every post type whose editor is the block editor, plus `post` and `page`. The list is
computed with `use_block_editor_for_post_type()`, so it follows
[`includes/post-type.php`](includes/post-type.php) automatically 鈥?currently `post`,
`page`, `poem`, `portfolio`, `photo`, `selfie`, `product`, `film_review`,
`tvshow_review`, `game_review`, `book_review` and `product_review`.

Post types that still use the classic editor (`videos`, `links`, `music`,
`slideshow`, `testimonial`, `service-type`) cannot host a block. Those keep the CMB2
subtitle metabox the theme registers in `themes/duke/inc/meta-box.php`, so the
subtitle stays editable there.

The CMB2 metabox is hidden on the post types that *do* have the block, because two
controls writing the same `_headline` key can overwrite each other. That is done from
this plugin (`dukeyin_hide_cmb2_subtitle_box()`), so the theme is not modified, and
dropping the hook brings the metabox back. CMB2 remains the fallback: if the block is
ever unavailable, the metabox is the way to set a subtitle.

#### Existing posts

A post-type template only affects posts created afterwards, so posts written before the
block existed need it added to their content. That is **not** done automatically: a
site may run this plugin on more than one install (a test site and the production
site), and each one has to be able to see what would change and run the migration when
it suits them. Use **Tools 鈫?Subtitle Block Migration**
([`includes/subtitle-migration.php`](includes/subtitle-migration.php)):

| Action | What it does |
|---|---|
| **Check** | Walks every post that still lacks the block and verifies that adding it cannot change the rendered output. Writes nothing. |
| **Run migration** | Prepends the block delimiter to `post_content`. Runs in batches of 50, one request each, so a large site does not hit the time limit. |
| **Revert migration** | Removes the delimiter again 鈥?the exact inverse. The subtitle text lives in `_headline` post meta and is never touched, so nothing is lost. |

Every step is nonce-protected and restricted to `manage_options`. The page shows a
per-post-type table, a progress bar while a run is in flight, and the list of any posts
the check refused to touch.

That migration is safe only because of `dukeyin_restore_legacy_subtitle_content()`.
Adding any block delimiter makes `has_blocks()` true, and `do_blocks()` then removes
`wpautop()` from `the_content`. Posts written before the block editor store bare text
with no `<p>` tags and rely on `wpautop()` to create paragraphs, so leaving the
delimiter in place would collapse their layout. The filter runs at priority 8, before
`do_blocks()` at 9, and removes exactly the prefix the migration added whenever the
subtitle block is the only block in the post 鈥?handing the original string, byte for
byte, to the rest of the pipeline. Rendering therefore cannot change. As soon as a post
contains any other block, the filter steps aside and normal block rendering takes over.

The **Check** action verifies exactly that property on real data, without rendering
anything: for a post with no blocks it requires the guard to return the original bytes;
for a post that already has blocks it requires the added delimiter to strip back to the
original bytes. Both are pure string comparisons, so the result is deterministic 鈥?no
comparing two renders, which would be thrown off by `wp_unique_id()` counters.

Reverting is exact: strip the `<!-- wp:dukeyin/headline /-->` prefix.

### Shortcode compatibility

**The shortcodes are unchanged.** `includes/short-code.php` is byte-for-byte identical
to its previous revision, so existing posts render exactly as before. The blocks do
*not* reuse the shortcode code path, and the shortcodes do *not* call the block
renderers 鈥?the two are independent on purpose:

| | Shortcode (legacy) | Block (new) |
|---|---|---|
| Source | `includes/short-code.php` (frozen) | `includes/blocks/render.php` |
| `type` value | passed through as-is | whitelisted, falls back to `danger` |
| `close` | shown only when `close="1"` | shown unless `close` is false |
| Output | unchanged since before the blocks | adds `wp-block-dukeyin-*` class |

This means you can mix both in the same post, and you can migrate a post to blocks at
your own pace. If you never touch an old post, nothing about it changes.

Note that the legacy `[alert]` prints a literal `0` for `close="0"` and passes any
unknown `type` straight into the class attribute; those quirks are preserved on
purpose rather than "fixed", because fixing them would alter already-published posts.

### Alert Box 鈥?`dukeyin/alert`

The block version of `[alert type='success']鍐呭[/alert]`. The message is a single
RichText field stored as inline HTML, **not** inner blocks, so WordPress does not wrap
it in `<p class="wp-block-paragraph">` 鈥?that wrapper added paragraph margins inside
the box and made the block taller than the shortcode.

| Attribute | Type    | Default   | Description |
|-----------|---------|-----------|-------------|
| `type`    | string  | `danger`  | Bootstrap variant: `primary`, `secondary`, `success`, `danger`, `warning`, `info`, `light`, `dark`. |
| `close`   | boolean | `true`    | Whether to show the dismiss button. |
| `content` | string  | `''`      | The message, as inline HTML (bold/italic/links allowed, no block markup). |

Editor settings: 绫诲瀷 (type), 鏄剧ず鍏抽棴鎸夐挳 (close button).

Alerts created by the first version of the block stored their text as a
`core/paragraph` inner block. A `deprecated` entry migrates them on load, unwrapping
the paragraph so the text survives without picking up the margin.

### Progress Bar 鈥?`dukeyin/progress`

The block version of `[progress title="Download" percentage="75" color="success"]`.

| Attribute     | Type    | Default | Description |
|---------------|---------|---------|-------------|
| `title`       | string  | `''`    | Label above the bar. |
| `percentage`  | number  | `50`    | Fill amount, clamped to 0鈥?00. |
| `height`      | string  | `''`    | Height of the **track**, e.g. `1rem` or `24px`. Empty keeps the Bootstrap default. |
| `color`       | string  | `''`    | Bootstrap variant: `primary`, `secondary`, `success`, `danger`, `warning`, `info`, `light`, `dark`. |
| `striped`     | boolean | `false` | Striped fill. |
| `animated`    | boolean | `false` | Animated stripes (only visible with `striped`). |
| `showLabel`   | boolean | `true`  | Print the percentage inside the fill. |

Editor settings: 鐧惧垎姣? 鏍囬, 棰滆壊, 楂樺害, 鏉＄汗, 鍔ㄧ敾, 鏄剧ず鐧惧垎姣旀枃瀛?

`height` is written to the `.progress` track as both `height` and
`--bs-progress-height` (Bootstrap 5.3 uses the variable for the track height and the
stripe size). It is deliberately *not* applied to the `.progress-bar` fill: doing so
made the fill overflow its track and pushed the percentage label off vertical centre.

> The legacy `[progress]` shortcode still applies `height` to the fill bar, because
> its output is frozen. Only the block uses the corrected behaviour.

### Files

```
includes/blocks/blocks.php                 registration + server render callbacks
includes/blocks/render.php                 block markup builders (alert, progress)
includes/blocks/alert/block.json          alert block metadata
includes/blocks/progress/block.json       progress block metadata
includes/blocks/subtitle/block.json       subtitle block metadata (meta-backed)
includes/blocks/assets/blocks.js          editor bundle (registers all three blocks)
includes/blocks/assets/blocks-editor.css  editor preview styles (Bootstrap is not loaded in the canvas)
includes/meta-block.php                   _headline meta, post-type template, render guard, CMB2 fallback
includes/subtitle-migration.php           Tools → Subtitle Block Migration (check / migrate / revert)
tools/build-i18n.php                      compiles the .po into the .mo and the editor JSON
```

The editor bundle is registered by hand in `dukeyin_blocks_register_assets()` (no
build step, no `.asset.php`), and is wired up from
[`duke-yin-helper.php`](duke-yin-helper.php). All three blocks share it and appear
under the **Duke Yin Helper** inserter category.

The front end needs no extra CSS: the theme's compiled Bootstrap already styles
`.alert` and `.progress`, and the subtitle block prints nothing. Only the editor
canvas gets
[`includes/blocks/assets/blocks-editor.css`](includes/blocks/assets/blocks-editor.css),
because the canvas loads `style-editor.css` rather than the theme's Bootstrap.

### Translations

[`languages/duke-yin-helper-zh_CN.po`](languages/duke-yin-helper-zh_CN.po) is the single
source of truth. Two artefacts are derived from it by
[`tools/build-i18n.php`](tools/build-i18n.php) 鈥?run `php tools/build-i18n.php` after
editing the `.po`:

| File | Used for |
|---|---|
| `duke-yin-helper-zh_CN.mo` | PHP strings, and `block.json` metadata |
| `duke-yin-helper-zh_CN-dukeyin-blocks-editor.json` | the editor bundle's UI strings |

Two things are easy to get wrong, and the build script handles both:

- **Block titles and descriptions are looked up with a gettext context** (`block title`,
  `block description`, `block keyword` 鈥?see `wp-includes/block-i18n.json`). A plain
  `msgid "Subtitle"` does **not** translate a block title; it needs
  `msgctxt "block title"`. The editor bundle calls plain `__()`, so it needs the
  context-free entry as well 鈥?the two coexist.
- **The editor JSON must be named after the handle.** `wp_set_script_translations()` is
  called with an explicit `$path`, and in that form WordPress looks for
  `{domain}-{locale}-{handle}.json`, not the md5-of-path name.

`block.json` keywords stay in English on purpose: they are matched against what the
user types in the inserter, and the translated block title is what appears in the
list.

### Notes on the block path only

These apply to the blocks, and deliberately **not** to the shortcodes:

- An unknown `type` falls back to `danger` instead of leaking into the class attribute.
- `close` is treated as a boolean, so `false`/`0` simply omit the button.
- An unknown `color` is passed through `sanitize_html_class()`; an empty one drops the
  `bg-*` class entirely.
- Percentages are clamped to 0鈥?00 in both paths (the shortcode always did this).
