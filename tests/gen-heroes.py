# -*- coding: utf-8 -*-
"""Generate the spotlight-hero photographs through OpenRouter.

Every constraint in SHARED comes from how the hero actually renders
(inc/page-hero-spotlight.php + the .vhh-hero-spotlight block in main.css):

  - the media box is the RIGHT ~52% of the band, so the subject has to sit
    right of centre or it is cropped off;
  - its left ~38% is dissolved into the mint band by two gradients, so that
    edge must be bright and empty or the fade reads as a smear;
  - object-position is 46% 14%, i.e. the visible crop favours the upper
    middle, so faces belong high in the frame;
  - the band is #ECF5F5 -> #F6F9FA, so anything warm or saturated fights it.

Usage:  python gen_heroes.py [key ...]      (no args = all four)
"""
import base64, io, json, os, sys, urllib.request

KEY = os.environ.get("OPENROUTER_API_KEY")
if not KEY:
    sys.exit("OPENROUTER_API_KEY is not set")

MODEL = "google/gemini-3-pro-image"
OUT = os.path.dirname(os.path.abspath(__file__)) + os.sep + "generated"
os.makedirs(OUT, exist_ok=True)

SHARED = (
    "Photograph, 16:10 landscape, shot on a full-frame camera at f/2.0, natural "
    "window daylight, documentary rather than glossy stock. "
    "COMPOSITION IS CRITICAL: the subject sits in the RIGHT HALF of the frame; "
    "the LEFT THIRD is bright, soft and almost empty — pale wall, blown-out "
    "window light or open sky — because that edge is dissolved into a pale "
    "background. Keep faces and the main focal point in the UPPER-MIDDLE of the "
    "frame, never near the bottom edge. "
    "Palette: cool and airy, desaturated, sitting comfortably beside pale mint "
    "(#ECF5F5) and teal (#008080) — soft greens, greys, off-whites, no warm "
    "orange or amber cast, no heavy shadows. "
    "British setting, ordinary real-looking people, mixed ages and ethnicities, "
    "calm and capable rather than sick or worried. "
    "Absolutely no text, no lettering, no signage, no watermarks, no logos, no "
    "user interface elements, no medical branding."
)

PROMPTS = {
    # The shelf of three free calculators. What it has to say: these are yours
    # to use, right now, without asking anyone.
    "free-tools": (
        "A woman in her forties sitting at a bright kitchen table in soft morning "
        "light, holding a tablet at a comfortable reading angle, mid-thought and "
        "faintly pleased — the look of someone who has just got a useful answer. "
        "A glass of water and a notebook beside her. Pale kitchen, white walls, a "
        "large window to her left flooding the left of the frame with clean light. "
        + SHARED
    ),
    # A short private questionnaire about symptoms. Reflective, unhurried,
    # nobody in a clinical gown.
    "survey": (
        "A man in his thirties sitting by a wide window in a quiet living room, "
        "phone resting in both hands, looking down at it thoughtfully as he "
        "answers something. Unhurried and private. Plants and a pale sofa behind "
        "him, the window on the left of the frame filling that edge with bright "
        "diffused daylight. " + SHARED
    ),
    # The evidence library. A body of material, not a person — so the closest
    # photographic equivalent: reading, concentration, depth of field.
    "knowledgebase": (
        "An older woman reading on a tablet in a bright, modern public library "
        "reading room, softly out of focus shelves receding behind her, deep "
        "depth cues, calm concentration. Tall windows on the left of the frame "
        "wash that side of the picture almost white. " + SHARED
    ),
    # The page nobody plans to land on. A doorway rather than a person: the
    # message is 'there is a way on from here', not 'look at this stranger'.
    "not-found": (
        "An architectural interior: a bright open doorway in a pale minimalist "
        "building, daylight pouring through it onto a smooth floor, a corridor "
        "beyond leading somewhere out of shot. No people at all. Quiet, spacious, "
        "reassuring, almost abstract. The left third of the frame is a plain "
        "sunlit wall with nothing on it. " + SHARED
    ),
}


def generate(name, prompt):
    body = json.dumps({
        "model": MODEL,
        "modalities": ["image", "text"],
        "messages": [{"role": "user", "content": prompt}],
    }).encode()

    req = urllib.request.Request(
        "https://openrouter.ai/api/v1/chat/completions",
        data=body,
        headers={
            "Authorization": "Bearer " + KEY,
            "Content-Type": "application/json",
            "HTTP-Referer": "https://vancehealthhub.co.uk",
            "X-Title": "Vance Medical Hub hero images",
        },
    )
    with urllib.request.urlopen(req, timeout=300) as r:
        data = json.load(r)

    msg = (data.get("choices") or [{}])[0].get("message") or {}
    images = msg.get("images") or []
    if not images:
        raise RuntimeError("no image returned for %s: %s"
                           % (name, json.dumps(data)[:600]))

    url = images[0]["image_url"]["url"]
    head, b64 = url.split(",", 1)
    ext = "png" if "png" in head else "jpg"
    raw = base64.b64decode(b64)
    path = os.path.join(OUT, "%s.%s" % (name, ext))
    with open(path, "wb") as f:
        f.write(raw)

    usage = data.get("usage") or {}
    print("%-14s %7d bytes  %s  cost=%s"
          % (name, len(raw), path, usage.get("cost")))
    return path


wanted = sys.argv[1:] or list(PROMPTS)
for name in wanted:
    generate(name, PROMPTS[name])
