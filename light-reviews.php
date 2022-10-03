<?php

/*
 * Plugin Name: 		Light reviews
 * Description: 		Light-reviews - это легкий плагин для создания отзывов на сайте, который реализует минимально необходимые возможности, не перегружая код лишними функциями
 * Author:				Петр Жечков
 * Version:				1.0
 * Requires at least:	6
 * Requires PHP:		5.4
 * License:				GPL2
 * License URI:			https://www.gnu.org/licenses/gpl-2.0.html
 * Network:				true
 * Text Domain:			light-reviews
 * Domain Path:			/lang
 */

if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

class Views{
	public function register(){
		add_action('init', [$this, 'custom_post_type']);
		add_action('plugins_loaded', [$this, 'load_text_domain']);
		add_action('add_meta_boxes', [$this, 'metabox_client_data_html']);
		add_action('save_post', [$this, 'save_metadata']);
		add_action('init', [$this, 'redister_shortcode']);

		add_action('wp_ajax_get_review_popup', [$this, 'load_review_popup']);
		add_action('wp_ajax_nopriv_get_review_popup', [$this, 'load_review_popup']);

		add_action('wp_ajax_send_review', [$this, 'send_review_to_admin']);
		add_action('wp_ajax_nopriv_send_review', [$this, 'send_review_to_admin']);

		add_action('wp_enqueue_scripts', [$this, 'enqueue_front']);
	}
	public function enqueue_front(){
		wp_enqueue_style('light-reviews_css', plugins_url('/assets/front/light-reviews.css', __FILE__), array('main'), _S_VERSION);
		wp_enqueue_script('light-reviews_js', plugins_url('/assets/front/light-reviews.js', __FILE__), array('main'), _S_VERSION, true);
	}
	public function load_review_popup() {
		$action = admin_url('admin-ajax.php?action=send_review');
		$html = '<div class="popup-inner popup-inner--review rounded-30">
					<div class="close-btn popup__close-btn">
						<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 1000 1000" xml:space="preserve">
							<path d="M990,57.1L942.9,10L500,452.9L57.1,10L10,57.1L452.9,500L10,942.9L57.1,990L500,547.1L942.9,990l47.1-47.1L547.1,500L990,57.1z">
							</path>
						</svg>
					</div>
					<form action="'.$action.'" class="review-form" id="review-form">
						<div class="review-form__inner">
							<div class="review-form__title">'.esc_html__('Add feedback', 'light-reviews').'</div>
							<div class="review-form__inputs">
								<div class="review-form__inputs-item">
									<label for="reviewer-name" class="review-form__label">'.esc_html__('Your first and last name', 'light-reviews').'</label>
									<input type="text" class="review-form__input" name="reviewer-name" id="reviewer-name" placeholder="'.esc_html__('Anasavia Yakovleva', 'light-reviews').'" required>
								</div>
								<div class="review-form__inputs-item">
									<label for="reviewer-phone">'.esc_html__('Phone', 'light-reviews').'</label>
									<input type="text" class="review-form__input" name="reviewer-phone" id="reviewer-phone" placeholder="+380" required>
								</div>

							</div>
							<div class="textarea-wrapper mt-30">
								<textarea name="reviewer-text" id="reviewer-text" class="review-form__textarea" rows="7" cols="50"></textarea>
								<div class="form-message" id="form-message"></div>
							</div>
							<div class="review-form__info">'.esc_html__('Blank lines start a new paragraph', 'light-reviews').'</div>
							<input name="review-form-btn" id="review-form-btn" type="submit" class="review-btn review-form__btn" value="'.esc_html__('Send', 'light-reviews').'">
						</div>
					</form>
				</div>';
		die($html);
	}
	public function send_review_to_admin() {
		$reviewer_name = sanitize_text_field($_POST['reviewer-name']);
		if ($_POST['reviewer-phone']) {
			$reviewer_phone = sanitize_text_field($_POST['reviewer-phone']);
		}
		if ($_POST['reviewer-text']) {
			$reviewer_text = sanitize_textarea_field($_POST['reviewer-text']);
		}

		/* Создаем новый пост-письмо */
		$post_data = array(
			'post_title'    => $reviewer_name,
			'post_content'  => $reviewer_text,
			'post_status'   => 'pending',
			'post_author'   => 1,
			'post_type' => 'light-review',
		);

		$post_id = wp_insert_post( $post_data );

		if ($post_id != 0){
			// Если при отправке поста не было ошибок
			update_post_meta( $post_id, 'client-phone', $reviewer_phone );
			$answer = [
				'id' => $post_id,
				'message' => __('Feedback sent successfully!</br>After verification by the administrator, it will be published on the site.', 'light-reviews')
			];
		}
		else {
			// Если при отправке поста произошла ошибка
			// В $post_id в данном случае попадет ноль, так как параметр $wp_error отключен по умолчанию, а пропуск превой проверки говорит о наличии ошибки при вставке данных в БД.
			$answer = [
				'id' => $post_id,
				'message' => __('An error occurred while submitting feedback!</br>Review not sent. If this happens again, please let us know.', 'light-reviews')
			];
		}
		die(json_encode($answer));
	}
	public function redister_shortcode(){
		add_shortcode('light-reviews-short-code', [$this, 'light_review_html']);
	}
	public function light_review_html( $atts = array() ){
		$pairs = array('align' => 'left', 'custom_css_class' => '');
		extract(shortcode_atts( $pairs, $atts ));

		return '<button id="review-btn" class="review-btn review-btn--'.$align.' '.$custom_css_class.'">'.esc_html__('Leave feedback', 'light-reviews').'</button>';
	}
	function load_text_domain(){
		load_plugin_textdomain('light-reviews', false, dirname(plugin_basename(__FILE__)).'/lang');
	}
	public function custom_post_type(){
		register_post_type( 'light-review', [
			'label'  => null,
			'labels' => [
				'name'               => esc_html__('Feedbacks', 'light-reviews'), // основное название для типа записи
				'singular_name'      => esc_html__('Feedback', 'light-reviews'), // название для одной записи этого типа
				'add_new'            => esc_html__('Add feedbacks', 'light-reviews'), // для добавления новой записи
				'add_new_item'       => esc_html__('Adding a feedback', 'light-reviews'), // заголовка у вновь создаваемой записи в админ-панели.
				'edit_item'          => esc_html__('Editing a feedback', 'light-reviews'), // для редактирования типа записи
				'new_item'           => esc_html__('New feedback', 'light-reviews'), // текст новой записи
				'view_item'          => esc_html__('Watch feedback', 'light-reviews'), // для просмотра записи этого типа.
				'search_items'       => esc_html__('Search feedback', 'light-reviews'), // для поиска по этим типам записи
				'not_found'          => esc_html__('No feedbacks found', 'light-reviews'), // если в результате поиска ничего не было найдено
				'not_found_in_trash' => esc_html__('Not found in cart', 'light-reviews'), // если не было найдено в корзине
				'parent_item_colon'  => '', // для родителей (у древовидных типов)
				'menu_name'          => esc_html__('Feedbacks', 'light-reviews'), // название меню
			],
			'description'         => esc_html__('Company feedback', 'light-reviews'),
			'public'              => true,
			'show_in_menu'        => true, // показывать ли в меню адмнки
			'show_in_rest'        => true, // добавить в REST API. C WP 4.7
			'rest_base'           => null, // $post_type. C WP 4.7
			'menu_position'       => 4,
			'menu_icon'           => 'dashicons-admin-comments',
			'hierarchical'        => false,
			'supports'            => [ 'title', 'editor', 'custom-fields' ], // 'title','editor','author','thumbnail','excerpt','trackbacks','custom-fields','comments','revisions','page-attributes','post-formats'
			'taxonomies'          => ['category'],
			'has_archive'         => false,
			'rewrite'             => true,
			'query_var'           => true,
		]);
	}
	static function activation(){
		flush_rewrite_rules();
	}
	static function deactivation(){
		flush_rewrite_rules();
	}
	function metabox_client_data_html(){
		add_meta_box( 'review-metabox', esc_html__('Client data', 'light-reviews'), [$this, 'meta_box_html_func'], 'light-review' );
	}

	// HTML код блока
	function meta_box_html_func( $post ){
		// $screens = $meta['args'];

		// Используем nonce для верификации
		wp_nonce_field( plugin_basename(__FILE__), 'light_reviews_noncename' );

		// значение поля
		$value = get_post_meta( $post->ID, 'client-phone', true );

		// Поля формы для введения данных
		echo '<table class="form-table">
			<tbody>
				<tr>
					<th><label for="client-phone">' . esc_html__("Client phone", 'light-reviews' ) . '</label></th>
					<td><input type="text" id="client-phone" name="client-phone" value="'. esc_attr($value) .'" size="25" /></td>
				</tr>
			</tbody>
		</table>';
	}

	public function save_metadata($post_id){
		// Убедимся что поле установлено.
		if ( ! isset( $_POST['client-phone'] ) )
			return;
		// проверяем nonce нашей страницы, потому что save_post может быть вызван с другого места.
		if ( ! wp_verify_nonce( $_POST['light_reviews_noncename'], plugin_basename(__FILE__) ) )
			return;
		// если это автосохранение ничего не делаем
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
			return;
		// проверяем права юзера
		if( ! current_user_can( 'edit_post', $post_id ) )
			return;
		// Все ОК. Теперь, нужно найти и сохранить данные
		// Очищаем значение поля input.
		$my_data = sanitize_text_field( $_POST['client-phone'] );

		// Обновляем данные в базе данных.
		update_post_meta( $post_id, 'client-phone', $my_data );

		// return $post_id;
	}

}

if(class_exists('Views')){
	$Views = new Views();
	$Views->register();
}

register_activation_hook(__FILE__, array($Views, 'activation'));
register_deactivation_hook(__FILE__, array($Views, 'deactivation'));
