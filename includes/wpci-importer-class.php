<?php 

Class wpci_importer{

	// ----------- STATIC VARIABLES ---------------
	private static $all_product_categories;
	private static $all_images;
	private static $id_associations;
	private static $new_id_associations;
	private static $parent_id;

	private static $attributes;
	private static $custom_fields_start_val;
	private static $custom_fields;
	// --------------------------------------------

	//DELETE EXISTING PRODUCTS - PRESERVE STOCK
	public static function clear_products(){
		global $wpdb;
		$wpdb->query("DELETE wp_posts, wp_postmeta FROM " . $wpdb->prefix . "posts LEFT JOIN " . $wpdb->prefix . "postmeta ON " . $wpdb->prefix . "posts.ID = " . $wpdb->prefix . "postmeta.post_id WHERE (post_type='product' OR post_type='product_variation')"); //AND meta_key != '_stock'
		$wpdb->query("DELETE FROM wp_options WHERE `option_name` LIKE '%_transient_wc%'");
		self::$parent_id = null;
	}

	// --- GET ALL PRODUCT CATEGORIES ---
	public static function set_all_product_categories(){
		$all_cats = get_categories( array('taxonomy' => 'product_cat', 'hide_empty' => 0, 'hierarchical' => 1) );
		self::$all_product_categories = array();
		foreach($all_cats as $cat){
			self::$all_product_categories[$cat->name] = $cat->term_id;
		}
	}
	public static function get_all_product_categories(){
		return self::$all_product_categories;
	}
	public static function reset_all_product_categories(){
		self::$all_product_categories = $_SESSION['wpci_all_product_categories'];
	}
	//------------------------------------

	// --- GET ALL IMAGE ATTACHMENT TYPES ---
	public static function set_all_product_images(){
		global $wpdb;
		$all_images = $wpdb->get_results('SELECT ID, post_title FROM ' . $wpdb->prefix . 'posts WHERE post_mime_type LIKE "%image%"', ARRAY_A);
		foreach($all_images as $img_info){
			self::$all_images[ $img_info['post_title'] ] = $img_info['ID'];
		}
	}
	public static function get_all_product_images(){
		return self::$all_images;
	}
	public static function reset_all_product_images(){
		self::$all_images = $_SESSION['wpci_all_images'];
	}
	//---------------------------------------

	// --- GET ALL UNIQUE ID/ POST ID PAIRS FROM DATABASE ---
	public static function set_id_associations(){
		global $wpdb;
		$assocs = $wpdb->get_results( 'SELECT unique_id, post_id, product_type FROM ' . $wpdb->prefix . 'wpci_id_associations', ARRAY_A );
		self::$id_associations = array();
		foreach($assocs as $assoc){
			if( in_array( $assoc['unique_id'], array_keys(self::$id_associations) ) ){
				$prod_array = array();
				$prod_array['parent'] = ('product' == $assoc['product_type']) ? $assoc['post_id'] : self::$id_associations[$assoc['unique_id']]['post_id'];
				$prod_array['variation'] = ('product' == $assoc['product_type']) ? self::$id_associations[$assoc['unique_id']]['post_id'] : $assoc['post_id'];
				self::$id_associations[$assoc['unique_id']] = $prod_array;
			}
			else{
				self::$id_associations[$assoc['unique_id']] = array('post_id' => $assoc['post_id'], 'product_type' => $assoc['product_type']);
			}
		}
	}
	public static function get_id_association(){
		return self::$id_associations;
	}
	public static function reset_id_associations(){
		self::$id_associations = $_SESSION['wpci_id_associations'];
	}
	//-------------------------------------------------------

	// --- SET AND GET LAST PARENT ID BETWEEN CALLS ---
	public static function get_last_parent_id(){
		return self::$parent_id;
	}
	public static function reset_last_parent_id(){
		self::$parent_id = $_SESSION['wpci_last_parent_id'];
	}
	// ------------------------------------------------

	//SET ARRAY OF ALL PRODUCT ATTRIBUTES PRESENT IN THE CSV
	public static function set_csv_atts($first_row){
		self::$attributes = array();
		$i = 10;
		while( trim($first_row[$i]) != '' ){
			$this_title = $first_row[$i];
			$this_att = sanitize_title( $first_row[$i] );
			self::register_attribute($this_title, $this_att);
			self::$attributes[] = $this_att;
			$i ++;
		}
		delete_transient( 'wc_attribute_taxonomies' );
		self::$custom_fields_start_val = $i + 1;
		$_SESSION['wpci_csv_attributes'] = self::$attributes;
		$_SESSION['wpci_custom_fields_start'] = self::$custom_fields_start_val;
	}
	public static function reset_csv_atts(){
		self::$attributes = $_SESSION['wpci_csv_attributes'];
		self::$custom_fields_start_val = $_SESSION['wpci_custom_fields_start'];
	}

	private static function register_attribute($title, $att){
		global $wpdb;
		$exists = $wpdb->get_row( $wpdb->prepare('SELECT `attribute_id` FROM ' . $wpdb->prefix . 'woocommerce_attribute_taxonomies WHERE `attribute_name`=%s', $att) );
		if(empty($exists)){
			$wpdb->insert( $wpdb->prefix . 'woocommerce_attribute_taxonomies', array('attribute_name' => $att, 'attribute_label' => $title, 'attribute_type' => 'select', 'attribute_orderby' => 'menu_order', 'attribute_public' => 0) );
		}
	}

	//SET ARRAY OF ALL CUSTOM META FIELDS PRESENT IN THE CSV
	public static function set_custom_meta_fields($first_row){
		self::$custom_fields = array();
		$i = self::$custom_fields_start_val;
		while( trim($first_row[$i]) != '' ){
			self::$custom_fields[] = $first_row[$i];
			$i ++;
		}
		$_SESSION['wpci_custom_meta_fields'] = self::$custom_fields;
	}
	public static function reset_custom_meta_fields(){
		self::$custom_fields = $_SESSION['wpci_custom_meta_fields'];
	}

	//GET BASIC WOOCOMMERCE PRODUCT DETAILS FOR THE CURRENT ROW
	public static function get_row_basic_details($row){
		global $wpdb;
		//CHECK WHETHER THE ROW CONTAINS A NEW PRODUCT OR A VARIATION
		$product['unique_id'] = (int)$row[0];
		$product['post_data'] = array(
			'post_title' => utf8_encode( trim( $row[3] ) ),
			'post_content' => utf8_encode( trim($row[5]) ),
			'post_status' => 'publish',
			'comment_status' => 'open',
			'ping_status' => 'closed',
			'post_name' => utf8_encode( trim($row[3]) )
		);
		$product['meta_data'] = array(
			'_weight' => trim($row[2]),
			'_sku' => trim($row[4]),
			'_stock' => trim($row[7]),
			'_price' => trim($row[8]),
			'_regular_price' => trim($row[8]),
			'_visibility' => 'visible'
		);
		if( '' != trim($row[9]) && '0' != trim($row[9]) ){
			$product['meta_data']['_sale_price'] = trim($row[9]);
		}
		$product['terms'] = array(
			'product_cat' => array_map('trim', explode( '|', $row[1] )),
		);
		return $product;
	}

	//RETURN WHETHER OR NOT THE CURRENT PRODUCT IS A VARIATION OR A NEW PRODUCT
	public static function check_variable($row, $product, $next_row){

		if(null != self::$parent_id){
			$product['post_data']['post_type'] = 'product_variation';
			$product['post_data']['post_parent'] = self::$parent_id;
			if( trim( $row[3] ) != trim( $next_row[3] ) ){
				self::$parent_id = null;
			}
		}
		else{
			$product['post_data']['post_type'] = 'product';
			if( false !== $next_row && trim( $row[3] ) == trim( $next_row[3] ) ){
				self::$parent_id = 'flag';
			}
			else{
				self::$parent_id = null;
			}
		}
		return $product;
	}

	//ADD PRODUCT IMAGES IF PRESENT
	public static function get_row_image_data($row, $product){
		if(trim($row[6]) != ''){
			$images = explode('|', trim($row[6]));
			$images = array_map('self::strip_image_extenstions', $images);
			if( in_array( $images[0], array_keys(self::$all_images) ) ){
				$product['meta_data']['_thumbnail_id'] = self::$all_images[$images[0]];
			}
			$product_gallery_string = '';
			foreach($images as $image_string){
				if( in_array( $image_string, array_keys(self::$all_images) ) ){
					$product_gallery_string .= self::$all_images[$image_string] . ', ';
				}
			}
			$product_gallery_string = substr($product_gallery_string, 0, -2);
			if('' != $product_gallery_string && 'product' == $product['post_data']['post_type']){
				$product['meta_data']['_product_image_gallery'] = $product_gallery_string;
			}
		}
		return $product;
	}

	private static function strip_image_extenstions($val){
		if(false !== strpos($val, '.') ){
			$val = substr( $val, 0, strrpos($val, '.') );
		}
		$val = str_replace(' ', '-', $val);
		return $val;
	}

	//GET ATTRIBUTE VALUES FOR THE CURRENT ROW
	public static function get_row_atts($row, $product){
		$i = 10;
		$product['atts'] = array();
		foreach(self::$attributes as $key => $val){
			if( trim($row[$i]) != '' ){
				$product['atts'][$val] = utf8_encode($row[$i]);
			}
			$i++;
		}
		return $product;
	}

	//GET CUSTOM META VALUES FOR THE CURRENT ROW
	public static function get_row_custom_meta($row, $product){
		$i = self::$custom_fields_start_val;
		$product['custom_meta'] = array();
		foreach(self::$custom_fields as $custom_field){
			if( trim($row[$i]) != '' ){
				$product['custom_meta'][$custom_field] = utf8_encode($row[$i]);
			}
			$i++;
		}
		return $product;
	}

	//IMPORT PRODUCT
	public static function import_product($product){
		global $wpdb;
		$unique_id = $product['unique_id'];

		//GET POST ID FROM UNIQUE ID
		if( null != self::$id_associations && in_array( $unique_id, array_keys( self::$id_associations ) ) ){
			//IF THIS UNIQUE ID HAS BOTH A PARENT AND VARIATION PRODUCT
			if( isset(self::$id_associations[$unique_id]['parent']) ){
				$product['post_data']['import_id'] = self::$id_associations[$unique_id]['parent'];
				$product['var_id'] = self::$id_associations[$unique_id]['variation'];
			}
			//OTHERWISE, SIMPLY SET THE IMPORT ID
			else{
				$product['post_data']['import_id'] = self::$id_associations[$unique_id]['post_id'];
			}
		}

		$product_id = null;
		//THIS IS A NEW VARIABLE PRODUCT PARENT
		if('flag' == self::$parent_id){
			self::$parent_id = wp_insert_post($product['post_data'], true);
			self::$new_id_associations[] = array('unique_id' => $product['unique_id'], 'post_id' => self::$parent_id, 'product_type' => 'product');

			//UPDATE CATEGORIES FOR NEW PARENT PRODUCT
			$atts = self::get_formatted_insert_atts(self::$parent_id, $product['atts'], 1);
			update_post_meta( self::$parent_id, '_product_attributes', $atts );
			self::set_parent_possible_att_values(self::$parent_id, $product['atts']);
			wp_set_object_terms(self::$parent_id, 'variable', 'product_type');

			//SET POST META AND TERMS FOR PARENT
			self::set_product_post_meta($product, self::$parent_id);
			self::set_product_categories($product, self::$parent_id);

			//INSERT VARIATION
			$product['post_data']['post_type'] = 'product_variation';
			$product['post_data']['post_parent'] = self::$parent_id;
			$product['post_data']['import_id'] = ( array_key_exists('var_id', $product) ) ? $product['var_id'] : null;
			$product_id = wp_insert_post($product['post_data'], true);
			
			//INSERT VARIATION ATTRIBUTES, UPDATE ID ASSOCIATIONS
			self::insert_product_attributes($product['atts'], $product_id);
			self::$new_id_associations[] = array('unique_id' => $product['unique_id'], 'post_id' => $product_id, 'product_type' => 'variation');
		}
		//THIS IS A SIMPLE PRODUCT
		elseif( $product['post_data']['post_type'] == 'product'){
			
			//INSERT POST AND UPDATE ASSOCIAITONS
			$product_id  = wp_insert_post($product['post_data'], true);
			self::$new_id_associations[] = array('unique_id' => $product['unique_id'], 'post_id' => $product_id, 'product_type' => 'product');

			//UPDATE CATEGORIES FOR NEW PARENT PRODUCT
			$atts = self::get_formatted_insert_atts($product_id, $product['atts'], 0);
			update_post_meta( $product_id, '_product_attributes', $atts );
			self::set_parent_possible_att_values(self::$parent_id, $product['atts']);
			self::set_product_categories($product, $product_id);
		}
		//THIS IS A VARIATION
		else{
			$product_id = wp_insert_post($product['post_data'], true);
			self::$new_id_associations[] = array('unique_id' => $product['unique_id'], 'post_id' => $product_id, 'product_type' => 'variation');
			self::get_formatted_insert_atts($product['post_data']['post_parent'], $product['atts'], 1);
			self::insert_product_attributes($product['atts'], $product_id);
			WC_Product_Variable::sync(self::$parent_id);
		}
		
		//SET PRODUCT META VALUES
		self::set_product_post_meta($product, $product_id);

		//SET CUSTOM META FOR PRODUCT
		self::set_product_custom_meta($product, $product_id);
	}

	//UPDATE POSTMETA FOR A PRODUCT
	private static function set_product_post_meta($product, $product_id){
		global $wpdb;
		$query = 'INSERT INTO ' . $wpdb->prefix . 'postmeta (post_id, meta_key, meta_value) VALUES';
		$params = array();
		foreach($product['meta_data'] as $key => $val){
			if(!empty($val)){
				$query .= " (%d, %s, %s),";
				array_push($params, $product_id, $key, $val);
			}
		}
		if(!empty($params)){
			$query = substr($query, 0, -1);
			$wpdb->query( $wpdb->prepare($query,$params) );
		}
	}

	//SET POSSIBLE ATTRIBUTES FOR EACH PARENT
	private static function set_parent_possible_att_values($parent_id, $atts){
		global $wpdb;
		foreach($atts as $att => $val){
			wp_set_object_terms($parent_id, sanitize_text_field($val), 'pa_' . $att);
		}
	}

	//SET CATEGORIES FOR PRODUCT
	private static function set_product_categories($product, $product_id){
		global $wpdb;
		$cat_term_ids = array();
		foreach($product['terms']['product_cat'] as $cat_name){
			$cat_term_ids[] = self::$all_product_categories[$cat_name];
		}
		wp_set_object_terms($product_id, $cat_term_ids, 'product_cat');
	}

	//UPDATE PRODUCT ATTRIBUTES
	private static function insert_product_attributes($atts, $variation_id){
		global $wpdb;
		foreach($atts as $att => $value){
			//CONVERT VALUE INTO SLUG
			$value = sanitize_title($value);
			update_post_meta($variation_id, 'attribute_pa_' . $att, $value);
		}
	}

	//UPDATE PARENT'S AVAILABLE ATTRIBUTES
	private static function get_formatted_insert_atts($id, $base_atts, $for_variation){
		global $wpdb;
		$return_atts = array();
		$term_atts = array();
		foreach($base_atts as $base_att => $val){
			$term_atts[] = $base_att;
			$return_atts['pa_' . $base_att] = array(
				'name' => 'pa_' . $base_att,
				'value' => '',
				'is_visible' => 1,
				'is_variation' => $for_variation,
				'is_taxonomy' => 1,
				'position' => 0
			);
			$term_exists_check = term_exists($val, 'pa_' . $base_att);
			if( !empty($term_exists_check) ){
				$tax_id = $term_exists_check['term_taxonomy_id'];
			}
			else{
				$term = wp_insert_term($val, 'pa_' . $base_att);
				$tax_id = $term->term_taxonomy_id;
			}
			$wpdb->insert($wpdb->prefix . 'term_relationships', array('object_id' => $id, 'term_taxonomy_id' => $tax_id, 'term_order' => 0) );
		}
		return $return_atts;
	}

	//UPDATE THIS PRODUCT'S CUSTOM META VALUES
	private static function set_product_custom_meta($product, $product_id){
		global $wpdb;
		$custom_meta = $product['custom_meta'];
		foreach($custom_meta as $meta_name => $value){
			$meta_name = 'wpci_meta_' . sanitize_title($meta_name);
			update_post_meta($product_id, $meta_name, $value);
		}
	}

	//UPDATE ID ASSOCIATIONS TABLE TO REFLECT NEW PRODUCTS
	public static function save_id_associations(){
		global $wpdb;
		$table = $wpdb->prefix . 'wpci_id_associations';
		$wpdb->query('DELETE FROM ' .  $table . ' WHERE 1=1;' );
		foreach(self::$new_id_associations as $id_assoc){
			$wpdb->insert( $table, $id_assoc );
		}
	}
	public static function get_new_id_associations(){
		return self::$new_id_associations;
	}
	public static function reset_new_id_associations(){
		self::$new_id_associations = $_SESSION['wpci_new_id_associations'];
	}
}