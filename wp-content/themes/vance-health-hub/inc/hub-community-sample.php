<?php
/**
 * Community sample data for a team review.
 *
 * Twenty-two questions with replies across the eight spaces, so the team can see
 * the community page with real-looking content before members post. Only staff
 * (users with vance_community_cap(): administrators and editors) see these
 * posts. Visitors and members see only real posts.
 *
 * No user accounts are made. Each sample question and reply has:
 *   _vance_community_sample       '1' (the marker)
 *   _vance_community_sample_name  the name to show
 * post_author and user_id are 0. The 'vance_community_author_name' filter shows
 * the stored name.
 *
 * The hiding is NOT in this file: inc/hub-community.php keeps marked questions
 * out of the feed (vance_community_sample_feed_args) and closes them to every
 * member action (vance_community_sample_hidden) for everyone who is not staff.
 * So the samples stay hidden even if this file is unloaded. Remove the data
 * before you take this file out, or the names show as "Member".
 *
 * Me-too and Helpful counts come from stored rows, as for real posts. A sample
 * row has the value '0', so it can never match a real member.
 *
 * Admin: a box on VanceHealthHub > Community queue loads and removes the data
 * (manage_options only). Remove deletes every sample question and all its
 * replies, real staff replies on those questions included.
 *
 * Needs inc/hub-community.php (vance_community_cap, vance_community_spaces,
 * vance_community_sample_key).
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The sample questions. Each row:
 *   0 space slug, 1 asker, 2 days ago, 3 me-too count, 4 title, 5 text,
 *   6 replies: list of array( name, helpful count, text ).
 *
 * Members share experience only: no medical advice, no doses, no product names.
 *
 * @return array[]
 */
function vance_community_sample_data() {
	return array(
		array( 'ibd', 'Sarah', 1, 6, "How do you explain IBD to a new manager?",
			"I start a new job next month. I do not know how much to tell my manager about my IBD. Did you tell yours on day one, or wait? How did you put it into words?",
			array(
				array( 'Jas', 6, "I waited until my first one-to-one. I kept it short: I have a long-term bowel condition, most days are fine, and some days I need quick access to a toilet. That was enough." ),
				array( 'Claire', 3, "I told HR first and asked them to be there. It felt less awkward with someone else in the room." ),
				array( 'LongHaulLee', 5, "I printed a one-page leaflet from an IBD charity. My manager read it and asked good questions after." ),
				array( 'Sarah', 1, "Thank you all. I like the idea of keeping it short. I will try that at my first one-to-one." ),
			),
		),
		array( 'ibd', 'Tom', 3, 11, "Tiredness that sleep does not fix",
			"My bloods are fine and my gut is calm at the moment, but I am still exhausted by mid-afternoon. Does anyone else get this, even in remission? What helps you get through the day?",
			array(
				array( 'Priya', 9, "Yes, every day. My IBD nurse told me fatigue is common even when things are quiet. It helped just to know I was not imagining it." ),
				array( 'Dave', 7, "I plan the hard jobs for the morning now. After 3pm I only do easy tasks." ),
				array( 'Gemma', 4, "A short walk at lunch helps me more than a nap. A nap makes me feel worse." ),
				array( 'Aisha', 5, "I keep a simple diary of sleep, food and energy. It showed that my bad days came after late nights. Obvious now, but I did not see it before." ),
				array( 'Rhys', 6, "Ask your team about it at your next review. Mine checked a few more things that the routine blood test did not cover." ),
				array( 'Mo', 2, "Same here. Some weeks are better than others. Be kind to yourself." ),
				array( 'Tom', 1, "Really helpful, thanks. I will start a diary and bring it up at my next appointment." ),
			),
		),
		array( 'ibd', 'Hannah', 9, 4, "First colonoscopy next week. What is the prep really like?",
			"I am more nervous about the bowel prep than the test. Any tips for the day before?",
			array(
				array( 'Fiona', 8, "Stay near the toilet all evening and have soft toilet tissue and a barrier cream ready. Put a film on. It is not fun, but it is one evening." ),
				array( 'Kwame', 6, "Chilling the drink made it easier for me. Follow the hospital sheet exactly, because they know your plan." ),
			),
		),
		array( 'ulcerative-colitis', 'Beth', 2, 14, "Living with urgency on the school run",
			"Since my last flare, mornings are the hardest. The school run is 20 minutes and I panic about the walk back. How do you manage urgency when you have kids with you?",
			array(
				array( 'Niamh', 8, "I know every toilet on our route now: the cafe, the library, the petrol station. Having a plan calms me down, and that helps on its own." ),
				array( 'Jo', 9, "Get a Can't Wait card and a RADAR key. I keep both in my coat. I have only used the key twice, but it gives me confidence." ),
				array( 'Ollie', 4, "My partner does the morning run on bad weeks and I do the afternoon one. It took me a long time to ask." ),
				array( 'Megan', 7, "I keep a small kit in the buggy: wipes, spare pants and a bag. I hope I never need it, but I worry less." ),
				array( 'Sunil', 5, "Talk to your IBD team if mornings are this bad. Mine changed my plan after a flare, and the urgency got better over a few weeks." ),
				array( 'Gutsy_Gill', 3, "You are doing really well. Most people would not guess what you deal with before 9am." ),
				array( 'Claire', 4, "I leave 10 minutes earlier so I never have to rush. Rushing makes it worse for me." ),
				array( 'Mum2Ellie', 6, "Tell the school. Our school office lets me use the staff toilet, no questions asked." ),
				array( 'Beth', 2, "I did not know about the RADAR key. I have ordered one. Thank you, everyone." ),
			),
		),
		array( 'ulcerative-colitis', 'Liam', 6, 3, "Does anyone track flares in an app?",
			"I want to keep a record of my symptoms to show my consultant. Paper or app? What do you use?",
			array(
				array( 'Priya', 4, "I use the notes app on my phone: the date, how many trips, and a score out of 10. My consultant liked that it was simple." ),
			),
		),
		array( 'ulcerative-colitis', 'Jas', 13, 9, "Going back to the gym after a long flare",
			"I have not trained for six months. I feel weak and a bit scared that exercise will set things off. How did you start again?",
			array(
				array( 'Dave', 6, "I started with walking, then swimming. Swimming felt gentle, and the changing rooms have toilets right there." ),
				array( 'Rhys', 5, "Ten minutes, three times a week, for the first month. It felt silly, but I did not crash." ),
				array( 'Hannah', 3, "I told the gym staff. They showed me the nearest toilets to each area. Small thing, big help." ),
				array( 'Fiona', 4, "Ask your IBD nurse first if you are still on a reducing plan. Mine gave me a rough idea of when to start." ),
				array( 'Jas', 1, "Thanks. Walking first, then the pool. I will check with my nurse too." ),
			),
		),
		array( 'crohns-disease', 'CrohnieKate', 4, 18, "What do you say when people ask why you are so thin?",
			"I lost a lot of weight before my diagnosis. People keep commenting, and some think it is a compliment. How do you answer without telling your life story?",
			array(
				array( 'Aisha', 9, "I say: I have a health condition and I am working on it with my doctors. Then I change the subject. Most people get the hint." ),
				array( 'Mo', 5, "Honestly, sometimes I just say thanks and move on. I do not owe everyone an explanation." ),
				array( 'TeaAndToast', 7, "Family were the worst for this. I told them once, properly, and asked them to stop. They did." ),
				array( 'Kwame', 6, "It still hurts when people say it. You are not alone in that." ),
				array( 'Megan', 8, "My dietitian helped me a lot when I was trying to put weight back on. If you have not seen one, ask your team for a referral." ),
				array( 'Ollie', 3, "I wear clothes that fit me now instead of baggy ones. Odd, but I feel less on show." ),
				array( 'Gemma', 6, "'I have Crohn's, and it affects how I absorb food.' Short and true. People usually say sorry, and that is the end of it." ),
				array( 'Sunil', 4, "Some people mean well and some do not. I save my energy for the ones who mean well." ),
				array( 'LongHaulLee', 2, "It took me two years to stop caring. You will get there." ),
				array( 'CrohnieKate', 1, "These are great. I am going to use Gemma's line. Thank you." ),
			),
		),
		array( 'crohns-disease', 'Niamh', 11, 5, "Hospital bag: what do you always pack?",
			"I have had three admissions this year and I always forget something. What is in your bag?",
			array(
				array( 'Jo', 9, "A long phone charger, lip balm, an eye mask, earplugs, my own pillowcase, and a paper list of my medicines." ),
				array( 'Beth', 5, "Snacks that I know agree with me, and slippers with a hard sole. Hospital floors are cold." ),
				array( 'Liam', 7, "A notebook. I write down what each doctor says, because I forget it by the afternoon." ),
			),
		),
		array( 'crohns-disease', 'Rhys', 20, 7, "Telling a new partner about Crohn's",
			"I have been seeing someone for a few weeks. It is going well and I want to tell them before it gets serious. When did you tell yours?",
			array(
				array( 'Hannah', 8, "On the third date. I said it like a fact, not a confession. He asked one question and then told me about his asthma." ),
				array( 'Claire', 6, "I waited too long, and it came out during a bad flare. I wish I had told him earlier, when I was well." ),
				array( 'Ollie', 5, "If they are the right person, it will not change much. Mine now knows more about my condition than I do." ),
				array( 'Gutsy_Gill', 4, "I sent a link to a good explainer page first, then we talked. It took the pressure off." ),
				array( 'Mo', 3, "Choose a calm moment, not a night out. You want room to talk." ),
				array( 'Rhys', 2, "Thanks, all. I will tell them this weekend, on a quiet night in." ),
			),
		),
		array( 'diverticular-disease', 'Brian', 3, 9, "Just told I have diverticular disease. What helped you at the start?",
			"I am 61 and the scan found it after a bad week of pain. The nurse gave me a leaflet, but I have lots of questions. What did you wish you knew at the start?",
			array(
				array( 'Pat', 7, "That I was not the only one. Two friends of mine turned out to have it too. That calmed me down a lot." ),
				array( 'Fiona', 5, "Write your questions down before each appointment. I forgot half of mine the first time." ),
				array( 'Dave', 6, "Ask your GP what to look out for, and when to call. Having a plan made me less anxious." ),
				array( 'Brian', 1, "Thank you. I will make a list for my GP." ),
			),
		),
		array( 'diverticular-disease', 'Pat', 10, 5, "Getting back to walking after a flare",
			"I had diverticulitis last month and I am finally feeling more like me. I used to walk every day. How did you build back up?",
			array(
				array( 'Brian', 4, "Short walks round the block first. I added five minutes each week." ),
				array( 'TeaAndToast', 3, "I joined a walking group. The slower pace was right for me, and the chat was a bonus." ),
				array( 'Pat', 1, "A walking group is a good idea. There is one at my library." ),
			),
		),
		array( 'ibs', 'Gemma', 1, 21, "Bloating by the end of the day. Anyone else?",
			"I start the day with a flat stomach, and by 6pm I look six months pregnant. It is uncomfortable and I have stopped wearing jeans. Does this happen to you?",
			array(
				array( 'Fiona', 7, "Every single day. Elastic waists are my best friends." ),
				array( 'Aisha', 6, "Yes. Smaller meals more often helped me a bit, but they did not stop it." ),
				array( 'Tom', 9, "My GP referred me to a dietitian, who took me through the low FODMAP steps. It was hard work, but I found my two big triggers." ),
				array( 'Priya', 5, "I keep a food and symptom diary for two weeks at a time. Patterns show up that I would never guess." ),
				array( 'Sunil', 6, "Stress makes mine far worse. Some days it has nothing to do with food." ),
				array( 'TeaAndToast', 4, "A warm wheat bag on my stomach in the evening makes me feel better, even if the bloating stays." ),
				array( 'Jo', 8, "If it is new or getting worse, see your GP. Mine wanted to rule a few things out first, which was reassuring." ),
				array( 'Gemma', 1, "So many of you! I will start a diary and ask my GP about a dietitian." ),
			),
		),
		array( 'ibs', 'Kwame', 7, 6, "Travelling by train with IBS",
			"I have a four-hour train journey next month. Any tips?",
			array(
				array( 'Megan', 6, "Book a seat near the toilet and eat lightly the day before. I always carry wipes and a spare top." ),
				array( 'Dave', 5, "Check in the app that the train has a working toilet before you leave. I have been caught out." ),
			),
		),
		array( 'ibs', 'Mum2Ellie', 15, 2, "My teenage daughter was just diagnosed with IBS",
			"My daughter is 14. She is embarrassed and does not want to talk about it. How did you help your teenager?",
			array(
				array( 'Gutsy_Gill', 7, "Let her lead. My son talked to me in the car, never face to face." ),
				array( 'Beth', 8, "We got a letter from the GP for school, so she can leave class without asking. It made a big difference." ),
				array( 'Claire', 5, "Find her an online group for teens. Hearing it from people her age helped my daughter more than anything I said." ),
				array( 'Niamh', 6, "I was that teenager. What helped was my mum not making it a big deal at the dinner table." ),
				array( 'Mum2Ellie', 1, "The school letter is a great idea. I will ask the GP this week." ),
			),
		),
		array( 'ibs', 'LongHaulLee', 22, 10, "Does anxiety make your IBS worse?",
			"My gut gets bad before big meetings. Then I worry about my gut, which makes it worse. Is anyone else stuck in this loop?",
			array(
				array( 'Jas', 8, "Exactly this. My GP suggested gut-directed hypnotherapy. I was sceptical, but it helped me." ),
				array( 'Hannah', 7, "Breathing before meetings: in for four, hold, out for six. It sounds simple, but it works for me." ),
				array( 'Rhys', 3, "I put the worst meetings in the morning now, when my gut is calmer." ),
				array( 'Liam', 6, "Talking therapy helped me with the worry part. In many areas the NHS lets you refer yourself." ),
				array( 'Priya', 4, "The loop is real. Knowing that it is a loop helped me break it a little." ),
				array( 'Sunil', 3, "Exercise helps me more than anything. Even a run round the block." ),
				array( 'LongHaulLee', 1, "Lots to try here. I will look up the self-referral service." ),
			),
		),
		array( 'food-and-recipes', 'Ollie', 5, 3, "Eating out: do you call the restaurant first?",
			"I want to go out for my birthday, but I get anxious about the menu. Do you phone ahead?",
			array(
				array( 'Fiona', 5, "Yes, always. I ask what they can make plain, without onion or garlic. Most places are kind about it, and I relax more." ),
			),
		),
		array( 'food-and-recipes', 'TeaAndToast', 8, 15, "Easy batch-cook meals that are gentle on your gut?",
			"Cooking from scratch is hard on low-energy days. What do you make in bulk and freeze?",
			array(
				array( 'Megan', 8, "Chicken and rice soup. I make a big pot on a good day and freeze it in single portions." ),
				array( 'Kwame', 5, "A plain fish pie with mash on top. It reheats well and it is soft." ),
				array( 'Gemma', 4, "Porridge pots. I portion the oats the night before, so breakfast takes no thought." ),
				array( 'Claire', 6, "Shepherd's pie with the veg cooked down well. My whole family eats it." ),
				array( 'Mo', 3, "Egg fried rice with the veg that I know is safe for me. Fast to reheat." ),
				array( 'Aisha', 7, "The recipe section on this site lets you filter by meal. I found two freezer meals there." ),
				array( 'Jo', 6, "Label everything with the date and what is in it. Future you will be grateful on a bad day." ),
				array( 'CrohnieKate', 5, "Bananas and toast on the really bad days. No shame in it." ),
				array( 'TeaAndToast', 1, "Brilliant list. Soup and fish pie are on my plan this weekend." ),
			),
		),
		array( 'food-and-recipes', 'Sunil', 17, 5, "Fibre: how do you find your balance?",
			"When I am well I want to eat more veg, but too much at once sets me off. How did you find the amount that works for you?",
			array(
				array( 'Tom', 7, "Small steps over weeks, not days. I also keep a note of what I added and when." ),
				array( 'Hannah', 6, "A dietitian helped me with this. The answer for me was different from the answer for my friend with the same condition." ),
				array( 'Niamh', 5, "Cooked and peeled veg suit me better than raw. Soups too." ),
				array( 'Sunil', 1, "Small steps and a note. I can do that." ),
			),
		),
		array( 'work-and-travel', 'Aisha', 10, 8, "Asking for reasonable adjustments at work",
			"I want to ask for a desk near the toilets and the option to work from home on flare days. Has anyone done this? How did it go?",
			array(
				array( 'Jo', 9, "I put it in writing to my manager and HR, and I said that it was under the Equality Act. They agreed to both within a week." ),
				array( 'Liam', 7, "Occupational Health did an assessment and wrote the recommendations. That made it easy for my manager to say yes." ),
				array( 'Dave', 6, "Be specific. 'Home working on flare days, with notice by 8am' worked better than 'some flexibility'." ),
			),
		),
		array( 'work-and-travel', 'LongHaulLee', 18, 9, "Long-haul flight with IBD: any tips?",
			"I am flying to see family overseas, and the flight is 12 hours. I am worried about the toilet queues and the food. What do you do?",
			array(
				array( 'Ollie', 8, "Pay for an aisle seat near the toilets at the back. Worth every penny." ),
				array( 'Gemma', 7, "Pack your own food. I bring plain crackers, rice cakes and a sandwich that I trust." ),
				array( 'Megan', 9, "Carry your medicines in your hand luggage, with a letter from your GP or IBD team." ),
				array( 'Kwame', 8, "Check that your travel insurance covers your condition. Mine needed it declared." ),
				array( 'Fiona', 5, "Tell the cabin crew quietly when you board. They were very kind to me." ),
				array( 'LongHaulLee', 2, "Insurance! I had not even thought of that. Thank you." ),
			),
		),
		array( 'carers', 'Gaz', 12, 16, "How do I help my wife through a bad flare without fussing?",
			"My wife is in the middle of a bad flare. I want to help, but she says I fuss too much. What did your partner do that actually helped?",
			array(
				array( 'Beth', 9, "Take jobs off the list without asking. Bins out, school run done, dinner sorted. No questions." ),
				array( 'Claire', 8, "Ask once: what would help today? Then do exactly that and stop asking." ),
				array( 'CrohnieKate', 7, "Do not comment on how much she eats. Ever. Just put the food there." ),
				array( 'Rhys', 6, "My partner came to my appointments and took notes. I could not take it all in." ),
				array( 'Niamh', 5, "Let her be grumpy. It is the flare and the tiredness talking, not you." ),
				array( 'Jas', 7, "Look after yourself too. You cannot help if you burn out. Carers UK has good advice." ),
				array( 'Priya', 6, "My husband learned to read my face. When we arrived somewhere, he would say 'car or toilet first?' It made me laugh." ),
				array( 'TeaAndToast', 4, "A hot water bottle, fresh sheets, the remote within reach. Small things." ),
				array( 'Gutsy_Gill', 3, "You are asking, so you are already doing well." ),
				array( 'Gaz', 1, "This is really helpful. I will ask her once and then just do it." ),
			),
		),
		array( 'carers', 'Shona', 24, 4, "Caring for my dad after his stoma surgery",
			"Dad came home last week with a stoma. He is managing, but he gets upset when there is a leak. How can I support him?",
			array(
				array( 'Sunil', 8, "Ask the stoma nurse to visit again. They solved our leak problem with a better bag fit." ),
				array( 'Megan', 6, "Let him do the changes himself as far as he can. My dad wanted to keep that part private, and it helped his confidence." ),
			),
		),
	);
}

/**
 * IDs of every sample question, in any status.
 *
 * @return int[]
 */
function vance_community_sample_ids() {
	return array_map( 'intval', (array) get_posts( array(
		'post_type'      => 'vance_question',
		'post_status'    => array( 'publish', 'pending', 'draft', 'private', 'trash' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_key'       => vance_community_sample_key(), // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery
		'no_found_rows'  => true,
	) ) );
}

/**
 * Number of sample questions that exist.
 *
 * @return int
 */
function vance_community_sample_count() {
	return count( vance_community_sample_ids() );
}

/**
 * Add $n stored rows with the value '0' (never a real member).
 *
 * @param string $type 'post' or 'comment'.
 * @param int    $id   Object ID.
 * @param string $key  Meta key.
 * @param int    $n    Rows to add.
 */
function vance_community_sample_rows( $type, $id, $key, $n ) {
	for ( $i = 0; $i < (int) $n; $i++ ) {
		add_metadata( $type, (int) $id, $key, '0', false );
	}
}

/**
 * The option that locks a load in progress.
 *
 * @return string
 */
function vance_community_sample_lock_key() {
	return 'vance_community_sample_lock';
}

/**
 * Load the sample data. Does nothing when sample questions already exist, or
 * while another load holds the lock (a double click, or two admins at once).
 * add_option() is atomic: only one request can create the lock row. A lock
 * older than ten minutes is from a load that died, and is taken over.
 *
 * @param int|null $now Unix time to date the posts from (tests pass one).
 * @return int Questions created (0 when refused).
 */
function vance_community_sample_load( $now = null ) {
	$lock = vance_community_sample_lock_key();
	if ( ! add_option( $lock, (string) time(), '', 'no' ) ) {
		if ( time() - (int) get_option( $lock, 0 ) < 10 * MINUTE_IN_SECONDS ) {
			return 0;
		}
		// A dead load's lock: delete it, then take it with the same atomic add,
		// so two requests that both see the old lock cannot both win.
		delete_option( $lock );
		if ( ! add_option( $lock, (string) time(), '', 'no' ) ) {
			return 0;
		}
	}
	try {
		return vance_community_sample_count() > 0 ? 0 : vance_community_sample_insert( $now );
	} finally {
		delete_option( $lock );
	}
}

/**
 * Insert every sample question and reply. Call vance_community_sample_load().
 *
 * @param int|null $now Unix time to date the posts from.
 * @return int Questions created.
 */
function vance_community_sample_insert( $now = null ) {
	$now     = null === $now ? time() : (int) $now;
	$key     = vance_community_sample_key();
	$created = 0;
	foreach ( vance_community_sample_data() as $i => $row ) {
		list( $space, $asker, $days, $metoo, $title, $text, $replies ) = $row;

		// A different hour of the day for each question, never in the future.
		$q_ts = $now - ( (int) $days * DAY_IN_SECONDS ) - ( ( $i * 5 ) % 9 + 1 ) * HOUR_IN_SECONDS;
		$id   = wp_insert_post( array(
			'post_type'     => 'vance_question',
			'post_status'   => 'publish',
			'post_author'   => 0,
			'post_title'    => $title,
			'post_content'  => $text,
			'post_date'     => wp_date( 'Y-m-d H:i:s', $q_ts ),
			'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $q_ts ),
			'meta_input'    => array(
				$key                            => '1',
				'_vance_community_sample_name'  => $asker,
				'_vance_community_state'        => 'approved',
			),
		), true );
		if ( is_wp_error( $id ) || ! $id ) {
			continue;
		}
		$created++;

		$term = get_term_by( 'slug', $space, 'vance_space' );
		if ( $term ) {
			wp_set_object_terms( (int) $id, array( (int) $term->term_id ), 'vance_space', false );
		}
		vance_community_sample_rows( 'post', $id, '_vance_community_metoo', $metoo );

		// Replies follow the question in order, all before $now.
		$step = (int) min( 3 * HOUR_IN_SECONDS, floor( ( $now - $q_ts ) / ( count( $replies ) + 1 ) ) );
		foreach ( $replies as $n => $reply ) {
			list( $name, $helpful, $body ) = $reply;
			$r_ts = $q_ts + ( $n + 1 ) * $step;
			$cid  = wp_insert_comment( array(
				'comment_post_ID'      => (int) $id,
				'comment_content'      => $body,
				'comment_type'         => 'vance_reply',
				'comment_approved'     => 1,
				'user_id'              => 0,
				'comment_author'       => $name,
				'comment_author_email' => '',
				'comment_date'         => wp_date( 'Y-m-d H:i:s', $r_ts ),
				'comment_date_gmt'     => gmdate( 'Y-m-d H:i:s', $r_ts ),
			) );
			if ( ! $cid ) {
				continue;
			}
			update_comment_meta( $cid, $key, '1' );
			update_comment_meta( $cid, '_vance_community_sample_name', $name );
			update_comment_meta( $cid, '_vance_community_state', 'approved' );
			vance_community_sample_rows( 'comment', $cid, '_vance_community_helpful', $helpful );
		}
	}
	return $created;
}

/**
 * Delete every sample question with all its replies.
 *
 * @return int Questions deleted.
 */
function vance_community_sample_remove() {
	$deleted = 0;
	foreach ( vance_community_sample_ids() as $id ) {
		if ( wp_delete_post( $id, true ) ) {
			$deleted++;
		}
	}
	return $deleted;
}

/**
 * Show the stored name on a sample question or reply that has no author.
 *
 * @param string $name    Name so far.
 * @param string $kind    'question' or 'reply'.
 * @param int    $id      Post or comment ID.
 * @param int    $user_id Author's user ID.
 * @return string
 */
function vance_community_sample_author_name( $name, $kind, $id, $user_id ) {
	if ( (int) $user_id > 0 ) {
		return $name;
	}
	$type = 'reply' === $kind ? 'comment' : 'post';
	if ( '1' !== (string) get_metadata( $type, (int) $id, vance_community_sample_key(), true ) ) {
		return $name;
	}
	$stored = trim( (string) get_metadata( $type, (int) $id, '_vance_community_sample_name', true ) );
	return '' !== $stored ? $stored : $name;
}
add_filter( 'vance_community_author_name', 'vance_community_sample_author_name', 10, 4 );

/**
 * The "Sample data for team review" box on the Community queue screen.
 * Empty for users without manage_options.
 *
 * @return string
 */
function vance_community_sample_box_html() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return '';
	}
	$count = vance_community_sample_count();
	$html  = '<div class="vcom-sample card" style="max-width:none;margin:16px 0;padding:12px 16px;">';
	$html .= '<h2 style="margin-top:0;">' . esc_html__( 'Sample data for team review', 'vance-health-hub' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Twenty-two sample questions with replies, across the eight spaces. Only administrators and editors see them on the community page. Visitors and members do not see them.', 'vance-health-hub' ) . '</p>';
	$html .= '<p><strong>' . esc_html( sprintf(
		/* translators: %s: number of sample questions. */
		__( 'Sample questions now: %s', 'vance-health-hub' ),
		number_format_i18n( $count )
	) ) . '</strong></p>';

	$html .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;margin-right:8px;">';
	// One nonce action per button, so a Load nonce cannot send a Remove.
	$html .= wp_nonce_field( 0 === $count ? 'vance_community_sample_load' : 'vance_community_sample_remove', '_wpnonce', true, false );
	if ( 0 === $count ) {
		$html .= '<input type="hidden" name="action" value="vance_community_sample_load">';
		$html .= '<button type="submit" class="button button-primary">' . esc_html__( 'Load sample data', 'vance-health-hub' ) . '</button>';
	} else {
		$html .= '<input type="hidden" name="action" value="vance_community_sample_remove">';
		$html .= '<button type="submit" class="button">' . esc_html__( 'Remove sample data', 'vance-health-hub' ) . '</button>';
		$html .= '<p class="description">' . esc_html__( 'Remove deletes every sample question and all its replies. This includes replies that staff wrote on a sample question. You cannot undo this.', 'vance-health-hub' ) . '</p>';
	}
	$html .= '</form></div>';
	return $html;
}

/**
 * The notice after a load or remove, from ?vcom_sample=.
 *
 * @return string
 */
function vance_community_sample_notice_html() {
	$done = isset( $_GET['vcom_sample'] ) && is_string( $_GET['vcom_sample'] ) ? sanitize_key( wp_unslash( $_GET['vcom_sample'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
	$map  = array(
		'loaded'  => array( 'success', __( 'Sample data loaded. Open the community page to see it.', 'vance-health-hub' ) ),
		'removed' => array( 'success', __( 'Sample data removed.', 'vance-health-hub' ) ),
		'exists'  => array( 'warning', __( 'Sample data is already loaded. Remove it first.', 'vance-health-hub' ) ),
		'failed'  => array( 'error', __( 'No sample data was loaded. Try again.', 'vance-health-hub' ) ),
	);
	if ( ! isset( $map[ $done ] ) ) {
		return '';
	}
	return '<div class="notice notice-' . esc_attr( $map[ $done ][0] ) . ' is-dismissible"><p>' . esc_html( $map[ $done ][1] ) . '</p></div>';
}

/**
 * Shared guard and redirect for the two admin-post actions.
 *
 * @param string $done Value for ?vcom_sample=.
 */
function vance_community_sample_redirect( $done ) {
	wp_safe_redirect( add_query_arg( array(
		'page'        => 'vance-community-queue',
		'tab'         => 'approved',
		'vcom_sample' => $done,
	), admin_url( 'admin.php' ) ) );
	exit;
}

/**
 * Refuse unless the user may manage options and the nonce is good.
 *
 * @param string $action Nonce action: vance_community_sample_load or _remove.
 */
function vance_community_sample_guard( $action ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You cannot change the sample data.', 'vance-health-hub' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( $action );
}

/**
 * admin-post: load the sample data.
 */
function vance_community_sample_load_handler() {
	vance_community_sample_guard( 'vance_community_sample_load' );
	if ( vance_community_sample_count() > 0 ) {
		vance_community_sample_redirect( 'exists' );
	}
	vance_community_sample_redirect( vance_community_sample_load() > 0 ? 'loaded' : 'failed' );
}
add_action( 'admin_post_vance_community_sample_load', 'vance_community_sample_load_handler' );

/**
 * admin-post: remove the sample data.
 */
function vance_community_sample_remove_handler() {
	vance_community_sample_guard( 'vance_community_sample_remove' );
	vance_community_sample_remove();
	vance_community_sample_redirect( 'removed' );
}
add_action( 'admin_post_vance_community_sample_remove', 'vance_community_sample_remove_handler' );
