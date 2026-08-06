<?php
/**
 * Recipe nutrition + ingredients — GENERATED, do not hand-edit.
 *
 * Derived from the IBD Recipes bundle chunk that holds the recipe array
 * (`assets/tools/ibd-recipes/_next/static/chunks/`). Mirrored into PHP so the
 * dashboard can build a shopping list and per-meal nutrition for a saved meal
 * plan without parsing a build artifact at runtime.
 *
 * Regenerate with LOCAL/gen_recipe_data.py after any change to the bundle's
 * recipe list. Curated fields (display name, image, audit status) live in
 * inc/recipe-catalogue.php instead — this file is machine output only.
 *
 * @package sla-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array<string, array{servings:int, prep:int, cook:int, nutrition:array<string,int>, ingredients:array}>
 */
function vance_recipe_data() {
	return array(
		'blueberry-chia-pudding' => array(
			'servings' => 1,
			'prep'     => 15,
			'cook'     => 0,
			'nutrition' => array( 'calories' => 350, 'protein' => 11, 'carbs' => 20, 'fat' => 25, 'fibre' => 14 ),
			'ingredients' => array(
				array(
					'section' => '',
					'items'   => array(
						'3 tbsp chia seeds',
						'200 ml unsweetened almond milk (or coconut milk)',
						'1 tbsp Greek yogurt or dairy-free yogurt',
						'½ cup blueberries',
						'1 tbsp chopped walnuts',
						'1 tbsp ground flaxseed',
						'½ tsp cinnamon',
						'Optional: drizzle of raw honey',
					),
				),
			),
		),
		'blueberry-almond-smoothie' => array(
			'servings' => 1,
			'prep'     => 5,
			'cook'     => 0,
			'nutrition' => array( 'calories' => 245, 'protein' => 6, 'carbs' => 25, 'fat' => 14, 'fibre' => 9 ),
			'ingredients' => array(
				array(
					'section' => '',
					'items'   => array(
						'1 cup frozen blueberries',
						'1 tbsp almond butter',
						'1 tbsp chia seeds',
						'1 cup unsweetened almond milk',
						'½ banana',
						'½ tsp cinnamon',
						'Optional: ice, 1 scoop protein powder',
					),
				),
			),
		),
		'gf-protein-pancakes' => array(
			'servings' => 2,
			'prep'     => 5,
			'cook'     => 10,
			'nutrition' => array( 'calories' => 350, 'protein' => 26, 'carbs' => 32, 'fat' => 13, 'fibre' => 5 ),
			'ingredients' => array(
				array(
					'section' => '',
					'items'   => array(
						'¾ cup gluten-free oat flour',
						'¼ cup vegan protein powder',
						'2 eggs',
						'½ cup unsweetened almond milk',
						'1 small ripe banana',
						'1 tsp baking powder',
						'½ tsp cinnamon',
						'1 tsp vanilla extract',
						'1 tsp olive oil or coconut oil',
						'Optional toppings: fresh berries, almond butter, dairy-free yogurt',
					),
				),
			),
		),
		'gf-protein-granola' => array(
			'servings' => 8,
			'prep'     => 10,
			'cook'     => 20,
			'nutrition' => array( 'calories' => 230, 'protein' => 9, 'carbs' => 20, 'fat' => 14, 'fibre' => 5 ),
			'ingredients' => array(
				array(
					'section' => '',
					'items'   => array(
						'2 cups gluten-free rolled oats',
						'½ cup chopped almonds',
						'¼ cup pumpkin seeds',
						'¼ cup sunflower seeds',
						'2 tbsp chia seeds',
						'¼ cup vegan protein powder',
						'2 tbsp coconut oil (melted)',
						'2 tbsp maple syrup or honey',
						'1 tsp cinnamon',
						'1 tsp vanilla extract',
						'Pinch of sea salt',
						'Optional: walnuts, coconut flakes, dried fruit (add after baking)',
					),
				),
			),
		),
		'harissa-eggs-avocado' => array(
			'servings' => 1,
			'prep'     => 5,
			'cook'     => 6,
			'nutrition' => array( 'calories' => 380, 'protein' => 20, 'carbs' => 22, 'fat' => 24, 'fibre' => 8 ),
			'ingredients' => array(
				array(
					'section' => '',
					'items'   => array(
						'1 slice sourdough bread (use gluten-free if required)',
						'½ avocado',
						'Squeeze of lemon juice',
						'2 eggs',
						'1 tsp olive oil',
						'1 tsp harissa paste',
						'Sea salt and black pepper',
						'Optional: chilli flakes, sesame seeds, fresh herbs',
					),
				),
			),
		),
		'mango-ginger-smoothie' => array(
			'servings' => 1,
			'prep'     => 5,
			'cook'     => 0,
			'nutrition' => array( 'calories' => 210, 'protein' => 3, 'carbs' => 36, 'fat' => 6, 'fibre' => 5 ),
			'ingredients' => array(
				array(
					'section' => '',
					'items'   => array(
						'1 cup frozen mango chunks',
						'½ banana',
						'1 cup unsweetened coconut milk',
						'1 tsp fresh grated ginger',
						'1 tbsp ground flaxseed',
						'Juice of ¼ lime',
						'Optional: ice, 1 scoop protein powder',
					),
				),
			),
		),
		'strawberry-chia-smoothie' => array(
			'servings' => 1,
			'prep'     => 5,
			'cook'     => 0,
			'nutrition' => array( 'calories' => 295, 'protein' => 8, 'carbs' => 35, 'fat' => 16, 'fibre' => 10 ),
			'ingredients' => array(
				array(
					'section' => '',
					'items'   => array(
						'1 cup frozen strawberries',
						'1 tbsp chia seeds',
						'1 cup unsweetened almond milk',
						'½ banana',
						'1 tbsp almond butter',
						'½ tsp vanilla extract',
						'Optional: ice, 1 scoop protein powder',
					),
				),
			),
		),
		'crispy-chickpea-salad' => array(
			'servings' => 2,
			'prep'     => 10,
			'cook'     => 15,
			'nutrition' => array( 'calories' => 330, 'protein' => 9, 'carbs' => 24, 'fat' => 24, 'fibre' => 10 ),
			'ingredients' => array(
				array(
					'section' => 'For the salad',
					'items'   => array(
						'1 cup chickpeas, drained and rinsed',
						'1 tbsp olive oil',
						'½ tsp paprika',
						'Pinch salt and black pepper',
						'1 avocado, diced',
						'½ cucumber, chopped',
						'Handful of rocket or spinach',
					),
				),
				array(
					'section' => 'For the dressing',
					'items'   => array(
						'1 tbsp tahini',
						'Juice of ½ lemon',
						'1–2 tbsp water (to thin)',
						'Pinch salt',
					),
				),
			),
		),
		'sardine-avocado-bowl' => array(
			'servings' => 1,
			'prep'     => 8,
			'cook'     => 0,
			'nutrition' => array( 'calories' => 520, 'protein' => 28, 'carbs' => 42, 'fat' => 28, 'fibre' => 9 ),
			'ingredients' => array(
				array(
					'section' => '',
					'items'   => array(
						'1 cup cooked quinoa',
						'1 tin sardines in olive oil',
						'½ avocado, sliced',
						'Handful of rocket or spinach',
						'Juice of ½ lemon',
						'Drizzle of olive oil',
						'Sea salt to taste',
					),
				),
			),
		),
		'tuna-lentil-pasta-salad' => array(
			'servings' => 2,
			'prep'     => 10,
			'cook'     => 10,
			'nutrition' => array( 'calories' => 410, 'protein' => 34, 'carbs' => 30, 'fat' => 15, 'fibre' => 9 ),
			'ingredients' => array(
				array(
					'section' => '',
					'items'   => array(
						'120 g red lentil pasta',
						'2 tins tuna in olive oil, drained',
						'1 cup cherry tomatoes, halved',
						'½ cucumber, chopped',
						'2 tbsp olives, sliced',
						'1 tbsp olive oil',
						'Juice of ½ lemon',
						'Small handful fresh parsley, chopped',
						'Salt and black pepper, to taste',
					),
				),
			),
		),
		'ginger-chicken-stir-fry' => array(
			'servings' => 2,
			'prep'     => 10,
			'cook'     => 15,
			'nutrition' => array( 'calories' => 355, 'protein' => 34, 'carbs' => 22, 'fat' => 14, 'fibre' => 4 ),
			'ingredients' => array(
				array(
					'section' => '',
					'items'   => array(
						'2 chicken breasts, thinly sliced',
						'1 tbsp olive oil or avocado oil',
						'1 tbsp fresh grated ginger',
						'1 garlic clove, minced',
						'1 carrot, thinly sliced',
						'1 red pepper, sliced',
						'1 courgette, sliced',
						'2 tbsp coconut aminos or tamari',
						'1 tsp sesame oil',
						'1 cup cooked brown rice',
						'Salt and black pepper, to taste',
					),
				),
			),
		),
		'chicken-tacos-avocado-slaw' => array(
			'servings' => 2,
			'prep'     => 15,
			'cook'     => 12,
			'nutrition' => array( 'calories' => 420, 'protein' => 32, 'carbs' => 28, 'fat' => 20, 'fibre' => 7 ),
			'ingredients' => array(
				array(
					'section' => 'For the chicken',
					'items'   => array(
						'2 chicken breasts, sliced into strips',
						'1 tbsp olive oil',
						'1 tsp ground cumin',
						'1 tsp paprika',
						'½ tsp garlic powder',
						'Juice of ½ lime',
						'Salt and black pepper, to taste',
					),
				),
				array(
					'section' => 'For the avocado slaw',
					'items'   => array(
						'1 cup shredded cabbage',
						'½ avocado',
						'Juice of ½ lime',
						'1 tbsp olive oil',
						'Small handful fresh coriander, chopped',
						'Pinch salt',
					),
				),
				array(
					'section' => 'To serve',
					'items'   => array(
						'4 small gluten-free corn tortillas',
					),
				),
			),
		),
		'lemon-herb-salmon' => array(
			'servings' => 2,
			'prep'     => 5,
			'cook'     => 15,
			'nutrition' => array( 'calories' => 420, 'protein' => 30, 'carbs' => 17, 'fat' => 24, 'fibre' => 3 ),
			'ingredients' => array(
				array(
					'section' => '',
					'items'   => array(
						'2 salmon fillets',
						'1 tbsp olive oil',
						'Juice of 1 lemon',
						'1 tsp dried oregano',
						'2 cups fresh spinach',
						'1 cup cooked lentils or brown rice',
						'Salt and black pepper, to taste',
					),
				),
			),
		),
		'mediterranean-lentil-bowl' => array(
			'servings' => 2,
			'prep'     => 10,
			'cook'     => 20,
			'nutrition' => array( 'calories' => 300, 'protein' => 11, 'carbs' => 24, 'fat' => 18, 'fibre' => 9 ),
			'ingredients' => array(
				array(
					'section' => 'For the bowl',
					'items'   => array(
						'1 cup cooked lentils',
						'1 courgette, sliced',
						'1 red pepper, chopped',
						'1 tbsp olive oil',
						'½ tsp dried oregano',
						'1 cup cherry tomatoes, halved',
						'Handful of rocket or spinach',
					),
				),
				array(
					'section' => 'For the dressing',
					'items'   => array(
						'1 tbsp olive oil',
						'Juice of ½ lemon',
						'Pinch sea salt and black pepper',
						'Optional: 1 tbsp fresh parsley, chopped',
					),
				),
			),
		),
		'mediterranean-seabass' => array(
			'servings' => 2,
			'prep'     => 10,
			'cook'     => 20,
			'nutrition' => array( 'calories' => 320, 'protein' => 32, 'carbs' => 8, 'fat' => 18, 'fibre' => 2 ),
			'ingredients' => array(
				array(
					'section' => '',
					'items'   => array(
						'2 seabass fillets (about 150 g each)',
						'1 courgette, sliced',
						'1 red pepper, sliced',
						'1 cup cherry tomatoes',
						'1 tbsp olive oil',
						'Juice of ½ lemon',
						'1 tsp dried oregano',
						'Small handful fresh parsley, chopped',
						'Salt and black pepper, to taste',
					),
				),
			),
		),
		'sweet-potato-ginger-soup' => array(
			'servings' => 3,
			'prep'     => 15,
			'cook'     => 35,
			'nutrition' => array( 'calories' => 200, 'protein' => 3, 'carbs' => 31, 'fat' => 7, 'fibre' => 6 ),
			'ingredients' => array(
				array(
					'section' => '',
					'items'   => array(
						'3 medium sweet potatoes, peeled and diced',
						'2 carrots, chopped',
						'1 small onion, chopped',
						'1 tbsp fresh grated ginger',
						'750 ml vegetable broth',
						'1 tbsp olive oil',
						'½ tsp turmeric',
						'Salt and black pepper, to taste',
						'50–100 ml unsweetened coconut milk or almond milk (optional, for creaminess)',
					),
				),
			),
		),
		'apple-almond-butter-plate' => array(
			'servings' => 1,
			'prep'     => 3,
			'cook'     => 0,
			'nutrition' => array( 'calories' => 260, 'protein' => 7, 'carbs' => 25, 'fat' => 17, 'fibre' => 6 ),
			'ingredients' => array(
				array(
					'section' => '',
					'items'   => array(
						'1 apple, sliced',
						'1 tbsp almond butter',
						'1 tbsp pumpkin seeds',
						'1 tbsp chopped walnuts',
						'½ tsp cinnamon',
						'Optional: small drizzle of raw honey',
					),
				),
			),
		),
		'date-nut-energy-balls' => array(
			'servings' => 10,
			'prep'     => 15,
			'cook'     => 0,
			'nutrition' => array( 'calories' => 120, 'protein' => 3, 'carbs' => 12, 'fat' => 7, 'fibre' => 2 ),
			'ingredients' => array(
				array(
					'section' => '',
					'items'   => array(
						'1 cup Medjool dates, pitted',
						'½ cup almonds',
						'¼ cup peanut butter or almond butter',
						'2 tbsp chia seeds',
						'2 tbsp unsweetened cocoa powder',
						'1 tbsp coconut oil',
						'1 tsp vanilla extract',
						'Optional: 1 tbsp water if mixture is too dry',
						'Optional: 2 tbsp shredded coconut for rolling',
					),
				),
			),
		),
		'turmeric-roasted-chickpeas' => array(
			'servings' => 2,
			'prep'     => 5,
			'cook'     => 25,
			'nutrition' => array( 'calories' => 190, 'protein' => 7, 'carbs' => 20, 'fat' => 9, 'fibre' => 6 ),
			'ingredients' => array(
				array(
					'section' => '',
					'items'   => array(
						'1 can chickpeas, drained and rinsed',
						'1 tbsp olive oil',
						'½ tsp turmeric',
						'½ tsp paprika',
						'Pinch of sea salt and black pepper',
					),
				),
			),
		),
	);
}
