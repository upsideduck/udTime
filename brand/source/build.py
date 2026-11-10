"""Rebuild the Upsideduck brand kit: SVG logo variants, lockups, tokens and PNG renders.

Usage:  python3 -I brand/source/build.py
Needs Google Chrome (headless) for the PNGs; everything else is plain Python.
"""
import base64
import json
import os
import subprocess
import sys
import tempfile
from pathlib import Path

HERE = Path(__file__).resolve().parent
KIT = HERE.parent
CHROME = os.environ.get("CHROME", "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome")

DUCK = (HERE / "duck.path").read_text().strip()

# ---------------------------------------------------------------- palette
CORE = {
    "ink": ("#14100C", "Ink", "Type, rules, the logo"),
    "cream": ("#F6F1E4", "Cream", "Cards, surfaces, reversed logo"),
    "green": ("#1C4A38", "Bottle Green", "The one accent: actions, links, roundel"),
}
LIGHT = {
    "bg": "#EFE8D8", "surface": "#F6F1E4", "surface-2": "#ECE5D3", "field": "#FBF8F0",
    "border": "rgba(20, 16, 12, .16)", "rule": "#14100C", "text": "#14100C", "muted": "#5C5347",
    "accent": "#1C4A38", "accent-hover": "#143829", "accent-soft": "rgba(28, 74, 56, .10)", "on-accent": "#F6F1E4",
    "success": "#2F6B47", "warning": "#9A6A1C", "danger": "#8E2A1E",
}
DARK = {
    "bg": "#14100C", "surface": "#1C1813", "surface-2": "#252019", "field": "#17130F",
    "border": "rgba(246, 241, 228, .18)", "rule": "#F6F1E4", "text": "#F6F1E4", "muted": "#C4BBA9",
    "accent": "#A8D6BE", "accent-hover": "#C2E4D2", "accent-soft": "rgba(141, 191, 165, .20)", "on-accent": "#14100C",
    "success": "#A8D6BE", "warning": "#E6BA72", "danger": "#EDA393",
}
DATA = {"green": "#1C4A38", "ochre": "#B07D2B", "sienna": "#A0522D", "slate": "#4A5D7E"}

FONT_FILES = {
    "IM Fell English": "IMFellEnglish-Regular.woff2",
    "IM Fell English SC": "IMFellEnglishSC-Regular.woff2",
    "Inter": "Inter-Variable.woff2",
}


def font_face(families=FONT_FILES):
    out = []
    for fam, fn in families.items():
        b64 = base64.b64encode((KIT / "fonts" / fn).read_bytes()).decode()
        weight = "100 900" if fam == "Inter" else "400"
        out.append(f"@font-face{{font-family:'{fam}';font-weight:{weight};"
                   f"src:url(data:font/woff2;base64,{b64}) format('woff2');}}")
    return "".join(out)


# ---------------------------------------------------------------- logo svgs
def duck_g(transform, fill):
    return f'<g transform="{transform}" fill="{fill}"><path d="{DUCK}"/></g>'


def svg(body, label="Upsideduck", vb="0 0 1000 1000", w=1000, h=1000, extra=""):
    return (f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="{vb}" width="{w}" height="{h}" '
            f'role="img" aria-label="{label}"><title>{label}</title>{extra}{body}</svg>')


def duck_svg(c):
    return svg(duck_g("translate(104.16,112.84) scale(0.75883)", c))


def mark_svg(c):
    return svg(f'<circle cx="500" cy="500" r="466" fill="none" stroke="{c}" stroke-width="36"/>'
               + duck_g("translate(177.78,187.28) scale(0.61479)", c))


def roundel_svg(ground, art):
    return svg(f'<circle cx="500" cy="500" r="500" fill="{ground}"/>'
               f'<circle cx="500" cy="500" r="466" fill="none" stroke="{art}" stroke-width="36"/>'
               + duck_g("translate(177.78,187.28) scale(0.61479)", art))


INK, CREAM, GREEN = CORE["ink"][0], CORE["cream"][0], CORE["green"][0]
LOGOS = {
    "duck-ink": duck_svg(INK), "duck-cream": duck_svg(CREAM), "duck-green": duck_svg(GREEN),
    "mark-ink": mark_svg(INK), "mark-cream": mark_svg(CREAM), "mark-green": mark_svg(GREEN),
    "roundel-green": roundel_svg(GREEN, CREAM), "roundel-ink": roundel_svg(INK, CREAM),
    "roundel-cream": roundel_svg(CREAM, INK),
}


# ---------------------------------------------------------------- lockups (measured in Chrome)
def chrome(*args):
    subprocess.run([CHROME, "--headless=new", "--hide-scrollbars", "--disable-gpu",
                    "--no-first-run", "--no-default-browser-check", *args],
                   check=True, stdout=subprocess.PIPE, stderr=subprocess.DEVNULL)


def measure(samples):
    """Return {key: width_px} for (key, family, size_px, letter_spacing_em, text)."""
    spans = "".join(f'<span data-k="{k}" style="font-family:\'{f}\';font-size:{s}px;letter-spacing:{ls}em;'
                    f'white-space:pre;position:absolute">{t}</span>' for k, f, s, ls, t in samples)
    html = (f"<!doctype html><style>{font_face()}</style><body>{spans}<pre id=out></pre><script>"
            "document.fonts.ready.then(()=>{const r={};document.querySelectorAll('span').forEach(e=>"
            "r[e.dataset.k]=e.getBoundingClientRect().width);out.textContent='@@'+JSON.stringify(r)+'@@'})"
            "</script>")
    with tempfile.TemporaryDirectory() as td:
        p = Path(td) / "m.html"
        p.write_text(html)
        r = subprocess.run([CHROME, "--headless=new", "--disable-gpu", "--virtual-time-budget=4000",
                            "--dump-dom", p.as_uri()], capture_output=True, text=True, check=True)
    return json.loads(r.stdout.split("@@")[1])


def lockup_svg(app, text_c, muted_c, mark_c, widths, stacked=False, family="Upsideduck"):
    """App lockup: ring mark + app name (IM Fell English) + family line (IM Fell English SC, tracked)."""
    fonts = f"<style>{font_face({k: v for k, v in FONT_FILES.items() if 'Fell' in k})}</style>"
    mark = f'<circle cx="500" cy="500" r="466" fill="none" stroke="{mark_c}" stroke-width="36"/>' + \
        duck_g("translate(177.78,187.28) scale(0.61479)", mark_c)
    name_w, fam_w = widths["name"], widths["family"]
    pad = 24
    if stacked:
        m = 160
        w = int(max(m, name_w, fam_w) + 2 * pad)
        h = pad + m + 28 + 80 + 40 + pad
        cx = w / 2
        body = (f'<g transform="translate({cx - m / 2},{pad}) scale({m / 1000})">{mark}</g>'
                f'<text x="{cx}" y="{pad + m + 28 + 62}" text-anchor="middle" font-family="IM Fell English" '
                f'font-size="80" fill="{text_c}">{app}</text>'
                f'<text x="{cx + fam_w * 0}" y="{pad + m + 28 + 80 + 34}" text-anchor="middle" '
                f'font-family="IM Fell English SC" font-size="29" letter-spacing="7.5" fill="{muted_c}">{family}</text>')
    else:
        m = 154
        gap = 28
        tx = pad + m + gap
        w = int(tx + max(name_w, fam_w) + pad)
        h = m + 2 * pad
        body = (f'<g transform="translate({pad},{pad}) scale({m / 1000})">{mark}</g>'
                f'<text x="{tx}" y="{pad + 86}" font-family="IM Fell English" font-size="99" fill="{text_c}">{app}</text>'
                f'<text x="{tx + 2}" y="{pad + 132}" font-family="IM Fell English SC" font-size="29" '
                f'letter-spacing="7.5" fill="{muted_c}">{family}</text>')
    return svg(body, label=f"{app}, an {family} app", vb=f"0 0 {w} {h}", w=w, h=h, extra=fonts)


# ---------------------------------------------------------------- tokens
def tokens():
    css = ["/* Upsideduck brand tokens. Light by default; dark under [data-theme=\"dark\"]. */", ":root {"]
    css += [f"  --ud-{k}: {v[0]};  /* {v[1]} */" for k, v in CORE.items()]
    css += [f"  --app-{k}: {v};" for k, v in LIGHT.items()]
    css += [f"  --data-{k}: {v};" for k, v in DATA.items()]
    css += ["  --radius: 3px;", "  --radius-sm: 2px;",
            '  --font-ui: "Inter", system-ui, -apple-system, "Segoe UI", sans-serif;',
            '  --font-logo: "IM Fell English", "Iowan Old Style", Palatino, Georgia, serif;   /* logo only */',
            '  --font-logo-caps: "IM Fell English SC", "IM Fell English", Georgia, serif;   /* logo only */',
            "}", "", '[data-theme="dark"] {']
    css += [f"  --app-{k}: {v};" for k, v in DARK.items()]
    css += ["}", "", "/* Victorian double rule: 2px heavy line over a 1px hairline */",
            ".double-rule { height: 6px; border: 0; margin: 0;",
            "  background: linear-gradient(var(--app-rule), var(--app-rule)) top / 100% 2px no-repeat,",
            "              linear-gradient(var(--app-border), var(--app-border)) bottom / 100% 1px no-repeat; }", ""]
    (KIT / "tokens.css").write_text("\n".join(css))
    (KIT / "tokens.json").write_text(json.dumps({
        "core": {k: {"name": v[1], "hex": v[0], "use": v[2]} for k, v in CORE.items()},
        "light": LIGHT, "dark": DARK, "data": DATA,
        "radius": {"default": "3px", "sm": "2px"},
        "font": {"ui": "Inter", "logo": "IM Fell English", "logoCaps": "IM Fell English SC"},
    }, indent=2) + "\n")


# ---------------------------------------------------------------- reference sheets (HTML -> PNG)
def uri(s):
    return "data:image/svg+xml;base64," + base64.b64encode(s.encode()).decode()


BASE_CSS = f"""
{font_face()}
*{{box-sizing:border-box;margin:0}}
body{{font-family:Inter,sans-serif;background:#EFE8D8;color:#14100C;-webkit-font-smoothing:antialiased;
  font-feature-settings:"cv11","ss01";width:1200px}}
.sheet{{padding:56px 64px 64px}}
.eyebrow{{font-size:13px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#5C5347}}
h1{{font-size:34px;font-weight:700;letter-spacing:-.02em;margin:6px 0 4px}}
.lede{{font-size:16px;color:#5C5347;max-width:760px;line-height:1.5}}
.rule{{height:6px;margin:22px 0 32px;background:linear-gradient(#14100C,#14100C) top/100% 2px no-repeat,
  linear-gradient(rgba(20,16,12,.16),rgba(20,16,12,.16)) bottom/100% 1px no-repeat}}
.grid{{display:grid;gap:20px}}
.card{{background:#F6F1E4;border:1px solid rgba(20,16,12,.16);border-radius:3px}}
.cap{{font-size:13px;color:#5C5347;margin-top:10px}}
.cap b{{color:#14100C;font-weight:600}}
"""


def page(title, eyebrow, lede, inner, extra_css=""):
    return (f"<!doctype html><meta charset=utf-8><style>{BASE_CSS}{extra_css}</style><div class=sheet>"
            f"<div class=eyebrow>{eyebrow}</div><h1>{title}</h1><p class=lede>{lede}</p><div class=rule></div>"
            f"{inner}</div>")


def sheets(lockups):
    S = {}
    # 01 cover
    S["01-cover"] = ("<!doctype html><meta charset=utf-8><style>" + BASE_CSS + """
      body{background:#1C4A38;color:#F6F1E4}
      .c{height:675px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:30px;position:relative}
      .c img{width:250px}
      .n{font-family:'IM Fell English';font-size:96px;line-height:1}
      .f{font-family:'IM Fell English SC';font-size:22px;letter-spacing:.3em;color:#C9D7CC;margin-top:-14px}
      .r{position:absolute;left:64px;right:64px;height:6px;background:linear-gradient(#F6F1E4,#F6F1E4) top/100% 2px no-repeat,
         linear-gradient(rgba(246,241,228,.35),rgba(246,241,228,.35)) bottom/100% 1px no-repeat}
      </style>""" + f"""<div class=c><div class=r style="top:48px"></div>
      <img src="{uri(LOGOS['roundel-green'])}"><div class=n>Upsideduck</div><div class=f>Brand guidelines</div>
      <div class=r style="bottom:48px;transform:scaleY(-1)"></div></div>""", 675)

    # 02 logo family
    def tile(name, bg, caption, w=300):
        return (f'<div><div class=card style="background:{bg};height:{w}px;display:grid;place-items:center">'
                f'<img src="{uri(LOGOS[name])}" style="width:62%"></div><div class=cap>{caption}</div></div>')
    S["02-logo-family"] = (page(
        "The logo family", "Logo",
        "One drawing of the duck, used three ways. The ring mark is the default; the roundel is for app icons "
        "and favicons; the bare duck is for large, quiet placements.",
        '<div class="grid" style="grid-template-columns:repeat(3,1fr)">'
        + tile("mark-ink", "#F6F1E4", "<b>Ring mark</b> &middot; default, used in every lockup")
        + tile("roundel-green", "#F6F1E4", "<b>Roundel</b> &middot; app icon, favicon, avatars")
        + tile("duck-ink", "#F6F1E4", "<b>Duck</b> &middot; large or decorative use")
        + tile("mark-cream", "#14100C", "Ring mark reversed on Ink")
        + tile("roundel-ink", "#EFE8D8", "Roundel on Ink ground")
        + tile("duck-green", "#F6F1E4", "Duck in Bottle Green")
        + "</div>"), 920)

    # 03 lockups
    S["03-lockups"] = (page(
        "App lockup", "Logo",
        "Each product carries the family name underneath its own: <i>App name</i> in IM Fell English, "
        "<i>Upsideduck</i> below in IM Fell English SC, tracked wide and set in the muted tone.",
        '<div class="grid" style="grid-template-columns:1fr 1fr">'
        f'<div><div class=card style="height:260px;display:grid;place-items:center"><img src="{uri(lockups["h-light"])}" style="height:120px"></div>'
        '<div class=cap><b>Horizontal</b> &middot; navigation, headers</div></div>'
        f'<div><div class=card style="height:260px;display:grid;place-items:center;background:#14100C;border-color:#14100C"><img src="{uri(lockups["h-dark"])}" style="height:120px"></div>'
        '<div class=cap><b>Horizontal, dark</b> &middot; Cream on Ink</div></div>'
        f'<div><div class=card style="height:330px;display:grid;place-items:center"><img src="{uri(lockups["s-light"])}" style="height:240px"></div>'
        '<div class=cap><b>Stacked</b> &middot; sign-in, splash, about screens</div></div>'
        f'<div><div class=card style="height:330px;display:grid;place-items:center;background:#1C4A38;border-color:#1C4A38"><img src="{uri(lockups["s-green"])}" style="height:240px"></div>'
        '<div class=cap><b>Stacked on Bottle Green</b> &middot; covers, marketing</div></div>'
        "</div>"), 900)

    # 04 clear space
    S["04-clear-space"] = (page(
        "Clear space &amp; minimum size", "Logo",
        "Keep a margin of at least half the mark's diameter on every side. Below 24&nbsp;px, drop the ring mark "
        "and use the roundel, which survives at favicon size.",
        f"""<div class="grid" style="grid-template-columns:1.3fr 1fr;align-items:stretch">
        <div class=card style="display:grid;place-items:center;padding:30px">
          <div style="position:relative;padding:60px;outline:1.5px dashed #1C4A38;background:
            repeating-linear-gradient(45deg,rgba(28,74,56,.07) 0 8px,transparent 8px 16px)">
            <div style="background:#F6F1E4;width:240px;height:240px"><img src="{uri(LOGOS['mark-ink'])}" style="width:240px;display:block"></div>
            <div style="position:absolute;top:8px;left:50%;transform:translateX(-50%);font-size:13px;color:#1C4A38;font-weight:600">&frac12; &oslash;</div>
            <div style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:13px;color:#1C4A38;font-weight:600">&frac12; &oslash;</div>
          </div></div>
        <div class=card style="padding:36px;display:flex;flex-direction:column;gap:26px;justify-content:center">
          {''.join(f'<div style="display:flex;align-items:center;gap:22px"><div style="width:72px;display:flex;justify-content:center"><img src="{uri(LOGOS[n])}" style="width:{s}px"></div><div><b style="font-size:15px">{s} px</b><div class=cap style="margin:2px 0 0">{c}</div></div></div>'
                   for n, s, c in [("mark-ink", 64, "Ring mark, comfortable"), ("mark-ink", 32, "Ring mark minimum"),
                                   ("roundel-green", 24, "Roundel below this"), ("roundel-green", 16, "Favicon")])}
        </div></div>"""), 700)

    # 05 palette
    def sw(hexv, name, use, dark=False, big=False):
        fg = "#F6F1E4" if dark else "#14100C"
        return (f'<div class=card style="overflow:hidden"><div style="background:{hexv};height:{200 if big else 110}px;'
                f'padding:16px;color:{fg};display:flex;align-items:flex-end;font-weight:600;font-size:{18 if big else 15}px">{name}</div>'
                f'<div style="padding:12px 16px;font-size:13px"><b style="font-family:ui-monospace,Menlo,monospace">{hexv}</b>'
                f'<div class=cap style="margin-top:4px">{use}</div></div></div>')
    S["05-palette"] = (page(
        "Colour", "Palette",
        "Three core colours do almost all the work: Ink on Cream, with Bottle Green as the single accent. "
        "The supporting tones are muted, earthy, and used sparingly for state and data.",
        '<div class="grid" style="grid-template-columns:repeat(3,1fr)">'
        + sw(INK, "Ink", "Type, rules, logo", True, True)
        + sw(CREAM, "Cream", "Cards and surfaces", False, True)
        + sw(GREEN, "Bottle Green", "Accent: actions, links, roundel", True, True)
        + '</div><div class=eyebrow style="margin:34px 0 14px">Supporting</div>'
        '<div class="grid" style="grid-template-columns:repeat(6,1fr)">'
        + sw("#EFE8D8", "Paper", "Page background")
        + sw("#ECE5D3", "Parchment", "Secondary surface")
        + sw("#5C5347", "Umber", "Muted text", True)
        + sw("#2F6B47", "Fern", "Success", True)
        + sw("#9A6A1C", "Brass", "Warning", True)
        + sw("#8E2A1E", "Oxblood", "Danger", True)
        + '</div><div class=eyebrow style="margin:34px 0 14px">Data series &amp; dark-mode accents</div>'
        '<div class="grid" style="grid-template-columns:repeat(6,1fr)">'
        + sw("#B07D2B", "Ochre", "Data series 2", True)
        + sw("#A0522D", "Sienna", "Data series 3", True)
        + sw("#4A5D7E", "Slate", "Data series 4", True)
        + sw("#A8D6BE", "Mint", "Accent on dark")
        + sw("#E6BA72", "Wheat", "Warning on dark")
        + sw("#EDA393", "Rose", "Danger on dark")
        + "</div>"), 1000)

    # 06 typography
    S["06-typography"] = (page(
        "Typography", "Type",
        "Two voices, kept strictly apart. The engraved serif belongs to the logo and nothing else; "
        "every word of interface and body copy is set in Inter.",
        """<div class="grid" style="grid-template-columns:1fr 1.25fr">
        <div class=card style="padding:30px">
          <div class=eyebrow>Logo only</div>
          <div style="font-family:'IM Fell English';font-size:84px;line-height:1.05;margin:14px 0 2px">udTime</div>
          <div style="font-family:'IM Fell English SC';font-size:22px;letter-spacing:.26em;color:#5C5347">Upsideduck</div>
          <div class=rule style="margin:24px 0 18px"></div>
          <div style="font-size:14px;line-height:1.55;color:#5C5347"><b style="color:#14100C">IM Fell English</b> for the app name<br>
          <b style="color:#14100C">IM Fell English SC</b>, +0.26em tracking, for the family line<br>Never for headings, buttons or paragraphs.</div>
        </div>
        <div class=card style="padding:30px">
          <div class=eyebrow>Interface &middot; Inter</div>
          <div style="font-size:40px;font-weight:700;letter-spacing:-.02em;margin:14px 0 6px">Page title 700</div>
          <div style="font-size:22px;font-weight:600;letter-spacing:-.01em;margin-bottom:8px">Section heading 600</div>
          <div style="font-size:16px;line-height:1.55;margin-bottom:12px">Body copy is Inter 400 at 15&ndash;16&nbsp;px with generous line height.
          Labels and buttons use 500. Numbers in tables use tabular figures: <span style="font-variant-numeric:tabular-nums">08:30 &ndash; 17:15</span>.</div>
          <div style="font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#5C5347">Overline &middot; 600 &middot; +0.06em</div>
          <div class=rule style="margin:22px 0 14px"></div>
          <div style="font-size:30px;letter-spacing:-.01em">Aa Bb Cc 0123456789</div>
        </div></div>"""), 660)

    # 07 elements
    S["07-elements"] = (page(
        "Graphic elements", "Details",
        "A printed-matter feel comes from a few small, consistent devices, not from ornament.",
        """<div class="grid" style="grid-template-columns:repeat(3,1fr)">
        <div><div class=card style="height:200px;padding:40px 30px;display:flex;flex-direction:column;justify-content:center;gap:28px">
          <div class=rule style="margin:0"></div><div style="height:3px;background:#14100C"></div></div>
          <div class=cap><b>Double rule</b> &middot; 2&nbsp;px Ink over a 1&nbsp;px hairline. Under the logo and section titles. Cards may carry a 3&nbsp;px Ink top rule.</div></div>
        <div><div class=card style="height:200px;display:flex;align-items:center;justify-content:center;gap:34px">
          <div style="width:70px;height:70px;border-radius:50%;display:grid;place-items:center;font-size:30px;color:#2F6B47;border:2px solid currentColor;box-shadow:0 0 0 4px #F6F1E4,0 0 0 5.5px currentColor">&#10003;</div>
          <div style="width:70px;height:70px;border-radius:50%;display:grid;place-items:center;font-size:30px;color:#8E2A1E;border:2px solid currentColor;box-shadow:0 0 0 4px #F6F1E4,0 0 0 5.5px currentColor">!</div></div>
          <div class=cap><b>Seal</b> &middot; a double-ringed circle that echoes the roundel. Used for status on result pages.</div></div>
        <div><div class=card style="height:200px;display:flex;align-items:center;justify-content:center;gap:16px">
          <div style="width:84px;height:84px;background:#ECE5D3;border:1px solid rgba(20,16,12,.16);border-radius:3px"></div>
          <div style="width:84px;height:84px;background:#ECE5D3;border:1px solid rgba(20,16,12,.16);border-radius:2px"></div>
          <div style="width:84px;height:84px;background:#F6F1E4;border:1px solid rgba(20,16,12,.16);border-top:3px solid #14100C;border-radius:3px"></div></div>
          <div class=cap><b>Square corners</b> &middot; 3&nbsp;px radius for cards, 2&nbsp;px for buttons and fields. Hairline borders, almost no shadow.</div></div>
        </div>"""), 530)

    # 08 UI in light + dark
    def ui(t, dark):
        p = DARK if dark else LIGHT
        mark = LOGOS["mark-cream" if dark else "mark-ink"]
        return f"""<div style="background:{p['bg']};padding:28px;border-radius:3px;color:{p['text']}">
          <div style="display:flex;align-items:center;gap:12px;padding-bottom:14px;position:relative">
            <img src="{uri(mark)}" style="width:40px"><div style="line-height:1"><div style="font-family:'IM Fell English';font-size:26px">udTime</div>
            <div style="font-family:'IM Fell English SC';font-size:12px;letter-spacing:.26em;color:{p['muted']};margin-top:5px">Upsideduck</div></div></div>
          <div style="height:5px;margin-bottom:22px;background:linear-gradient({p['rule']},{p['rule']}) top/100% 2px no-repeat,linear-gradient({p['border']},{p['border']}) bottom/100% 1px no-repeat"></div>
          <div style="background:{p['surface']};border:1px solid {p['border']};border-top:3px solid {p['rule']};border-radius:3px;padding:22px">
            <div style="font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:{p['muted']}">This week</div>
            <div style="font-size:40px;font-weight:700;letter-spacing:-.02em;font-variant-numeric:tabular-nums;margin:4px 0 2px">32:45</div>
            <div style="font-size:14px;color:{p['muted']};margin-bottom:18px">of 40:00 scheduled</div>
            <div style="display:flex;gap:10px;margin-bottom:18px">
              <span style="background:{p['accent']};color:{p['on-accent']};padding:9px 16px;border-radius:2px;font-weight:500;font-size:15px">Start timer</span>
              <span style="border:1px solid {p['border']};background:{p['surface-2']};padding:9px 16px;border-radius:2px;font-weight:500;font-size:15px">Add entry</span></div>
            <div style="font-size:14px;padding:10px 14px;border-radius:3px;border:1px solid {p['success']};border-left-width:3px;background:{p['accent-soft']};color:{p['success']}">Saved. Your week is up to date.</div>
            <div style="font-size:14px;padding:10px 14px;margin-top:10px;border-radius:3px;border:1px solid {p['danger']};border-left-width:3px;color:{p['danger']}">Entry overlaps another entry.</div>
          </div><div class=cap style="color:{p['muted']};margin-top:14px">{t}</div></div>"""
    S["08-interface"] = (page(
        "In use", "Interface",
        "Cream surfaces on a warm paper ground in light mode; Ink ground with Cream type in dark mode, "
        "where Bottle Green lifts to Mint so it keeps its contrast.",
        f'<div class="grid" style="grid-template-columns:1fr 1fr">{ui("Light", False)}{ui("Dark", True)}</div>'), 760)

    # 09 don'ts
    def dont(inner, caption, bg="#F6F1E4"):
        return (f'<div><div class=card style="height:210px;display:grid;place-items:center;background:{bg};position:relative;overflow:hidden">{inner}'
                f'<div style="position:absolute;top:10px;right:12px;width:26px;height:26px;border-radius:50%;background:#8E2A1E;color:#F6F1E4;'
                f'display:grid;place-items:center;font-weight:700;font-size:15px">&times;</div></div><div class=cap>{caption}</div></div>')
    m = uri(LOGOS["mark-ink"])
    S["09-donts"] = (page(
        "Please don&rsquo;t", "Misuse",
        "The duck is meant to be upside down. Everything else about it should stay exactly as drawn.",
        '<div class="grid" style="grid-template-columns:repeat(4,1fr)">'
        + dont(f'<img src="{m}" style="width:120px;transform:scale(1.45,.8)">', "Stretch or squash the mark")
        + dont(f'<img src="{m}" style="width:120px;transform:rotate(180deg)">', "Turn the duck the right way up")
        + dont(f'<img src="{uri(mark_svg("#D2452B"))}" style="width:120px">', "Recolour outside the palette")
        + dont(f'<img src="{m}" style="width:120px">', "Place Ink on low-contrast grounds", "#5C5347")
        + dont(f'<img src="{m}" style="width:120px;filter:drop-shadow(6px 6px 4px rgba(0,0,0,.45))">', "Add shadows, glows or bevels")
        + dont(f'<div style="font-family:\'IM Fell English\';font-size:26px;line-height:1.3;padding:20px">Set paragraphs and headings in the logo serif</div>', "Use IM Fell for UI or body text")
        + dont(f'<div style="display:flex;align-items:center;gap:10px"><img src="{m}" style="width:60px"><span style="font:700 30px Inter">udTime</span></div>', "Rebuild the lockup in other fonts")
        + dont(f'<div style="display:flex;gap:8px"><span style="background:#1C4A38;color:#F6F1E4;padding:8px 12px;border-radius:2px;font-weight:500">Save</span><span style="background:#4A5D7E;color:#F6F1E4;padding:8px 12px;border-radius:2px;font-weight:500">Share</span><span style="background:#B07D2B;color:#F6F1E4;padding:8px 12px;border-radius:2px;font-weight:500">Send</span></div>', "Use data colours as extra accents")
        + "</div>"), 740)
    return S


def shoot(html, out, w, h, scale=2, transparent=False):
    with tempfile.TemporaryDirectory() as td:
        p = Path(td) / "p.html"
        p.write_text(html)
        args = [f"--window-size={w},{h}", f"--force-device-scale-factor={scale}", "--virtual-time-budget=4000",
                f"--screenshot={out}"]
        if transparent:
            args.append("--default-background-color=00000000")
        chrome(*args, p.as_uri())


def main():
    logo_svg = KIT / "logo" / "svg"
    logo_png = KIT / "logo" / "png"
    for d in (logo_svg, logo_png, KIT / "images"):
        d.mkdir(parents=True, exist_ok=True)

    for name, s in LOGOS.items():
        (logo_svg / f"{name}.svg").write_text(s)

    w = measure([("name", "IM Fell English", 99, 0, "udTime"),
                 ("family", "IM Fell English SC", 29, 0.26, "Upsideduck"),
                 ("sname", "IM Fell English", 80, 0, "udTime")])
    hw = {"name": w["name"], "family": w["family"]}
    sw = {"name": w["sname"], "family": w["family"]}
    lockups = {
        "h-light": lockup_svg("udTime", INK, "#5C5347", INK, hw),
        "h-dark": lockup_svg("udTime", CREAM, "#C4BBA9", CREAM, hw),
        "s-light": lockup_svg("udTime", INK, "#5C5347", INK, sw, stacked=True),
        "s-dark": lockup_svg("udTime", CREAM, "#C4BBA9", CREAM, sw, stacked=True),
        "s-green": lockup_svg("udTime", CREAM, "#C9D7CC", CREAM, sw, stacked=True),
    }
    names = {"h-light": "lockup-horizontal-ink", "h-dark": "lockup-horizontal-cream",
             "s-light": "lockup-stacked-ink", "s-dark": "lockup-stacked-cream", "s-green": "lockup-stacked-cream-on-green"}
    for k, s in lockups.items():
        (logo_svg / f"{names[k]}.svg").write_text(s)

    tokens()

    if "--no-png" in sys.argv:
        return
    # transparent PNG logos
    for name, s in LOGOS.items():
        for size in (1024, 256):
            html = (f"<!doctype html><style>*{{margin:0}}body{{background:transparent}}</style>"
                    f'<img src="{uri(s)}" style="width:{size}px;height:{size}px;display:block">')
            shoot(html, logo_png / f"{name}-{size}.png", size, size, scale=1, transparent=True)
    for k, s in lockups.items():
        import re
        vw, vh = map(float, re.search(r'viewBox="0 0 ([\d.]+) ([\d.]+)"', s).groups())
        html = (f"<!doctype html><style>*{{margin:0}}body{{background:transparent}}</style>"
                f'<img src="{uri(s)}" style="width:{int(vw)}px;height:{int(vh)}px;display:block">')
        shoot(html, logo_png / f"{names[k]}.png", int(vw), int(vh), scale=4, transparent=True)
    for name, (html, h) in sheets(lockups).items():
        shoot(html, KIT / "images" / f"{name}.png", 1200, h)
        print("rendered", name)


if __name__ == "__main__":
    main()
