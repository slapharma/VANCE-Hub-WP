# -*- coding: utf-8 -*-
"""Crop the generated frames to the box the hero actually declares.

The renderer emits width="1400" height="876" so the <img> reserves its space
and cannot shift the headline as it decodes. Anything else there is a lie the
browser corrects after layout, which is the shift the attribute exists to
prevent -- so the files are cut to exactly that.
"""
import io, os
from PIL import Image

W, H = 1400, 876
# gen-heroes.py writes into ./generated; the theme is one level up from here.
HERE = os.path.dirname(os.path.abspath(__file__))
SRC = os.path.join(HERE, "generated")
DST = os.path.join(os.path.dirname(HERE), "wp-content", "themes",
                   "vance-health-hub", "assets", "img", "heroes")
os.makedirs(DST, exist_ok=True)

# Whatever gen-heroes.py left behind, rather than a list to keep in step with
# it. The model returns PNG or JPEG depending on the run, so both are collected
# and the extension is dropped -- the theme only ever holds .jpg.
NAMES = sorted({os.path.splitext(f)[0] for f in os.listdir(SRC)
                if f.lower().endswith((".png", ".jpg", ".jpeg"))})
assert NAMES, "nothing in %s -- run gen-heroes.py first" % SRC

for name in NAMES:
    src = None
    for ext in ("png", "jpg", "jpeg"):
        p = os.path.join(SRC, "%s.%s" % (name, ext))
        if os.path.exists(p):
            src = p
            break
    assert src, "no generated file for " + name

    im = Image.open(src).convert("RGB")
    w, h = im.size

    # Centre-crop to the target ratio, then scale. Cropping before scaling keeps
    # the composition; the sources are already within 0.4% of 1400x876 so this
    # removes a few pixels, not a subject.
    want = W / H
    have = w / h
    if have > want:
        new_w = int(round(h * want))
        left = (w - new_w) // 2
        im = im.crop((left, 0, left + new_w, h))
    elif have < want:
        new_h = int(round(w / want))
        top = (h - new_h) // 2
        im = im.crop((0, top, w, top + new_h))

    im = im.resize((W, H), Image.LANCZOS)

    out = os.path.join(DST, name + ".jpg")
    im.save(out, "JPEG", quality=84, optimize=True, progressive=True)
    print("%-14s %s -> %d x %d, %d KB"
          % (name, os.path.basename(src), W, H, os.path.getsize(out) // 1024))
