<?php
/**
 * ВАЖНО! Никакие файлы плагина тут не подключаются!
 *
 * Здесь доступны только базовые функции WordPress.
 *
 * Если нужны какие-то, функции или классы плагина для удаления,
 * подключите файлы и инициализируйте нужные классы отдельно.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// проверка пройдена успешно.

// Удаляем опции и все остальное.
// global $wpdb;

// $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_type IN ('light-review');");

$reviews = get_posts(array('post_type'=>'light-review', 'numberposts'=>-1));
foreach ($reviews as $review) {
	wp_delete_post($review->ID, true);
}

// die();
