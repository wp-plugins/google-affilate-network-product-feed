<?php

/* Load our constants */
require_once(dirname(__FILE__) . "/GAN_Constants.php");
/* Load Database code */
require_once(dirname(__FILE__) . "/GAN_Database.php");

require_once (dirname(__FILE__) . '/../../../wp-admin/includes/class-wp-list-table.php');

global $wpdb;
define('GAN_PRODUCT_SUBSCRIPTIONS_TABLE',$wpdb->prefix . "dws_gan_prodsubscript");
define('GAN_PROD_HEADERS',"ProductID,ProductName,ProductURL,BuyURL,ImageURL,Category,CategoryID,PFXCategory,BriefDesc,ShortDesc,IntermDesc,LongDesc,ProductKeyword,Brand,Manufacturer,ManfID,ManufacturerModel,UPC,Platform,MediaTypeDesc,MerchandiseType,Price,SalePrice,VariableCommission,SubFeedID,InStock,Inventory,RemoveDate,RewPoints,PartnerSpecific,ShipAvail,ShipCost,ShippingIsAbsolut,ShippingWeight,ShipNeeds,ShipPromoText,ProductPromoText,DailySpecialsInd,GiftBoxing,GiftWrapping,GiftMessaging,ProductContainerName,CrossSellRef,AltImagePrompt,AltImageURL,AgeRangeMin,AgeRangeMax,ISBN,Title,Publisher,Author,Genre,Media,Material,PermuColor,PermuSize,PermuWeight,PermuItemPrice,PermuSalePrice,PermuInventorySta,Permutation,PermutationSKU,BaseProductID,Option1,Option2,Option3,Option4,Option5,Option6,Option7,Option8,Option9,Option10,Option11,Option12,Option13,Option14,Option15,Option16,Option17,Option18,Option19,Option20");
define('GAN_PROD_TAGABLE_HEADERS',"Category,CategoryID,ProductKeyword,Brand,Manufacturer,Platform,MerchandiseType,Publisher,Author,Genre,Media,Material");
define('GAN_TIMELIMIT', 25*100);

class GAN_Products_List extends WP_List_Table {
	var $row_actions = array();

	function __construct() {
	  if ( method_exists('WP_List_Table','WP_List_Table')) {
		parent::WP_List_Table( array ('subscriptions') );
	  } else {
		parent::__construct( array ('subscriptions') );
	  }
	  add_screen_option('per_page',array('label' => __('Subscriptions') ));

	  $this->row_actions =
		array( __('Edit','gan') => add_query_arg(
			array('page' => 'gan-database-add-products',
			      'mode' => 'edit'),
			admin_url('admin.php')),
		       __('View','gan') => add_query_arg(
			array('page' => 'gan-database-add-products',
			      'mode' => 'view'),
			admin_url('admin.php')),
		       __('Delete','gan') => add_query_arg(
			array('page' => 'gan-database-products',
			      'action' => 'delete'),
			admin_url('admin.php')),
		       __('Import','gan') => add_query_arg(
			array('page' => 'gan-database-products',
			      'action' => 'import'),
			admin_url('admin.php')) );


	}


	function get_columns() {
		return array (
			'cb' => '<input type="checkbox" />',
			'MerchantID' => __('Advertiser','gan'),
			'zipfilepath' => __('Zip File Path','gan'),
			'dailyimport' => __('Daily Import?','gan'));
	}

	function get_items_per_page ($option, $default = 20) {
	  if ( isset($_REQUEST['screen-options-apply']) &&
	       $_REQUEST['wp_screen_options']['option'] == $option ) {
	    $per_page = (int) $_REQUEST['wp_screen_options']['value'];
	  } else {
	    $per_page = $default;
	  }
	  return (int) apply_filters( $option, $per_page );
	}	
	function check_permissions() {
	  if (!current_user_can('manage_options')) {
	    wp_die( __('You do not have sufficient permissions to access this page.','gan') );
	  }
	}
	function get_bulk_actions() {
	  return array ('delete' => __('Delete','gan'),
			'import' => __('Import','gan') );
	}
	function get_column_info() {
	  if ( isset($this->_column_headers) ) {return $this->_column_headers;}
	  $this->_column_headers = 
		array( $this->get_columns(), 
		       array(), 
		       $this->get_sortable_columns() );
	  return $this->_column_headers;
	}
	function column_cb ($item) {
	  return '<input type="checkbox" name="checked[]" value="'.$item->id.'" />';
	}
	function column_MerchantID($item) {
	  echo GAN_Database::get_merch_name($item->MerchantID);
	  echo '<br />';
	  $option = str_replace( '-', '_', 
				get_current_screen()->id . '_per_page' );
	  $per_page = $this->get_pagination_arg('per_page');
	  foreach ($this->row_actions as $label => $url) {
	    ?><a href="<?php echo add_query_arg( 
				array('paged'   => $paged,
				      'screen-options-apply' => 'Apply',
				      'wp_screen_options[option]' => $option,
				      'wp_screen_options[value]' => $per_page,
				      'id' => $item->id ), $url); 
			?>"><?php echo $label; ?></a>&nbsp;<?php
	  }
	  return '';
	}
	function column_zipfilepath($item) {
	  return $item->zipfilepath;
	}
	function column_dailyimport($item) {
	  if ($item->dailyimport) {
	    return 'yes';
	  } else {
	    return 'no';
	  }
	}
	function column_default($item, $column_name) {
	  return apply_filters( 'manage_items_custom_column','',$column_name,$item->id);
	}
	function prepare_items() {
	  $this->check_permissions();

	  $answer = '';

	  if ( isset($_REQUEST['action']) && $_REQUEST['action'] != -1 ) {
	    $theaction = $_REQUEST['action'];
	  } else if ( isset($_REQUEST['action2']) && $_REQUEST['action2'] != -1 ) {
	    $theaction = $_REQUEST['action2'];
	  } else {
	    $theaction = 'none';
	  }

	  switch ($theaction) {
	    case 'delete':
		if ( isset($_REQUEST['id']) ) {
		  $answer = '<p>'.GAN_Products::delete_sub_by_id($_REQUEST['id']).'</p>';
		} else {
		  $answer = '';
		  foreach ( $_REQUEST['checked'] as $theitem ) {
		    $answer .= '<p>'.GAN_Products::delete_sub_by_id($theitem).'</p>';
		  }
		}
		break;
	    case 'import':
		if ( isset($_REQUEST['id']) ) {
		  $answer = '<p>'.GAN_Products::import_products($_REQUEST['id']).'</p>';
		} else {
		  $answer = '';
		  foreach ( $_REQUEST['checked'] as $theitem ) {
		    $answer .= '<p>'.GAN_Products::import_products($theitem).'</p>';
		  }
		}
		break;
	  }
	  $all_items = GAN_Products::get_sub_data();
	  $screen = get_current_screen();
	  $option = str_replace( '-', '_', $screen->id . '_per_page' );
	  $per_page = $this->get_items_per_page( $option );
	  $total_items = count($all_items);
	  $this->set_pagination_args( array (
		'total_items' => $total_items,
		'per_page'    => $per_page ));
	  $total_pages = $this->get_pagination_arg( 'total_pages' );
	  $pagenum = $this->get_pagenum();
	  if ($pagenum < 1) {
	    $pagenum = 1;
	  } else if ($pagenum > $total_pages && $total_pages > 0) {
	    $pagenum = $total_pages;
	  }
	  $start = ($pagenum-1)*$per_page;
  	  $this->items = array_slice( $all_items,$start,$per_page );
	  return $answer;
	}
}

class GAN_Products {

	var $viewmode = 'add';
	var $viewid   = 0;
	var $viewitem;

	var $gan;

	var $prod_list = '';

	var $main_screen;

	function __construct($thegan) {
	  $this->gan = $thegan;
	  GAN_Products::make_prodsub_table();

	  add_action('admin_menu', array($this,'admin_menu'));
	  add_action('wp_head', array($this,'wp_head'));
	  add_action('admin_head', array($this,'admin_head'));
	  add_action('wp_dashboard_setup', array($this,'wp_dashboard_setup'));
	  add_option('wp_gan_products_shoppress','no');
	  add_option('wp_gan_products_postformat',
	    '<p><img align="left" src="%ImageURL" alt="%ProductName" border="">%LongDesc</p>
Price: $%Price<br /><a href="%BuyURL" class="gan_prod_buylink">Buy Now</a><br />
<a href="%ProductURL" class="gan_prod_prodlink">Detailed Product Page</a><br />');
	  add_option('wp_gan_products_css',
'a.gan_prod_buylink {
	line-height: 15px;
	padding: 3px 10px;
	white-space: nowrap;
	background-color: #ffd700;
	-webkit-border-radius: 10px;} 
a.gan_prod_prodlink {
	line-height: 15px;
	padding: 3px 10px;
	white-space: nowrap;
	background-color: #87ceeb;
	-webkit-border-radius: 10px;}');
	  add_option('wp_gan_products_customfields',"");
	  add_option('wp_gan_products_category_mode','category_tree');
	  add_option('wp_gan_products_category_treesep','>');
	  add_option('wp_gan_products_category_maxtreedepth',0);
	  add_option('wp_gan_products_tagheaders','ProductKeyword,Brand,Manufacturer');
	  add_option('wp_gan_products_matchcols','');
	  add_option('wp_gan_products_matchpattern','');
	  add_option('wp_gan_products_batchqueue',"");
	  add_action('wp_gan_products_batchrun',array('GAN_Products','run_batch'));
	}
	function admin_menu() {
	  $screen_id = add_menu_page( __('GAN Product Database','gan'), 
					__('GAN Product DB','gan'), 
					'manage_options',
					'gan-database-products', 
					array($this,'admin_product_subscriptions'),
					GAN_PLUGIN_IMAGE_URL.'/GAN_prod_menu.png');
	  add_action("load-$screen_id",array($this,init_prod_list_class));
	  //$this->add_contentualhelp($screen_id,'gan-database-products');
	  $screen_id = add_submenu_page( 'gan-database-products', 
					__('Add new GAN Product Subscriptions', 'gan'),
					__('Add new','gan'),
					'manage_options', 
					'gan-database-add-products',
					array($this,'admin_product_add_subscriptions'));
	  //$this->add_contentualhelp($screen_id,'gan-database-add-products');
	  $screen_id = add_submenu_page( 'gan-database-products', 
					__('Configure GAN Product Subscriptions', 'gan'),
					__('Configure','gan'),
					'manage_options', 
					'gan-database-configure-products',
					array($this,'admin_product_configure_subscriptions'));
	  //$this->add_contentualhelp($screen_id,'gan-database-configure-products');
	}

	function init_prod_list_class() {
	  if ($this->prod_list == '') {
	    $this->prod_list = new GAN_Products_List();
	  }
	}



	function admin_product_subscriptions() {
	  $message = $this->prod_list->prepare_items();
	  /* Head of page, filter and screen options. */
	  ?><div class="wrap"><div id="icon-gan-prod" class="icon32"><br /></div>
	    <h2><?php _e('GAN Product Subscriptions','gan'); ?> <a href="<?php
                echo add_query_arg(
                   array('page' => 'gan-database-add-products',
                         'mode' => 'add',
                         'id' => false));
                ?>" class="button add-new-h2"><?php _e('Add New','gan');
                ?></a><?php
			$this->gan->InsertVersion(); ?></h2>
	    <?php $this->gan->PluginSponsor(); ?>
	    <?php if ($message != '') {
		?><div id="message" class="update fade"><?php echo $message; ?></div><?php
		} ?>
	    <form method="post" action="">
		<input type="hidden" name="page" value="gan-database-products" />
		<?php $this->prod_list->display(); ?></form></div><?php
	}

	static function make_prodsub_table() {
	  $columns = array ( 'id' => 'int NOT NULL AUTO_INCREMENT',   /* ID */
			     'zipfilepath' => "varchar(255) NOT NULL default''" , /* path to zip file */
			     'MerchantID'  => "varchar(16) NOT NULL default ''",  /* Merchant ID */
			     'dailyimport' => 'boolean NOT NULL default true', /* daily import? */
			     'PRIMARY' => 'KEY (id)'
		     );
	  global $wpdb;
	  $sql = "CREATE TABLE " . GAN_PRODUCT_SUBSCRIPTIONS_TABLE . ' (';
	  foreach($columns as $column => $option) {
	    $sql .= "{$column} {$option}, \n";
	  }
	  $sql = rtrim($sql, ", \n") . " \n)";
	  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	  $result = dbDelta($sql);
	}

	static function get_sub_data() {
	  global $wpdb;
	  return $wpdb->get_results("SELECT id,zipfilepath,MerchantID,dailyimport FROM ".GAN_PRODUCT_SUBSCRIPTIONS_TABLE,'OBJECT');
	}
	static function get_dailyimports() {
	  global $wpdb;
	  return $wpdb->get_col("SELECT id FROM ".GAN_PRODUCT_SUBSCRIPTIONS_TABLE." where dailyimport = 1");
	}
	static function daily_import_new() {
	  $ids = GAN_Products::get_dailyimports();
	  foreach ( $ids as $theitem ) {
	    GAN_Products::import_products($theitem);
	  }
	}
	/* Single item: add, edit, view */
	function admin_product_add_subscriptions() {
	  $message = $this->prepare_one_item();
	  ?><div class="wrap"><div id="<?php echo $this->add_item_icon(); ?>" class="icon32"><br />
	    </div><h2><?php echo $this->add_item_h2(); ?><?php   
				     $this->gan->InsertVersion(); ?></h2>
	    <?php $this->gan->PluginSponsor(); 
		  $this->gan->InsertH2AffiliateLoginButton(); ?>
	    <?php if ($message != '') {
		?><div id="message" class="update fade"><?php echo $message; ?></div><?php
		} ?>
	    <form action="<?php echo admin_url('admin.php'); ?>" method="get">
	    <input type="hidden" name="page" value="gan-database-add-products" />
	    <?php $this->display_one_item_form(
			add_query_arg(array('page' => 'gan-database-products',
			'mode' => false, 
			'id' => false))); ?></form></div><?php
	}
	static function insert_prod($item) {
	  global $wpdb;
	  if (preg_match("/^[[:digit:]]/",$item->MerchantID)) {
	    $item->MerchantID = 'K'.$item->MerchantID;
	  }
	  $item->MerchantID = strtoupper($item->MerchantID);
	  $wpdb->insert(GAN_PRODUCT_SUBSCRIPTIONS_TABLE,
			array('zipfilepath' => $item->zipfilepath,
			      'MerchantID' => $item->MerchantID,
			      'dailyimport' => $item->dailyimport),
			array("%s", "%s", "%d") );
	  $newid = $wpdb->insert_id;
	  return ($newid);
	}
	static function update_prod($item) {
	  global $wpdb;
	  if (preg_match("/^[[:digit:]]/",$item->MerchantID)) {
	    $item->MerchantID = 'K'.$item->MerchantID;
	  }
	  $item->MerchantID = strtoupper($item->MerchantID);
	  $wpdb->update(GAN_PRODUCT_SUBSCRIPTIONS_TABLE,
			array('zipfilepath' => $item->zipfilepath,
			      'MerchantID' => $item->MerchantID,
			      'dailyimport' => $item->dailyimport),
			array('id' => $item->id),
			array("%s","%s","%d"),
			array("%d"));
	  return $item->id;	  
	}
	static function get_prod($id) {
	  global $wpdb;
	  $sql = $wpdb->prepare("SELECT * FROM ".
				GAN_PRODUCT_SUBSCRIPTIONS_TABLE.
				" WHERE ID = %d",$id);
	  $result = $wpdb->get_row($sql, 'OBJECT' );
	  return $result;
	}
	static function find_prod_merchid($MerchantID) {
	  global $wpdb;
	  if (preg_match("/^[[:digit:]]/",$MerchantID)) {
	    $MerchantID = 'K'.$MerchantID;
	  }
	  $MerchantID = strtoupper($MerchantID);
	  $sql = $wpdb->prepare("SELECT id FROM ".
				GAN_PRODUCT_SUBSCRIPTIONS_TABLE.
				" WHERE MerchantID = %s",$MerchantID);
	  return $wpdb->get_var($sql);
	}
	
	static function get_blank_prod() {
	  return (object) array(
	    'zipfilepath' => '',
	    'MerchantID' => '',
	    'dailyimport' => 1);
	}
	static function delete_import_job($theid) {
	  $products_batchqueue = get_option('wp_gan_products_batchqueue');
	  $the_queue = explode(':',$products_batchqueue);
	  $new_queue = array();
	  foreach ($the_queue as $job) {
	    $jobvect = explode(',',$job);
	    $fun = $jobvect[0];
	    $id  = $jobvect[1];
	    $skip = $jobvect[2];
	    if ($fun == 'I' && $id == $theid) continue;
	    else $new_queue[] = $job;
	  }
	  $products_batchqueue = implode(':',$new_queue);
	  update_option('wp_gan_products_batchqueue',$products_batchqueue);
	}
	static function delete_sub_by_id($id) {
	  GAN_Products::delete_import_job($id);
	  
	  $answer = GAN_Products::delete_products($id);
	  $item =   GAN_Products::get_prod($id);
	  if (get_option('wp_gan_products_shoppress') == 'yes') {
	    $q = new WP_Query(  array('meta_key'        => 'datafeedr_merchant_id',
				      'meta_value'      => $item->MerchantID) );
	  } else {
	    $q = new WP_Query(  array('meta_key'        => '_merchant_id',
				      'meta_value'      => $item->MerchantID) );
	  }
	  if (!$q->have_posts()) {
	    global $wpdb;
	    $sql = $wpdb->prepare("DELETE FROM ".
				  GAN_PRODUCT_SUBSCRIPTIONS_TABLE.
				  " WHERE ID = %d",$id);
	    $wpdb->query($sql);
	    $answer .= '<p>'.
		sprintf(__('%s product subscription deleted.','gan'),
			GAN_Database::get_merch_name($item->MerchantID)).
		'</p>';
	  } else {
	    $answer .= '<p>'.
		sprintf(__('%d pending posts to be deleted for %s.','gan'),
			   $q->found_posts,
			   GAN_Database::get_merch_name($item->MerchantID)).
		'</p>';
	  }
	  return $answer;
	}
	function checkiteminform($id) {
	  $result = '';
	  if ( empty($_REQUEST['MerchantID']) ) {
	    $result .= '<p>'.__('Advertiser missing.','gan').'</p>';
	  } else if ($id != GAN_Products::find_prod_merchid($_REQUEST['MerchantID'])) {
	    $result .= '<p>'.__('Duplicate Advertiser.','gan').'</p>';
	  }
	  if ( empty($_REQUEST['zipfilepath']) ) {
	    $result .= '<p>'.__('Zip File Path missing.','gan').'</p>';
	  }
	  return $result;
	}
	function getitemfromform() {
	  $itemary = array(
		'MerchantID' => $_REQUEST['MerchantID'],
		'zipfilepath' => $_REQUEST['zipfilepath'],
		'dailyimport' => $_REQUEST['dailyimport']);
	  return (OBJECT) $itemary;
	}
	function display_one_item_form($returnURL) {
	  if ( isset($_REQUEST['paged']) ) {
	    ?><input type="hidden" name="paged" value="<?php echo $_REQUEST['paged'] ?>" /><?php
	  }
	  if ( isset($_REQUEST['screen-options-apply']) ) {
	    ?><input type="hidden" name="screen-options-apply" value="<?php echo $_REQUEST['screen-options-apply'] ?>" /><?php
	  }
	  if ( isset($_REQUEST['wp_screen_options']['option']) ) {
	    ?><input type="hidden" name="wp_screen_options[option]" value="<?php echo $_REQUEST['wp_screen_options']['option'] ?>" /><?php
	  }
	  if ( isset($_REQUEST['wp_screen_options']['value']) ) {
	    ?><input type="hidden" name="wp_screen_options[value]" value="<?php echo $_REQUEST['wp_screen_options']['value'] ?>" /><?php
	  }
	  if ($this->viewmode != 'add') {
	    ?><input type="hidden" name="id" value="<?php echo $this->viewid; ?>" /><?php
	  }
	  $GANMerchants = GAN_Database::get_merchants();
	  ?><table class="form-table">
	    <tr valign="top">
		<th scope="row"><label for="GAN-MerchantID" style="width:20%;"><?php _e('Advertiser:','gan'); ?></label></th>
		<td><?php 
		    if ($this->viewmode == 'view') {
		    ?><input id="GAN-MerchantID"
			   value="<?php echo GAN_Database::get_merch_name($this->viewitem->MerchantID); ?>"
			   name="MerchantID"
			   style="width:75%;"
			   readonly="readonly" /><?php
		    } else { ?><select name="MerchantID" id="GAN-MerchantID" 
				style="width:75%;"<?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?>>
		    <option value="" <?php
			if ($this->viewitem->MerchantID == "")  echo 'selected="selected"';
			?>><?php _e('-- Select a Merchant --','gan'); ?></option><?php
		    foreach ((array)$GANMerchants as $GANMerchant) {
		      ?><option value="<?php echo $GANMerchant['MerchantID']; ?>" <?php
		      if ($this->viewitem->MerchantID == $GANMerchant['MerchantID'] )
			echo 'selected="selected"';
		      ?> label="<?php echo $GANMerchant['Advertiser'];
		      ?>"><?php echo $GANMerchant['Advertiser'] ?></option><?php
		    }
		  ?></select><?php } ?></td></tr>
	    <tr valign="top">
		<th scope="row"><label for="GAN-zipfilepath" style="width:20%;"><?php _e('Zip File Path:','gan'); ?></label></th>
		<td><input id="GAN-zipfilepath"
			   value="<?php echo $this->viewitem->zipfilepath; ?>" 
			   name="zipfilepath"
			   style="width:75%;"<?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?> /></td></tr>
	    <tr valign="top">
		<th scope="row"><label for="GAN-dailyimport"><?php _e('Import Daily?','gan'); ?></label></th>
		<td><input class="checkbox" type="checkbox"
			<?php checked( $this->viewitem->dailyimport, true ); ?>
			id="GAN-dailyimport" name="dailyimport" value="1"
			<?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?> /></td></tr>
	  </table>
	  <p>
		<?php switch($this->viewmode) {
			case 'add':
				?><input type="submit" name="addprod" class="button-primary" value="<?php _e('Add Product Subscription','gan'); ?>" /><?php
				break;
			case 'edit':
				?><input type="submit" name="updateprod" class="button-primary" value="<?php _e('Update Product Subscription','gan'); ?>" /><?php
				break;
		      } ?>
		<a href="<?php echo $returnURL; ?>" class="button-primary"><?php _e('Return','gan'); ?></a>
	  </p><?php
	}
	function add_item_icon() {
	  switch ($this->viewmode) {
	    case 'add': return 'icon-gan-add-prod';
	    case 'edit': return 'icon-gan-edit-prod';
	    case 'view': return 'icon-gan-view-prod';
	  }
	}
	function add_item_h2() {
	  switch ($this->viewmode) {
	    case 'add': return __('Add Product Subscription','gan');
	    case 'edit': return __('Edit Product Subscription','gan');
	    case 'view': return __('View Product Subscription','gan');
	  }
	}
	function check_permissions() {
	  if (!current_user_can('manage_options')) {
	    wp_die( __('You do not have sufficient permissions to access this page.','gan') );
	  }
	}
	function prepare_one_item() {
	  $this->check_permissions();
	  $message = '';
	  if ( isset($_REQUEST['addprod']) ) {
	    $message = $this->checkiteminform(0);
	    $item    = $this->getitemfromform();
	    if ($message == '') {
	      $newid = GAN_Products::insert_prod($item);
	      $message = '<p>'.sprintf(__('%s inserted with id %d.','gan'),
					  GAN_Database::get_merch_name($item->MerchantID),$newid);
	      $this->viewmode = 'edit';
	      $this->viewid   = $newid;
	      $this->viewitem = $item;
	    } else {
	      $this->viewmode = 'add';
	      $this->viewid   = 0;
	      $this->viewitem = $item;
	    }
	  } else if ( isset($_REQUEST['updateprod']) && isset($_REQUEST['id']) ) {
	    $message = $this->checkiteminform($_REQUEST['id']);
	    $item    = $this->getitemfromform();
	    $item->id = $_REQUEST['id'];
	    if ($message == '') {
	      GAN_Products::update_prod($item);
	      $message = '<p>'.sprintf(__('%s updated.','gan'),
					GAN_Database::get_merch_name($item->MerchantID)).'</p>';
	    }
	    $this->viewmode = 'edit';
	    $this->viewid   = $item->id;
	    $this->viewitem = $item;
	  } else {
	    $this->viewmode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : 'add';
	    $this->viewid   = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
	    switch ($this->viewmode) {
	      case 'edit':
	      case 'view': 
		if ($this->viewid == 0) {$this->viewmode = 'add';}
		break;
	      case 'add':
		$this->viewid   = 0;
		break;
	      default:
		$this->viewmode = 'add';
		$this->viewid   = 0;
		break;
	    }
	    if ($this->viewid != 0) {
	      $this->viewitem = GAN_Products::get_prod($this->viewid);
	    } else {
	      $this->viewitem = GAN_Products::get_blank_prod();
	    }	    
	  }
	  return $message;
	}
	function admin_product_configure_subscriptions () {
	  //must check that the user has the required capability 
	  if (!current_user_can('manage_options'))
	  {
	    wp_die( __('You do not have sufficient permissions to access this page.', 'gan') );
	  }
	  if ( isset($_REQUEST['saveoptions']) ) {
	    $products_shoppress = $_REQUEST['gan_products_shoppress'];
	    update_option('wp_gan_products_shoppress',$products_shoppress);
	    $products_postformat = $_REQUEST['gan_products_postformat'];
	    update_option('wp_gan_products_postformat',$products_postformat);
	    $products_css = $_REQUEST['gan_products_css'];
	    update_option('wp_gan_products_css',$products_css);
	    $products_customfields_vec = $_REQUEST['gan_products_customfields'];
	    if (empty($products_customfields_vec)) {
	      $products_customfields = '';
	    } else {
	      $products_customfields = implode(',',$products_customfields_vec);
	    }
	    update_option('wp_gan_products_customfields',$products_customfields);
	    $products_category_mode = $_REQUEST['gan_products_category_mode'];
	    update_option('wp_gan_products_category_mode',$products_category_mode);
	    $products_category_treesep = $_REQUEST['gan_products_category_treesep'];
	    update_option('wp_gan_products_category_treesep',$products_category_treesep);
	    $products_category_maxtreedepth = $_REQUEST['gan_products_category_maxtreedepth'];
	    update_option('wp_gan_products_category_maxtreedepth',$products_category_maxtreedepth);
	    $products_tagheaders_vec = $_REQUEST['gan_products_tagheaders'];
	    if (empty($products_tagheaders_vec)) {
	      $products_tagheaders = '';
	    } else {
	      $products_tagheaders = implode(',',$products_tagheaders_vec);
	    }
	    update_option('wp_gan_products_tagheaders',$products_tagheaders);
	    $products_matchcols_vec = $_REQUEST['gan_products_matchcols'];
	    if (empty($products_matchcols_vec)) {
	      $products_matchcols = '';
	    } else {
	      $products_matchcols = implode(',',$products_matchcols_vec);
	    }
	    update_option('wp_gan_products_matchcols',$products_matchcols);
	    $products_matchpattern = $_REQUEST['gan_products_matchpattern'];
	    update_option('wp_gan_products_matchpattern',$products_matchpattern);
	    ?><div id="message"class="updated fade"><p><?php _e('Options Saved','gan'); ?></p></div><?php
	  }
	  /* Head of page, filter and screen options. */
	  $products_shoppress = get_option('wp_gan_products_shoppress');
	  $products_postformat = get_option('wp_gan_products_postformat');
	  $products_css = get_option('wp_gan_products_css');
	  $products_customfields = explode(',',get_option('wp_gan_products_customfields'));
	  $products_category_mode = get_option('wp_gan_products_category_mode');
	  $products_category_treesep = get_option('wp_gan_products_category_treesep');
	  $products_category_maxtreedepth = get_option('wp_gan_products_category_maxtreedepth');
	  $products_tagheaders = explode(',',get_option('wp_gan_products_tagheaders'));
	  $products_matchcols = explode(',',get_option('wp_gan_products_matchcols'));
	  $products_matchpattern = get_option('wp_gan_products_matchpattern');
	  $products_batchqueue = get_option('wp_gan_products_batchqueue');
	  ?><div class="wrap"><div id="icon-gan-prod-options" class="icon32"><br /></div><h2><?php _e('Configure Product Options','gan'); ?><?php $this->gan->InsertVersion(); ?></h2>
	    <?php $this->gan->PluginSponsor(); ?>
	    <form method="post" action="">
	    	<input type="hidden" name="page" value="gan-database-configure-products" />
		<table class="form-table">
		  <tr valign="top">
		    <th scope="row"><label for="gan_products_shoppress" style="width:20%;"><?php _e('Import for Shopper Press?','gan'); ?></label></th>
		    <td><input type="radio" name="gan_products_shoppress" value="yes"<?php
				if ($products_shoppress == 'yes') {
				  echo ' checked="checked" ';
				} 
			?> /><?php _e('Yes','gan'); ?>&nbsp;<input type="radio" name="gan_products_shoppress" value="no"<?php
				if ($products_shoppress == 'no') {
				  echo ' checked="checked" ';
				}
			?> /><?php _e('No','gan'); ?></td></tr>
		  <tr valign="top">
		    <th scope="row"><label for="gan_products_postformat" style="width:20%;"><?php _e('Post format (for other than Shopper Press)','gan'); ?></label></th>
		    <td><textarea name="gan_products_postformat" 
				  id="gan_products_postformat"
				  rows="5" cols="40"><?php echo stripslashes($products_postformat); ?></textarea></td></tr>
		  <tr valign="top">
		    <th scope="row"><label for="gan_products_css" style="width:20%;"><?php _e('CSS for posts (for other than Shopper Press)','gan'); ?></label></th>
		    <td><textarea name="gan_products_css" 
				  id="gan_products_css"
				  rows="5" cols="40"><?php echo stripslashes($products_css); ?></textarea></td></tr>
		  <tr valign="top">
		    <th scope="row"><label for="gan_products_customfields" style="width:20%;"><?php _e('Custom Fields (for other than Shopper Press)','gan'); ?></label></th>
		    <td><?php
			$cols = 0;
			foreach (explode(',',GAN_PROD_HEADERS) as $fieldname) {
			  ?><input type="checkbox" 
				   name="gan_products_customfields[]" 
				   value="<?php echo $fieldname; ?>"
				   <?php if (in_array($fieldname,$products_customfields))
						echo ' checked="checked"'; ?> /><?php
			    echo '&nbsp;'.$fieldname;
			    $cols++;
			    if ($cols < 5) echo '&nbsp;';
			    else {
				echo '<br />';
				$cols = 0;
			    }
			} ?></td></tr>
		  <tr valign="top">
		    <th scope="row"><label for="gan_products_category_mode" style="width:20%;"><?php _e('Category Mode:','gan'); ?></label></th>
		    <td style="width:75%;">
		        <!-- <?php echo "products_category_mode = $products_category_mode"; ?> -->
			<input id="gan_products_category_mode"
			       name="gan_products_category_mode"
			       type="radio"
			       value="category_tree"
			       <?php if ($products_category_mode == 'category_tree') echo 'checked="checked"'; ?>
			       />Category Tree<br />
			&nbsp;&nbsp;<label for="gan_products_category_treesep"><?php
			  _e('Tree branch separator:','gan'); 
			?></label>&nbsp;<input id="gan_products_category_treesep"
					 name="gan_products_category_treesep"
					 type="text" size="1" maxlength="1" 
					 value="<?php 
					echo $products_category_treesep; 
			?>" />&nbsp;<label for="gan_products_category_maxtreedepth"><?php
			  _e('Max tree depth (0 means unlimited)','gan');
			?></label>&nbsp;<input 
				    id="gan_products_category_maxtreedepth"
			 	    name="gan_products_category_maxtreedepth"
				    type="text" size="2" maxlength="2"
				    value="<?php 
					echo $products_category_maxtreedepth;
			?>" /><br clear="all"/>
			<input id="gan_products_category_mode"
			       name="gan_products_category_mode"
			       type="radio"
			       value="category_flat"
			       <?php if ($products_category_mode == 'category_flat') echo 'checked="checked"'; ?>
			       />Flat Category<br />
			<input id="gan_products_category_mode"
			       name="gan_products_category_mode"
			       type="radio"
			       value="brand"
			       <?php if ($products_category_mode == 'brand') echo 'checked="checked"'; ?>
			       />Brand as Category<br />
			<input id="gan_products_category_mode"
			       name="gan_products_category_mode"
			       type="radio"
			       value="merchant"
			       <?php if ($products_category_mode == 'merchant') echo 'checked="checked"'; ?>
			       />Merchant as Category</td></tr>
		  <tr valign="top">
		    <th scope="row"><label for="gan_products_tagheaders" style="width:20%;"><?php _e('Tag Columns','gan'); ?></label></th>
		    <td><?php
			$cols = 0;
			foreach (explode(',',GAN_PROD_TAGABLE_HEADERS) as $fieldname) {
			  ?><input type="checkbox" 
				   name="gan_products_tagheaders[]" 
				   value="<?php echo $fieldname; ?>"
				   <?php if (in_array($fieldname,$products_tagheaders))
						echo ' checked="checked"'; ?> /><?php
			    echo '&nbsp;'.$fieldname;
			    $cols++;
			    if ($cols < 5) echo '&nbsp;';
			    else {
				echo '<br />';
				$cols = 0;
			    }
			} ?></td></tr>
		  <tr valign="top">
		    <th scope="row"><label for="gan_products_matchcols" style="width:20%;"><?php _e('Matchable Columns','gan'); ?></label></th>
		    <td><?php
			$cols = 0;
			foreach (explode(',',GAN_PROD_TAGABLE_HEADERS) as $fieldname) {
			  ?><input type="checkbox" 
				   name="gan_products_matchcols[]" 
				   value="<?php echo $fieldname; ?>"
				   <?php if (in_array($fieldname,$products_matchcols))
						echo ' checked="checked"'; ?> /><?php
			    echo '&nbsp;'.$fieldname;
			    $cols++;
			    if ($cols < 5) echo '&nbsp;';
			    else {
				echo '<br />';
				$cols = 0;
			    }
			} ?></td></tr>
		  <tr valign="top">
		    <th scope="row"><label for="gan_products_matchpattern" style="width:20%;"><?php _e("Column Match Pattern (don't forget the delimiters!):",'gan'); ?></label></th>
		    <td><input id="gan_products_matchpattern"
		    	       value="<?php echo $products_matchpattern; ?>"
			       name="gan_products_matchpattern"
			       style="width:75%;" /><br />
			<a href="http://us.php.net/manual/en/reference.pcre.pattern.syntax.php"><?php _e('Pattern Syntax','gan'); ?></a></td></tr>
		  <tr valign="top">
		    <th scope="row"><label for="gan_products_batchqueue" style="width:20%;"><?php _e('Batch Queue:','gan'); ?></label></th>
		    <td><input id="gan_products_batchqueue"
		    	       value="<?php echo $products_batchqueue; ?>"
			       name="gan_products_batchqueue"
			       style="width:75%;"
			       readonly="readonly" /></td></tr>
		</table>
		<p>
			<input type="submit" name="saveoptions" class="button-primary" value="<?php _e('Save Options','gan'); ?>" />
		</p></form></div><?php
	}
	function wp_head () {
	  if (get_option('wp_gan_products_shoppress') == 'no' && 
	    get_option('wp_gan_products_css') != '') {
	    echo '<style type="text/css">'.get_option('wp_gan_products_css').'</style>';
	  }
	}
	function admin_head () {
	  $path = GAN_PLUGIN_CSS_URL . '/GAN_Prod_admin.css';
	  echo '<link rel="stylesheet" type="text/css" href="' . $path . '?version='.GAN_VERSION.'" />';
	}
	function wp_dashboard_setup () {
	}
	static function import_products($id,$skip=0) {
	  $item = GAN_Products::get_prod($id);
	  $Advertiser = GAN_Database::get_merch_name($item->MerchantID);
	  $products_shoppress = get_option('wp_gan_products_shoppress');
	  $products_postformat = get_option('wp_gan_products_postformat');
	  $products_customfields = explode(',',get_option('wp_gan_products_customfields'));
	  $products_matchcols = explode(',',get_option('wp_gan_products_matchcols'));
	  $products_matchpattern = get_option('wp_gan_products_matchpattern');
	  $count = 0;
	  $skip_index = 0;
	  $zip = new ZipArchive;
	  $res = $zip->open($item->zipfilepath);
	  if ($res === TRUE) {
	    $fp = $zip->getStream(basename($item->zipfilepath,'.zip').'.txt');
	    $headers = explode("\t",fgets($fp));
	    while ($line = fgets($fp)) {
	      $skip_index++;
	      if ($skip_count < $skip) {continue;}
	      $rowvect =  explode("\t",$line);
	      $row = array();
	      foreach ($headers as $index => $header) {
		$row[$header] = $rowvect[$index];
	      }
	      $rowobj = (OBJECT) $row;
	      $matched = count($products_matchcols) == 0;
	      foreach ($products_matchcols as $matchcolumn) {
		if (preg_match($products_matchpattern,$row[$matchcolumn]) > 0) {
		  $matched = true;
		  break;
		}
	      }
	      if ($matched) {
	        if ($products_shoppress == 'yes') {
		  GAN_Products::import_products_as_shoppress($rowobj,
							     $item->MerchantID,
							     $Advertiser);
	        } else {
		  GAN_Products::import_products_as_other($rowobj,
						         $products_postformat,
						         $products_customfields,
						         $item->MerchantID,
						         $Advertiser);
	        }
	        $count++;
	      }
	      if (($skip_index & 0x01ff) == 0) {
		$times = posix_times();
		if ($times['utime'] > GAN_TIMELIMIT) {
		  $products_batchqueue = get_option('wp_gan_products_batchqueue');
		  if ($products_batchqueue == "") {
		    wp_schedule_event(time()+60, 'hourly', 'wp_gan_products_batchrun');
		  }
		  // Queue batch: $id,$skip_count
		  $the_queue = explode(':',$products_batchqueue);
		  $the_queue[] = sprintf("I,%d,%d",$id,$skip_index);
		  $products_batchqueue = implode(':',$the_queue);
		  update_option('wp_gan_products_batchqueue',$products_batchqueue);
		  break;
		}
	      }
	    }
	    fclose($fp);
	    $zip->close();
	    return sprintf(__('%d Products imported from %s.','gan'),$count,$item->zipfilepath);
	  } else {
 	    return sprintf(__('Failed to open %s: %d.','gan'),$item->zipfilepath,$res);
	  }
	}
	static function import_products_as_shoppress($rowobj,$MerchantID,
							$Advertiser) {
	  $products_category_mode = get_option('wp_gan_products_category_mode');
	  if (!in_array($products_category_mode,array('category_tree','category_flat','brand','merchant'))) {
	    $products_category_mode = 'category_tree';
	  }
	  $products_category_treesep = get_option('wp_gan_products_category_treesep');
	  if ($products_category_mode == 'category_tree' && $products_category_treesep == '') {
	    $products_category_treesep = '>';
	  }
	  $products_category_maxtreedepth = get_option('wp_gan_products_category_maxtreedepth');
	  if ($products_category_mode == 'category_tree' && $products_category_maxtreedepth == '') {
	    $products_category_maxtreedepth = 0;
	  }
	  
	  // SETUP MAIN DATA
	  $my_post = array(
	  	'post_title'		  => $rowobj->ProductName,
	  	'post_content'		  => $rowobj->LongDesc,
	  	'post_excerpt'		  => $rowobj->ShortDesc,
	  	'post_author'		  => 1,
	  	'post_status'		  => "publish"
		);
	  switch ($products_category_mode) {
	    case 'category_tree':
	      $my_post['post_category'] = GAN_Products::getTheCat(
			explode($products_category_treesep,$rowobj->Category),
			$products_category_maxtreedepth);
	      break;
	    case 'category_flat':
	      $my_post['post_category'] = GAN_Products::getTheCat(
			array($rowobj->Category),1);
	      break;
	    case 'brand':
	      $my_post['post_category'] = GAN_Products::getTheCat(
			array($rowobj->Brand),1);
	      break;
	    case 'merchant':
	      $my_post['post_category'] = GAN_Products::getTheCat(
			 array($Advertiser),1);
	      break;
	  }
	  $my_post['tags_input'] = GAN_Products::getTheTags((array)$rowobj,
					explode(',',get_option('wp_gan_products_tagheaders')));
	  $customFields = array(
		"price"			=> $rowobj->Price,
		"featured"		=> "no",
		"image"			=> $rowobj->ImageURL,
		"buy_link"		=> $rowobj->BuyURL,
		"hits"			=> 0,
		"datafeedr_productID" 	=> $rowobj->ProductID,
		"datafeedr_network"	=> "Google Affiliate Network",
		"datafeedr_merchant"	=> $Advertiser,
		"datafeedr_merchant_id" => $MerchantID
		);
	  $posts = get_posts( array ('numberposts'     => 1,
				     'meta_key'        => 'datafeedr_productID',
				     'meta_value'      => $rowobj->ProductID) );
	  if ( empty($posts) ) {
	    $POSTID = wp_insert_post( $my_post );
	    foreach($customFields as $key=>$val){ 
	      add_post_meta($POSTID,$key,$val); 
	    }
	  } else {
	    $POSTID = $posts[0]->ID;
	    $my_post['ID']  = $POSTID;
	    wp_update_post( $my_post );
	    foreach($customFields as $key=>$val){ 
	      update_post_meta($POSTID,$key,$val); 
	    }
	  }
	}
	static function import_products_as_other($rowobj,$postformat,
						 $customfields,$MerchantID,
						 $Advertiser) {
	  $products_category_mode = get_option('wp_gan_products_category_mode');
	  if (!in_array($products_category_mode,array('category_tree','category_flat','brand','merchant'))) {
	    $products_category_mode = 'category_tree';
	  }
	  $products_category_treesep = get_option('wp_gan_products_category_treesep');
	  if ($products_category_mode == 'category_tree' && $products_category_treesep == '') {
	    $products_category_treesep = '>';
	  }
	  $products_category_maxtreedepth = get_option('wp_gan_products_category_maxtreedepth');
	  if ($products_category_mode == 'category_tree' && $products_category_maxtreedepth == '') {
	    $products_category_maxtreedepth = 0;
	  }
	  $rowary = (ARRAY)$rowobj;
	  $my_post = array(
		'post_title'		  => $rowobj->ProductName,
	  	'post_content'		  => GAN_Products::substfields($postformat,$rowary),
	  	'post_excerpt'		  => $rowobj->ShortDesc,
	  	'post_author'		  => 1,
		'post_status'		  => "publish"
		);
	  switch ($products_category_mode) {
	    case 'category_tree':
	      $my_post['post_category'] = GAN_Products::getTheCat(
			explode($products_category_treesep,$rowobj->Category),
			$products_category_maxtreedepth);
	      break;
	    case 'category_flat':
	      $my_post['post_category'] = GAN_Products::getTheCat(
			array($rowobj->Category),1);
	      break;
	    case 'brand':
	      $my_post['post_category'] = GAN_Products::getTheCat(
			array($rowobj->Brand),1);
	      break;
	    case 'merchant':
	      $my_post['post_category'] = GAN_Products::getTheCat(
			 array($Advertiser),1);
	      break;
	  }
	  $my_post['tags_input'] = GAN_Products::getTheTags((array)$rowobj,
			explode(',',get_option('wp_gan_products_tagheaders')));
	  $customFields = array(
	    '_ProductID'	=> $rowobj->ProductID,
	    '_network'		=> "Google Affiliate Network",
	    '_merchant'		=> $Advertiser,
	    '_merchant_id'	=> $MerchantID
	  );
	  foreach ($customfields as $field) {
	    $customFields[$field] = $rowary[$field];
	  }
	  $posts = get_posts( array ('numberposts'     => 1,
				     'meta_key'        => '_ProductID',
				     'meta_value'      => $rowobj->ProductID) );
	  if ( empty($posts) ) {
	    $POSTID = wp_insert_post( $my_post );
	    foreach($customFields as $key=>$val){
	      add_post_meta($POSTID,$key,$val);
	    }
	  } else {
	    $POSTID = $posts[0]->ID;
	    $my_post['ID']  = $POSTID;
	    wp_update_post( $my_post );
	    foreach($customFields as $key=>$val){
	      update_post_meta($POSTID,$key,$val);
	    }
	  }
	}
	static function getTheCat($catlist,$maxdepth) {
	  $parent = 0;
	  $catid  = '0';
	  $resultarray = array();
	  $depth = 0;
	  foreach ($catlist as $catname) {
	    $catid = get_cat_ID($catname);
	    if ( !$catid ) {
	      if ($parent) {
		$catid = wp_create_category($catname, $parent);
	      } else {
		$catid = wp_create_category($catname);
	      }
	    }
	    $resultarray[] = $catid;
	    $parent = $catid;
	    $depth++;
	    if ($maxdepth > 0 && $depth >= $maxdepth) break;
	  }
	  return $resultarray;		
	}
	static function getTheTags($rowary,$tagheads) {
	  $resultarray = array();
	  foreach ($tagheads as $taghead) {
	    $resultarray[] = $rowary[$taghead];
	  }
	  return $resultarray;
	}
	static function substfields($postformat,$rowary) {
	  $result = '';
	  $source = str_replace("\n",'\n',$postformat);
	  while ($source != '') {
	    if (($n=preg_match('/^([^%]*)%([\w][\w]*)(.*)/',$source,$matches)) > 0) {
	      $result .= $matches[1];
	      $result .= $rowary[$matches[2]];
	      $source = $matches[3];
	    } else if (($n=preg_match('/^([^%]*)%%(.*)/',$source,$matches)) > 0) {
	      $result .= $matches[1];
	      $result .= '%';
	      $source = $matches[2];
	    } else {
	      $result .= $source;
	      $source = '';
	    }
	  }
	  return str_replace('\n',"\n",$result);
	}
	static function run_batch() {
	  $products_batchqueue = get_option('wp_gan_products_batchqueue');
	  $the_queue = explode(':',$products_batchqueue);
	  $job = $the_queue[0];
	  $the_queue = array_slice($the_queue,1);
	  $products_batchqueue = implode(':',$the_queue);
	  update_option('wp_gan_products_batchqueue',$products_batchqueue);
	  if ($products_batchqueue == '') {
	    wp_clear_scheduled_hook('wp_gan_products_batchrun');
	  }
	  $jobvect = explode(',',$job);
	  $fun = $jobvect[0];
	  $id  = $jobvect[1];
	  $skip = $jobvect[2];
	  if ($fun == 'I') {
	    GAN_Products::import_products($id,$skip);
	  } else if ($fun == 'D') {
	    GAN_Products::delete_sub_by_id($id);
	  }
	}
	static function delete_products($id) {
	  $item =   GAN_Products::get_prod($id);
	  if (get_option('wp_gan_products_shoppress') == 'yes') {
	    $q = new WP_Query(  array('posts_per_page' => 1024,
				      'meta_key'        => 'datafeedr_merchant_id',
				      'meta_value'      => $item->MerchantID) );
	  } else {
	    $q = new WP_Query(  array('posts_per_page' => 1024,
				      'meta_key'        => '_merchant_id',
				      'meta_value'      => $item->MerchantID) );
	  }
	  if (!$q->have_posts()) return '';
	  $count = 0;
	  file_put_contents("php://stderr","*** GAN_Products::delete_products: q->found_posts = $q->found_posts\n");
	  file_put_contents("php://stderr","*** GAN_Products::delete_products: q->post_count = $q->post_count\n");
	  for ($i = 0; $i < $q->post_count; $i++) {
	    $p = $q->next_post();
	    //file_put_contents("php://stderr","*** GAN_Products::delete_products: i = $i, p->ID = $p->ID\n");
	    $ans = wp_delete_post($p->ID, true);
	    //file_put_contents("php://stderr","*** GAN_Products::delete_products: ans = ".print_r($ans,true)."\n");
	    if (!empty($ans)) $count++;
	    if (($count & 0x01ff) == 0) {
	      $times = posix_times();
	      if ($times['utime'] > GAN_TIMELIMIT) {
		$products_batchqueue = get_option('wp_gan_products_batchqueue');
		if ($products_batchqueue == "") {
		  wp_schedule_event(time()+60, 'hourly', 'wp_gan_products_batchrun');
		}
		// Queue batch: $id,$skip_count
		$the_queue = explode(':',$products_batchqueue);
		$the_queue[] = sprintf("D,%d",$id);
		$products_batchqueue = implode(':',$the_queue);
		update_option('wp_gan_products_batchqueue',$products_batchqueue);
		break;
	      }
	    }
	  }
	  file_put_contents("php://stderr","*** GAN_Products::delete_products: count = $count\n");
	  return sprintf(__('%d product posts deleted from %s.','gan'),
			 $count,GAN_Database::get_merch_name($item->MerchantID) );
	}
}

?>
