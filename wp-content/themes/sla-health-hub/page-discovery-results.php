<?php
/**
 * Template Name: Discovery Results
 * Display search results from Content Discovery Suite
 */

get_header();

/**
 * Filter parameters.
 *
 * The pre-rebuild suite also submitted reading_level[], pathway_tag[] and
 * indication_tag[], which matched `reading-`/`path-`/`indication-` prefixed tags
 * that were never created on this site. Those params are read by nobody now: an
 * old link or saved search widens to a broader result set rather than returning
 * an empty page, which is the more useful failure.
 */
$content_types = isset($_GET['content_type']) ? array_map('sanitize_title', (array) $_GET['content_type']) : array();
$topics        = isset($_GET['topic'])        ? array_map('sanitize_title', (array) $_GET['topic'])        : array();
$conditions    = isset($_GET['condition'])    ? array_map('sanitize_title', (array) $_GET['condition'])    : array();
$audience      = isset($_GET['audience'])     ? sanitize_key($_GET['audience'])                            : '';
$search_query  = isset($_GET['s'])            ? sanitize_text_field($_GET['s'])                            : '';
$sort          = isset($_GET['sort'])         ? sanitize_key($_GET['sort'])                                : '';

// Only honour condition slugs the site actually defines.
$conditions = array_values(array_intersect($conditions, array_keys(vance_gi_conditions())));

$audience_tags = array('patient' => 'audience-patient', 'hcp' => 'audience-hcp');

$args = array(
    'post_type'      => vance_discovery_post_types(),
    'post_status'    => 'publish',
    'posts_per_page' => 20,
    'paged'          => get_query_var('paged') ? get_query_var('paged') : 1,
);

if (!empty($search_query)) {
    $args['s'] = $search_query;
}

/* Newest-first by default. The one exception is an un-sorted keyword search,
   where WordPress's own relevance ordering is more useful than the date. */
if ('oldest' === $sort) {
    $args['orderby'] = 'date';
    $args['order']   = 'ASC';
} elseif ('newest' === $sort || '' === $search_query) {
    $args['orderby'] = 'date';
    $args['order']   = 'DESC';
}

/* Each facet is its own clause, ANDed together: picking a section and a
   condition narrows to posts matching both. Within a facet the terms are ORed.
   Category clauses keep include_children on, so choosing a section also matches
   posts filed only under one of its child topics. */
$tax_query = array('relation' => 'AND');

if (!empty($content_types)) {
    $tax_query[] = array('taxonomy' => 'category', 'field' => 'slug', 'terms' => $content_types, 'operator' => 'IN');
}
if (!empty($topics)) {
    $tax_query[] = array('taxonomy' => 'category', 'field' => 'slug', 'terms' => $topics, 'operator' => 'IN');
}
if (!empty($conditions)) {
    $tax_query[] = array('taxonomy' => 'post_tag', 'field' => 'slug', 'terms' => $conditions, 'operator' => 'IN');
}
if (isset($audience_tags[$audience])) {
    $tax_query[] = array('taxonomy' => 'post_tag', 'field' => 'slug', 'terms' => array($audience_tags[$audience]), 'operator' => 'IN');
}

if (count($tax_query) > 1) {
    $args['tax_query'] = $tax_query;
}

$query = new WP_Query($args);

// Get current URL for sharing
$current_url = home_url(add_query_arg(array()));

// Condition slug => display label, for rendering tag pills readably.
$condition_labels = wp_list_pluck(vance_gi_conditions(), 'label');
?>

<style>
.discovery-results-page {
    background: #F8FAFC;
    min-height: 100vh;
    padding-bottom: 60px;
}

.results-hero {
    background: linear-gradient(rgba(10, 25, 41, 0.85), rgba(10, 25, 41, 0.9)), url('<?php echo get_template_directory_uri(); ?>/assets/img/about_hero.png') center/cover;
    padding: 80px 0;
    color: white;
    text-align: center;
    border-bottom: 3px solid var(--primary-color);
}

.results-hero h1 {
    font-family: 'Outfit', sans-serif;
    font-size: 42px;
    font-weight: 800;
    margin: 0 0 16px 0;
    letter-spacing: -0.5px;
}

.results-hero p {
    font-size: 18px;
    color: #CBD5E1;
    margin: 0;
}

.control-box {
    background: white;
    border-radius: 0;
    padding: 24px;
    margin: -40px auto 40px;
    max-width: 1200px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border: 2px solid var(--primary-color);
    position: relative;
    z-index: 10;
}

.control-buttons {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: center;
}

.control-btn {
    padding: 10px 20px;
    border-radius: 0;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid #E2E8F0;
    background: white;
    color: #334155;
    display: flex;
    align-items: center;
    gap: 8px;
}

.control-btn:hover {
    border-color: var(--primary-color);
    background: #def4f4;
    transform: translateY(-2px);
}

.control-btn.primary {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

.control-btn.primary:hover {
    background: #e65100;
}

.results-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
    padding-bottom: 16px;
    border-bottom: 2px solid #E2E8F0;
}

.results-count {
    font-size: 18px;
    font-weight: 700;
    color: #0F172A;
}

.results-sort {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #64748B;
}

.results-sort select {
    padding: 8px 12px;
    border: 1px solid #E2E8F0;
    border-radius: 0;
    background: white;
    color: #334155;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.results-sort select:hover {
    border-color: var(--primary-color);
}

.result-item {
    background: white;
    border-radius: 0;
    padding: 24px;
    margin-bottom: 20px;
    border: 1px solid #E2E8F0;
    transition: all 0.2s;
    display: flex;
    gap: 20px;
}

.result-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transform: translateY(-2px);
}

.result-thumbnail {
    width: 120px;
    height: 120px;
    border-radius: 0;
    background: #F1F5F9;
    overflow: hidden;
    flex-shrink: 0;
}

.result-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.result-content {
    flex: 1;
}

.result-title {
    font-family: 'Outfit', sans-serif;
    font-size: 20px;
    font-weight: 700;
    color: #0F172A;
    margin: 0 0 8px 0;
    line-height: 1.3;
}

.result-title a {
    color: inherit;
    text-decoration: none;
}

.result-title a:hover {
    color: var(--primary-color);
}

.result-excerpt {
    color: #64748B;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 12px;
}

.result-meta {
    display: flex;
    gap: 16px;
    font-size: 13px;
    color: #94A3B8;
    margin-bottom: 12px;
}

.result-tags {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.result-tag {
    background: #F1F5F9;
    color: #475569;
    padding: 4px 10px;
    border-radius: 0;
    font-size: 11px;
    font-weight: 600;
}

.result-actions {
    display: flex;
    gap: 8px;
}

.action-btn {
    padding: 8px 16px;
    border-radius: 0;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid #E2E8F0;
    background: white;
    color: #64748B;
    transition: all 0.2s;
}

.action-btn:hover {
    border-color: var(--primary-color);
    color: var(--primary-color);
}

.action-btn.bookmarked {
    background: #def4f4;
    border-color: var(--primary-color);
    color: var(--primary-color);
}

.no-results {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 0;
    border: 2px dashed #E2E8F0;
}

.no-results h2 {
    font-family: 'Outfit', sans-serif;
    font-size: 24px;
    color: #0F172A;
    margin-bottom: 12px;
}

.no-results p {
    color: #64748B;
    margin-bottom: 24px;
}

@media (max-width: 768px) {
    .results-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .result-item {
        flex-direction: column;
    }
    
    .result-thumbnail {
        width: 100%;
        height: 200px;
    }
    
    .control-buttons {
        flex-direction: column;
    }
    
    .control-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="discovery-results-page">
    <!-- Hero Section -->
    <section class="results-hero">
        <div class="container">
            <h1>Discovery Results</h1>
            <p><?php echo $query->found_posts; ?> articles found matching your criteria</p>
        </div>
    </section>

    <!-- Control Box -->
    <div class="container">
        <div class="control-box">
            <div class="control-buttons">
                <button class="control-btn" onclick="window.history.back()">
                    <span>←</span> Update Search
                </button>
                <button class="control-btn" onclick="window.location.href='<?php echo home_url('/#discovery-suite'); ?>'">
                    <span>🔍</span> New Search
                </button>
                <?php if (is_user_logged_in()): ?>
                <button class="control-btn" onclick="saveCurrentSearch()">
                    <span>💾</span> Save Search
                </button>
                <button class="control-btn" onclick="window.location.href='<?php echo home_url('/dashboard/'); ?>'">
                    <span>📁</span> My Searches
                </button>
                <?php endif; ?>
                <button class="control-btn" onclick="copyToClipboard('<?php echo esc_js($current_url); ?>')">
                    <span>📋</span> Copy Link
                </button>
            </div>
        </div>
    </div>

    <!-- Results Container -->
    <div class="results-container">
        <?php if ($query->have_posts()): ?>
            
            <div class="results-header">
                <div class="results-count">
                    Showing <?php echo $query->post_count; ?> of <?php echo $query->found_posts; ?> results
                </div>
                <div class="results-sort">
                    <label for="results-sort-select">Sort</label>
                    <?php
                    /* Rewriting the current URL keeps every active filter and drops the
                       page number, so sorting always lands back on page one. */
                    $sort_selected = in_array($sort, array('newest', 'oldest'), true)
                        ? $sort
                        : ('' === $search_query ? 'newest' : 'relevance');
                    $sort_options = array('newest' => 'Newest first', 'oldest' => 'Oldest first');
                    if ('' !== $search_query) {
                        $sort_options = array('relevance' => 'Best match') + $sort_options;
                    }
                    ?>
                    <select id="results-sort-select" onchange="var u=new URL(window.location.href); if(this.value==='relevance'){u.searchParams.delete('sort');}else{u.searchParams.set('sort',this.value);} u.searchParams.delete('paged'); window.location.href=u.toString();">
                        <?php foreach ($sort_options as $sort_value => $sort_label) : ?>
                        <option value="<?php echo esc_attr($sort_value); ?>" <?php selected($sort_selected, $sort_value); ?>><?php echo esc_html($sort_label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php while ($query->have_posts()): $query->the_post(); ?>
                <article class="result-item">
                    <div class="result-thumbnail">
                        <?php if (has_post_thumbnail()): ?>
                            <?php the_post_thumbnail('medium'); ?>
                        <?php else: ?>
                            <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:40px; color:#CBD5E1;">
                                📄
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="result-content">
                        <h2 class="result-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h2>
                        
                        <div class="result-meta">
                            <span>📅 <?php echo get_the_date(); ?></span>
                            <span>✍️ <?php the_author(); ?></span>
                            <span>⏱️ <?php echo (int) vance_get_read_time(get_the_ID()); ?> min read</span>
                            <?php if (get_post_type() !== 'post'): ?>
                                <span>📁 <?php echo get_post_type_object(get_post_type())->labels->singular_name; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="result-excerpt">
                            <?php echo wp_trim_words(get_the_excerpt(), 30); ?>
                        </div>
                        
                        <?php
                        $cats = get_the_category();
                        /* Audience tags drive filtering; they are not a label a reader gains
                           anything from, so they stay out of the pills. */
                        $tags = array_filter((array) get_the_tags(), function ($tag) {
                            return 0 !== strpos($tag->slug, 'audience-');
                        });
                        if ($tags || $cats): ?>
                        <div class="result-tags">
                            <?php
                            if ($cats) {
                                foreach (array_slice($cats, 0, 3) as $cat) {
                                    echo '<span class="result-tag">' . esc_html($cat->name) . '</span>';
                                }
                            }
                            foreach (array_slice($tags, 0, 3) as $tag) {
                                $tag_label = isset($condition_labels[$tag->slug]) ? $condition_labels[$tag->slug] : $tag->name;
                                echo '<span class="result-tag">' . esc_html($tag_label) . '</span>';
                            }
                            ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="result-actions" style="margin-top: 12px;">
                            <button class="action-btn bookmark-btn" data-post-id="<?php the_ID(); ?>" onclick="toggleBookmark(this, <?php the_ID(); ?>)">
                                <span class="bookmark-icon">🔖</span> Add to Reading List
                            </button>
                            <a href="<?php the_permalink(); ?>" class="action-btn">
                                Read Article →
                            </a>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>

            <!-- Pagination -->
            <?php if ($query->max_num_pages > 1): ?>
            <div style="display: flex; justify-content: center; gap: 12px; margin-top: 40px;">
                <?php
                echo paginate_links(array(
                    'total' => $query->max_num_pages,
                    'current' => max(1, get_query_var('paged')),
                    'prev_text' => '← Previous',
                    'next_text' => 'Next →',
                ));
                ?>
            </div>
            <?php endif; ?>

        <?php else: ?>
            
            <div class="no-results">
                <h2>No Results Found</h2>
                <p>We couldn't find any articles matching your search criteria.</p>
                <button class="control-btn primary" onclick="window.location.href='<?php echo home_url('/#discovery-suite'); ?>'">
                    Try a New Search
                </button>
            </div>

        <?php endif; ?>
        
        <?php wp_reset_postdata(); ?>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Link copied to clipboard!');
    }, function(err) {
        prompt('Copy this link:', text);
    });
}

function saveCurrentSearch() {
    <?php if (!is_user_logged_in()): ?>
        alert('Please log in to save searches.');
        return;
    <?php else: ?>
        const name = prompt('Name this search:');
        if (!name) return;
        
        fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'vance_save_search',
                search_name: name,
                query_params: window.location.search.substring(1),
                nonce: '<?php echo wp_create_nonce("vance_save_search_nonce"); ?>'
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Search saved successfully!');
            } else {
                alert('Error: ' + data.data);
            }
        });
    <?php endif; ?>
}

function toggleBookmark(btn, postId) {
    <?php if (!is_user_logged_in()): ?>
        alert('Please log in to save articles.');
        return;
    <?php else: ?>
        fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'vance_toggle_bookmark',
                post_id: postId,
                nonce: '<?php echo wp_create_nonce("vance_dashboard_nonce"); ?>'
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (data.data.bookmarked) {
                    btn.classList.add('bookmarked');
                    btn.innerHTML = '<span class="bookmark-icon">✅</span> Added to List';
                } else {
                    btn.classList.remove('bookmarked');
                    btn.innerHTML = '<span class="bookmark-icon">🔖</span> Add to Reading List';
                }
            }
        });
    <?php endif; ?>
}

// Check existing bookmarks on page load
<?php if (is_user_logged_in()): ?>
document.addEventListener('DOMContentLoaded', function() {
    const bookmarks = <?php echo json_encode(get_user_meta(get_current_user_id(), '_sla_reading_list', true) ?: array()); ?>;
    document.querySelectorAll('.bookmark-btn').forEach(btn => {
        const postId = parseInt(btn.dataset.postId);
        if (bookmarks.includes(postId)) {
            btn.classList.add('bookmarked');
            btn.innerHTML = '<span class="bookmark-icon">✅</span> Added to List';
        }
    });
});
<?php endif; ?>
</script>

<?php get_footer(); ?>
