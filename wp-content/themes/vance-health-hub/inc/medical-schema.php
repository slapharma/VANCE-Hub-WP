<?php
/**
 * MedicalWebPage and MedicalCondition schema for the seven condition pages.
 *
 * The pages are a clinical resource but were describing themselves to Google as
 * a plain WebPage, with nothing to say what condition each one is about. This
 * upgrades the page node to MedicalWebPage and attaches a MedicalCondition
 * describing the condition itself.
 *
 * It hooks AIOSEO's own graph rather than printing a second JSON-LD block. That
 * is deliberate: the site already carried two competing schema blocks naming
 * two different publishers, and the fix for that was to end up with one graph.
 * Adding a separate block here would recreate exactly that problem.
 *
 * Everything asserted below is taken from the page it describes. Symptom, test,
 * treatment and prevention lists are the page's own bullet lists, condensed to
 * names; the epidemiology lines are the page's own statistic blocks. Where a
 * page presents diagnosis or treatment as prose rather than a list, no
 * typicalTest or possibleTreatment is claimed — the schema never asserts more
 * than the page supports.
 *
 * Deliberately absent: `lastReviewed` and `reviewedBy`. The pages state that a
 * clinician checks them, but name nobody and give no date, and inventing either
 * would be fabricating a medical credential. Both become available the moment
 * named reviewers exist.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Medical facts per condition, keyed by page slug.
 *
 * Short descriptions are pulled from vance_gi_condition_cards() so the schema
 * and the tiles that link to these pages can never drift apart.
 *
 * @return array
 */
function vance_gi_condition_medical() {
	static $cache = null;
	if ( $cache !== null ) {
		return $cache;
	}

	// Canonical short descriptions, so there is one source of truth.
	$desc = array();
	if ( function_exists( 'vance_gi_condition_cards' ) ) {
		foreach ( vance_gi_condition_cards() as $card ) {
			$desc[ $card['slug'] ] = html_entity_decode( wp_strip_all_tags( $card['desc'] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		}
	}

	$cache = array(
		'inflammatory-bowel-disease' => array(
			'name'         => 'Inflammatory Bowel Disease',
			'alternate'    => 'IBD',
			'anatomy'      => 'Gastrointestinal tract',
			'epidemiology' => 'Around 500,000 people in the UK are estimated to live with inflammatory bowel disease.',
			'symptoms'     => array(
				'Persistent diarrhoea',
				'Blood or mucus in the stool',
				'Abdominal pain and cramping',
				'Fatigue',
				'Unintended weight loss',
				'Loss of appetite',
				'Frequent or urgent need to empty the bowel',
			),
		),

		'ulcerative-colitis' => array(
			'name'         => 'Ulcerative Colitis',
			'alternate'    => 'UC',
			'anatomy'      => 'Colon and rectum',
			'epidemiology' => 'Around 1 in 420 people in the UK are estimated to live with ulcerative colitis.',
			'symptoms'     => array(
				'Diarrhoea containing blood or mucus',
				'Urgent and frequent need to empty the bowel',
				'Abdominal pain and cramping',
				'Tenesmus',
				'Fatigue',
				'Weight loss',
				'Reduced appetite during flares',
			),
		),

		'crohns-disease' => array(
			'name'         => 'Crohn’s Disease',
			'alternate'    => 'Crohn disease',
			'anatomy'      => 'Gastrointestinal tract, most commonly the terminal ileum',
			'epidemiology' => 'An estimated 115,000 to 250,000 people in the UK live with Crohn’s disease.',
			'symptoms'     => array(
				'Abdominal pain and cramping, often in the lower right side',
				'Persistent or recurring diarrhoea',
				'Fatigue',
				'Unintended weight loss',
				'Blood or mucus in the stool',
				'Mouth ulcers',
				'Soreness around the anus',
			),
		),

		'microscopic-colitis' => array(
			'name'         => 'Microscopic Colitis',
			'alternate'    => '',
			'anatomy'      => 'Colon',
			'epidemiology' => 'Most commonly affects women over the age of 60. The colon usually looks normal during colonoscopy.',
			'symptoms'     => array(
				'Chronic watery diarrhoea that is not bloody',
				'Urgent need to empty the bowel, sometimes at night',
				'Abdominal pain or cramps',
				'Faecal incontinence',
				'Fatigue',
				'Mild weight loss',
			),
		),

		'irritable-bowel-syndrome' => array(
			'name'         => 'Irritable Bowel Syndrome',
			'alternate'    => 'IBS',
			'anatomy'      => 'Gastrointestinal tract',
			'epidemiology' => 'Around 1 in 7 adults are thought to have IBS symptoms, and it is about twice as common in women as in men.',
			'symptoms'     => array(
				'Abdominal pain or cramping, often relieved by passing a stool',
				'Bloating and a swollen abdomen',
				'Diarrhoea',
				'Constipation',
				'Excess wind',
				'A feeling of not having fully emptied the bowel',
				'Mucus in the stool',
			),
			'tests'        => array(
				'Blood tests to check for anaemia and inflammation',
				'Coeliac disease test',
				'Faecal calprotectin stool test',
			),
		),

		'colorectal-cancer' => array(
			'name'         => 'Colorectal Cancer',
			'alternate'    => 'Bowel cancer',
			'anatomy'      => 'Colon and rectum',
			'epidemiology' => 'The fourth most common cancer in the UK. Around 9 in 10 people survive bowel cancer when it is found at the earliest stage.',
			'symptoms'     => array(
				'Bleeding from the back passage',
				'Blood in the stool',
				'A lasting change in bowel habit',
				'Abdominal pain, bloating or discomfort',
				'Unintended weight loss',
				'Tiredness or breathlessness from unexplained anaemia',
			),
			'prevention'   => array(
				'Eating plenty of fibre, fruit and vegetables',
				'Limiting red and processed meat and alcohol',
				'Keeping to a healthy weight',
				'Staying physically active',
				'Not smoking',
				'Taking part in bowel cancer screening when invited',
			),
		),

		'diverticular-disease' => array(
			'name'         => 'Diverticular Disease',
			'alternate'    => '',
			'anatomy'      => 'Colon',
			'epidemiology' => 'More than half of people over 50 have diverticula, and around a quarter will develop symptoms at some point.',
			'symptoms'     => array(
				'Abdominal pain, usually in the lower left side',
				'Pain that eases after passing wind or a stool',
				'Bloating',
				'Constipation',
				'Diarrhoea',
				'Blood in the stool',
				'Fever',
				'Nausea or vomiting',
			),
			'tests'        => array(
				'CT scan',
				'Colonoscopy',
				'CT colonography',
				'Blood tests to check for signs of infection',
			),
			'treatments'   => array(
				'A high-fibre diet, increased gradually',
				'Plenty of fluids',
				'Managing constipation',
				'Simple pain relief such as paracetamol',
				'Antibiotics',
				'Hospital treatment for more serious cases',
				'Surgery for complications',
			),
		),
	);

	foreach ( $cache as $slug => $data ) {
		$cache[ $slug ]['description'] = $desc[ $slug ] ?? '';
	}

	return $cache;
}

/**
 * The condition data for the page being rendered, or null.
 *
 * @return array|null
 */
function vance_current_gi_condition() {
	if ( ! is_page() ) {
		return null;
	}

	$slug = get_post_field( 'post_name', get_queried_object_id() );
	$all  = vance_gi_condition_medical();

	return $all[ $slug ] ?? null;
}

/**
 * Swap AIOSEO's WebPage graph for MedicalWebPage on the condition pages.
 *
 * @param  array $graphs Graph names AIOSEO intends to output.
 * @return array
 */
function vance_medical_schema_graphs( $graphs ) {
	if ( ! is_array( $graphs ) || ! vance_current_gi_condition() ) {
		return $graphs;
	}

	foreach ( $graphs as $i => $graph ) {
		if ( $graph === 'WebPage' ) {
			$graphs[ $i ] = 'MedicalWebPage';
		}
	}

	return $graphs;
}
add_filter( 'aioseo_schema_graphs', 'vance_medical_schema_graphs' );

/**
 * Build a list of typed schema nodes from a list of names.
 *
 * @param  array  $names Item names.
 * @param  string $type  schema.org type.
 * @return array
 */
function vance_medical_schema_nodes( $names, $type ) {
	$out = array();
	foreach ( (array) $names as $name ) {
		$name = trim( (string) $name );
		if ( $name === '' ) {
			continue;
		}
		$out[] = array(
			'@type' => $type,
			'name'  => $name,
		);
	}

	return $out;
}

/**
 * Attach the MedicalCondition and point the page node at it.
 *
 * @param  array $graph The assembled @graph array.
 * @return array
 */
function vance_medical_schema_output( $graph ) {
	$condition = vance_current_gi_condition();
	if ( ! is_array( $graph ) || ! $condition ) {
		return $graph;
	}

	$url         = get_permalink( get_queried_object_id() );
	$conditionId = $url . '#medicalcondition';

	$node = array(
		'@type'       => 'MedicalCondition',
		'@id'         => $conditionId,
		'name'        => $condition['name'],
		'description' => $condition['description'],
		'url'         => $url,
	);

	if ( ! empty( $condition['alternate'] ) ) {
		$node['alternateName'] = $condition['alternate'];
	}
	if ( ! empty( $condition['anatomy'] ) ) {
		$node['associatedAnatomy'] = array(
			'@type' => 'AnatomicalStructure',
			'name'  => $condition['anatomy'],
		);
	}
	if ( ! empty( $condition['epidemiology'] ) ) {
		$node['epidemiology'] = $condition['epidemiology'];
	}
	if ( ! empty( $condition['symptoms'] ) ) {
		$node['signOrSymptom'] = vance_medical_schema_nodes( $condition['symptoms'], 'MedicalSignOrSymptom' );
	}
	if ( ! empty( $condition['tests'] ) ) {
		$node['typicalTest'] = vance_medical_schema_nodes( $condition['tests'], 'MedicalTest' );
	}
	if ( ! empty( $condition['treatments'] ) ) {
		$node['possibleTreatment'] = vance_medical_schema_nodes( $condition['treatments'], 'MedicalTherapy' );
	}
	if ( ! empty( $condition['prevention'] ) ) {
		$node['primaryPrevention'] = vance_medical_schema_nodes( $condition['prevention'], 'MedicalTherapy' );
	}

	// Point the page node at the condition, and mark who the page is written for.
	foreach ( $graph as $i => $entry ) {
		if ( ! is_array( $entry ) || ! isset( $entry['@type'] ) ) {
			continue;
		}
		if ( $entry['@type'] !== 'MedicalWebPage' ) {
			continue;
		}

		$graph[ $i ]['about']          = array( '@id' => $conditionId );
		$graph[ $i ]['specialty']      = 'https://schema.org/Gastroenterologic';
		$graph[ $i ]['medicalAudience'] = 'https://schema.org/Patient';
		break;
	}

	$graph[] = $node;

	return $graph;
}
add_filter( 'aioseo_schema_output', 'vance_medical_schema_output' );
