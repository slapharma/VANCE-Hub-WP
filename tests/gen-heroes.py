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

# The seven that were still borrowing an image bought for another page. They are
# deliberately not seven variations of "a person with a device in a bright room":
# two are object-led (a bench, a worktop) to break up the run, and the four that
# do show a screen differ in setting, distance and who is looking at whom.
PROMPTS.update({
    # Contact Us. The card beside it promises "a real reply, within one business
    # day", so the picture has to be a person who could send one -- not a call
    # centre, and not a stock headset.
    "contact": (
        "A woman in her thirties at a desk in a small, bright office, turning "
        "slightly towards the camera mid-thought while typing a reply, relaxed "
        "and approachable. A plant and a mug on the desk, a pale wall behind. "
        "A tall window at the left of the frame floods that edge with clean "
        "light. " + SHARED
    ),
    # About Us. The card is the 30+ years stat and a flask; the page's claim is
    # pharmaceutical rigour, so this one is glassware and a bench, not a screen.
    "about": (
        "Two scientists in a bright modern laboratory at a clean white bench, "
        "one holding a glass flask up to the light while the other looks on, "
        "both absorbed in the work. Pale equipment, shallow depth of field, "
        "clinical but not cold. The left of the frame is a bright window wall, "
        "almost white. " + SHARED
    ),
    # The meal planner. Object-led on purpose: the page is about food you would
    # actually cook, so the food is the subject and the hands are incidental.
    "recipes": (
        "Overhead-ish three-quarter view of a pale kitchen worktop, a woman's "
        "hands slicing courgette on a wooden board, fresh salmon fillets, spinach "
        "and lemon laid out beside it, a small bowl of oats. Bright and clean, "
        "no clutter. The left of the frame is empty sunlit worktop. " + SHARED
    ),
    # The malnutrition screener. One-to-one and warm -- a dietitian beside a
    # patient, not a pair of clinicians talking over one.
    "malnutrition": (
        "A dietitian in a soft blue shirt sitting beside an older male patient at "
        "a bright consulting room table, turned towards him, explaining something "
        "on a printed sheet between them. Attentive and unhurried, no white coats, "
        "no equipment on show. A wide window at the left of the frame is blown out "
        "to near white. " + SHARED
    ),
    # VANCE-Ai. Someone asking a question of their own, at home, at their own
    # pace -- the opposite of a consultation.
    "askai": (
        "A man in his late twenties sitting on a pale sofa with a laptop on his "
        "knees, one hand on the trackpad, mid-question and looking at the screen "
        "with mild curiosity rather than worry. A quiet living room, a bright "
        "window filling the left of the frame. " + SHARED
    ),
    # Get Started Today. Evidence being handled rather than made: printed papers,
    # charts, two people comparing them. Distinct from About's laboratory.
    "evidence": (
        "Two colleagues at a large bright table covered with printed research "
        "papers and charts, one pointing at a figure on a page while the other "
        "follows, a laptop open and out of focus to one side. Serious, "
        "collaborative, an institutional meeting room with a glass wall on the "
        "left of the frame that washes that edge white. " + SHARED
    ),
    # The User Guide. Being shown how something fits together -- side by side and
    # domestic, not clinical.
    "userguide": (
        "A young woman sitting beside an older woman at a kitchen table, leaning "
        "in to point at something on a laptop screen they are both looking at, "
        "the older woman nodding. Warm and ordinary, a bright kitchen window at "
        "the left of the frame. " + SHARED
    ),
})


# The category-archive heroes (inc/category-hero.php). Keyed "cat-<slug>" so
# process-heroes.py can route them into assets/img/heroes/categories/ while
# everything else keeps landing flat in assets/img/heroes/.
#
# Only the three sections that carry content today. The other nine categories
# render the geometric motif, which is the same thing the Knowledgebase, the
# 404 and the five policy documents do -- a section with no articles in it does
# not need a photograph of somebody enjoying it.
#
# The three are deliberately NOT three variations of "a person in a bright room
# with a screen": that run already exists across the page heroes. One reads on
# paper, one is domestic and object-led, one is a room of people mid-movement.
PROMPTS.update({
    # Clinical Reviews. Evidence being READ rather than produced -- so paper and
    # a pen, not a laboratory (that is About's picture) and not two people
    # comparing charts (that is Get Started Today's).
    "cat-clinical-reviews": (
        "A woman in her forties at a desk in a bright university office, holding "
        "a printed research paper in one hand and a pen in the other, mid-"
        "annotation, reading closely and unhurried. A short stack of journals to "
        "one side of the desk. A tall window at the left of the frame is blown "
        "out to near white. " + SHARED
    ),
    # Gastro Living. The section is about ordinary life continuing, so the
    # picture is somebody on their way out of the door -- not a consultation,
    # not a kitchen (the meal planner already has the kitchen).
    "cat-gastro-living": (
        "A man in his fifties sitting on the edge of an armchair in a bright "
        "front room, lacing a walking boot, a coat over the back of the chair "
        "beside him, about to head out for the day. Capable and ordinary, "
        "nothing clinical in the room. A large window on the left of the frame "
        "floods that edge with clean daylight. " + SHARED
    ),
    # Healthcare News. Movement and immediacy, and the only one of the three
    # with more than one person -- caught mid-sentence rather than posed.
    "cat-healthcare-news": (
        "Three colleagues standing at the end of a long table in a bright "
        "open-plan office, mid-conversation, one gesturing while the others "
        "listen, printed pages and coffee cups on the table between them. "
        "Caught in the middle of a sentence rather than posed. A glass wall on "
        "the left of the frame washes that edge almost white. " + SHARED
    ),
})


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
