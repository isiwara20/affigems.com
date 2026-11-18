#!/usr/bin/env python3
"""
Batch-convert PNG/JPEG images under assets/ to WebP sidecars.
Keeps originals for fallback; storefront helpers prefer .webp when present.

Skips logos and favicons (kept as PNG):
  logo.png, logobottom.png, favicon*, apple-touch-icon*

Run: py scripts/convert-all-images-to-webp.py
"""
from __future__ import annotations

import re
import sys
from pathlib import Path

try:
    from PIL import Image
except ImportError:
    print("Pillow required: py -m pip install pillow")
    sys.exit(1)

ROOT = Path(__file__).resolve().parent.parent / "assets"
SOURCE_EXT = {".png", ".jpg", ".jpeg"}
QUALITY = 80

SKIP_RE = re.compile(
    r"(?i)(^|[/\\])(logo|logobottom)\.png$|"
    r"(^|[/\\])favicon[^/\\]*\.(png|jpe?g|ico)$|"
    r"(^|[/\\])apple-touch-icon[^/\\]*\.png$"
)

# Max (width, height) by path fragment; first match wins.
SIZE_RULES: list[tuple[str, tuple[int, int]]] = [
    ("images/login-couple", (720, 1400)),
    ("images/login-bg", (1920, 1080)),
    ("images/pic02", (640, 640)),
    ("images/pic1", (900, 600)),
    ("images/M01", (900, 1200)),
    ("images/about/ezymart-about-banner", (1920, 720)),
    ("images/contact/ezymart-contact-banner", (1920, 720)),
    ("images/about/", (1200, 900)),
    ("images/contact/", (1200, 900)),
    ("images/payment/", (400, 200)),
    ("images/brands/", (800, 400)),
    ("uploads/banners", (1600, 700)),
    ("uploads/products", (900, 900)),
    ("uploads/categories", (600, 600)),
    ("uploads/warranty", (1200, 1200)),
    ("uploads/", (1200, 1200)),
    ("images/", (1400, 1400)),
]


def should_skip(path: Path) -> bool:
    rel = path.relative_to(ROOT).as_posix()
    return bool(SKIP_RE.search(rel))


def limits_for(path: Path) -> tuple[int, int]:
    rel = path.relative_to(ROOT).as_posix().lower()
    for fragment, lim in SIZE_RULES:
        if fragment.rstrip("/") in rel:
            return lim
    return (1400, 1400)


def fit_size(w: int, h: int, max_w: int, max_h: int) -> tuple[int, int]:
    if w <= max_w and h <= max_h:
        return w, h
    ratio = min(max_w / w, max_h / h)
    return max(1, int(w * ratio)), max(1, int(h * ratio))


def process(path: Path, force: bool = False) -> str | None:
    if path.suffix.lower() not in SOURCE_EXT:
        return None
    if should_skip(path):
        return f"SKIP {path.relative_to(ROOT)} (logo/favicon)"

    webp_path = path.with_suffix(".webp")
    if webp_path.exists() and not force:
        # Still refresh if webp is much older / source larger and webp missing quality
        if webp_path.stat().st_mtime >= path.stat().st_mtime:
            return None

    try:
        with Image.open(path) as im:
            im.load()
            if im.mode in ("RGBA", "LA", "P"):
                im = im.convert("RGBA")
            else:
                im = im.convert("RGB")

            max_w, max_h = limits_for(path)
            nw, nh = fit_size(im.width, im.height, max_w, max_h)
            if (nw, nh) != (im.width, im.height):
                im = im.resize((nw, nh), Image.Resampling.LANCZOS)

            save_kw: dict = {"quality": QUALITY, "method": 6}
            im.save(webp_path, "WEBP", **save_kw)

        orig_kb = path.stat().st_size / 1024
        webp_kb = webp_path.stat().st_size / 1024
        return f"{path.relative_to(ROOT)} — {orig_kb:.1f} KB -> webp {webp_kb:.1f} KB"
    except Exception as exc:
        return f"ERROR {path.relative_to(ROOT)}: {exc}"


def main() -> None:
    force = "--force" in sys.argv
    if not ROOT.is_dir():
        print(f"Assets folder not found: {ROOT}")
        sys.exit(1)

    # Remove accidental WebP for logos/favicons
    removed = 0
    for path in ROOT.rglob("*"):
        if not path.is_file():
            continue
        name = path.name.lower()
        if path.suffix.lower() != ".webp":
            continue
        stem = path.stem.lower()
        if stem in {"logo", "logobottom"} or stem.startswith("favicon") or stem.startswith("apple-touch-icon"):
            path.unlink(missing_ok=True)
            removed += 1
            print(f"Removed logo/favicon webp: {path.relative_to(ROOT)}")

    results: list[str] = []
    skipped_up_to_date = 0
    for path in sorted(ROOT.rglob("*")):
        if not path.is_file():
            continue
        if path.suffix.lower() not in SOURCE_EXT:
            continue
        line = process(path, force=force)
        if line is None:
            skipped_up_to_date += 1
        else:
            results.append(line)

    print("\nARIGATO.LK - WebP conversion complete\n")
    print(f"Converted/reported: {len(results)} file(s)")
    print(f"Already up to date: {skipped_up_to_date}")
    if removed:
        print(f"Removed logo/favicon webp files: {removed}")
    print()
    for line in results:
        print(line)


if __name__ == "__main__":
    main()
