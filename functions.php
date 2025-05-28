<?php
// =========== adding form for user given names and showing them in the signle post page start ================

// 1. Register custom table on plugin/theme load
function custom_name_table_setup() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_names';
    $charset_collate = $wpdb->get_charset_collate();

    if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT UNSIGNED NOT NULL,
            user_name VARCHAR(255) NOT NULL,
            given_name VARCHAR(255) NOT NULL,
            votes INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
add_action('after_setup_theme', 'custom_name_table_setup');

// 2. Enqueue JS & Localize
function enqueue_name_script() {
    wp_enqueue_script('name-script', get_stylesheet_directory_uri() . '/name-script.js', array('jquery'), null, true);
    wp_localize_script('name-script', 'nameObj', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('name_nonce')
    ]);
}
add_action('wp_enqueue_scripts', 'enqueue_name_script');

// 3. Add Form + Tables to Single Post
function show_name_submission_form() {
    if (!is_single()) return;

    ob_start(); ?>
    <div class="user-name-wrapper">
        <h2>Top 20 Names</h2>
        <table class="top-names-table">
            <thead><tr><th>SL</th><th>Name</th><th>Votes</th></tr></thead>
            <tbody id="top-names-body"></tbody>
        </table>

        <h2>User Given Names</h2>
        <table class="user-given-names-table">
            <thead><tr><th>SL</th><th>User's Name</th><th>Given Name</th><th>Vote</th></tr></thead>
            <tbody id="user-given-names-body"></tbody>
        </table>

		<h2>Add A Name</h2>
        <form id="name-submit-form">
            <input type="text" name="user_name" placeholder="Your Name" required>
            <input type="text" name="given_name" placeholder="Suggested Name" required>
            <input type="hidden" name="post_id" value="<?php echo get_the_ID(); ?>">
            <button type="submit">Add Name</button>
        </form>
    </div>
    <?php
    echo ob_get_clean();
}
add_action('the_content', function ($content) {
    if (is_single()) {
        $content .= show_name_submission_form();
    }
    return $content;
});

// 4. AJAX - Submit Name
add_action('wp_ajax_submit_name', 'submit_name_ajax');
add_action('wp_ajax_nopriv_submit_name', 'submit_name_ajax');
function submit_name_ajax() {
    check_ajax_referer('name_nonce', 'nonce');
    global $wpdb;
    $table = $wpdb->prefix . 'user_names';

    $post_id = intval($_POST['post_id']);
    $user_name = sanitize_text_field($_POST['user_name']);
    $given_name = sanitize_text_field($_POST['given_name']);

    $wpdb->insert($table, [
        'post_id' => $post_id,
        'user_name' => $user_name,
        'given_name' => $given_name,
    ]);

    wp_send_json_success();
}

// 5. AJAX - Get Names
add_action('wp_ajax_get_names', 'get_names_ajax');
add_action('wp_ajax_nopriv_get_names', 'get_names_ajax');
function get_names_ajax() {
    global $wpdb;
    $post_id = intval($_GET['post_id']);
    $table = $wpdb->prefix . 'user_names';

    $all = $wpdb->get_results("SELECT * FROM $table WHERE post_id = $post_id ORDER BY created_at DESC");
    $top = $wpdb->get_results("SELECT given_name, votes FROM $table WHERE post_id = $post_id ORDER BY votes DESC LIMIT 20");

    wp_send_json_success(['all' => $all, 'top' => $top]);
}

// 6. AJAX - Vote
add_action('wp_ajax_vote_name', 'vote_name_ajax');
add_action('wp_ajax_nopriv_vote_name', 'vote_name_ajax');
function vote_name_ajax() {
    check_ajax_referer('name_nonce', 'nonce');
    global $wpdb;
    $id = intval($_POST['id']);
    $cookie_name = 'voted_name_' . $id;

    if (isset($_COOKIE[$cookie_name])) {
        wp_send_json_error('Already voted');
    }

    $table = $wpdb->prefix . 'user_names';
    $wpdb->query($wpdb->prepare("UPDATE $table SET votes = votes + 1 WHERE id = %d", $id));
    setcookie($cookie_name, 1, time() + 3600 * 24 * 30, "/");

    wp_send_json_success(['id' => $id]);
}
// =========== adding form for user given names and showing them in the signle post page end ================
