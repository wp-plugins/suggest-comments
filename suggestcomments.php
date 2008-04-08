<?php
/*
Plugin Name: Suggest Comments
Plugin URI: http://blog.quickes-wohnzimmer.de/suggestcomments
Description: Suggest some comments to your lazy visitors :)  
Version: 0.2
Author: Rene Springborn
Author URI: http://blog.quickes-wohnzimmer.de

  Copyright 2007  Rene Springborn  (email : plugins at quickes-wohnzimmer.de)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

add_action('wp_print_scripts', 'suggestcomment_js_header' );
add_action('comment_form','suggestcomment_form_items');
add_action('admin_menu', 'suggestcomment_add_options_to_admin');

register_activation_hook(__FILE__,'suggestcomment_activate');

//js for replacing the current comment with a suggested one
function suggestcomment_js_header(){
?>
<script type="text/javascript">
//<![CDATA[
function suggestcomment_replace_comment(comment)
{
replaceIt = confirm("Current comment will be replaced.");
 if (replaceIt==true){ 
 		window.document.forms['commentform'].elements['comment-text'].value=comment
		} 
}
//]]>
</script>
<?php
}

//add admin menu
function suggestcomment_add_options_to_admin() {
    if (function_exists('add_options_page')) {
add_options_page('suggestcomment', 'Suggest Comments', '8', basename(__FILE__), 'suggestcomment_options_subpanel');
    }
 }

//admin menu
function suggestcomment_options_subpanel() {
		// database version
		$suggestcomment_db_version = get_option('suggestcomment_db_version');
		//short introduction above the given comments 
		$suggestcomment_describe = stripslashes(get_option('suggestcomment_describe'));

		//update field information in database
		if (isset($_POST['submit'])) {
			 global $wpdb;
 	 			$new_comment = $_POST['suggestcomment_new_comment'];
				if ($new_comment<>""){suggestcomment_add_comment($new_comment);}	
				$suggestcomment_describe = stripslashes($_POST['suggestcomment_describe']);
				update_option('suggestcomment_describe', $wpdb->escape($suggestcomment_describe));
				}
		
		// is there a comment to be deleted?
		if (isset($_REQUEST['delete'])) {
			 $id = $_REQUEST['delete'];
			 suggestcomment_delete_comment($id);
		}
				
//draw admin menu
	?>
	<div class=wrap>
  <form method="post">
    <h2>Suggest Comments</h2>
		<form name="form1" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
  	<fieldset class="options">
		<legend>Suggested comments</legend>
		<table width="100%" cellspacing="2" cellpadding="5" class="editform"> 

		<table class="widefat">
			<thead>
				<tr>
					<th scope="col">Comment</th>
					<th scope="col">&nbsp;</th>
				</tr>
			</thead>
	
		<tbody id="the-list">

<?php 
			//retrieve comments from database
			$commentlist = suggestcomment_get_comments();
			foreach ($commentlist as $sugcomment){
				echo "<tr id='".$sugcomment->comment_id."' class='alternate author-self status-future'>\n";
				echo "<td><input size=\"80\"type=\"text\" name='suggestcomment_".$sugcomment->comment_id."' value=\"".htmlspecialchars(stripslashes($sugcomment->comment_text))."\"></td>\n";
				echo "<td><div style=\"text-align: center\"><a href='".GetBackLink()."&delete=".$sugcomment->comment_id."'>L&ouml;schen</a></div></td>\n";
				echo "</tr>\n";
			}  

?>
			<tr class='alternate author-self status-future'><td><input size="80" type= "text" name="suggestcomment_new_comment"></td><td><div style="text-align: center">(new entry)</div></td></tr>
			<tr class='alternate author-self status-future'><td><input size="80" type= "text" name="suggestcomment_describe" value="<?php echo $suggestcomment_describe; ?>"></td><td><div style="text-align: center">(show above selection)</div></td></tr>
					</tbody>
				</table>

			</table>
			 </fieldset>
		 	
      		<div class="submit">
			<input type="submit" name="submit" value="<?php _e('Update Options') ?> &raquo;" />
		</div>
  </form>
 </div><?php

}

//options page link
function GetBackLink() {
		$page = basename(__FILE__);
		if(isset($_GET['page']) && !empty($_GET['page'])) {
			$page = preg_replace('[^a-zA-Z0-9\.\_\-]','',$_GET['page']);
		}
		return $_SERVER['PHP_SELF'] . "?page=" .  $page;	
	}
	

//display selection on comment form
function suggestcomment_form_items()
{
$commentlist = suggestcomment_get_comments();
$suggestcomment_describe = stripslashes(get_option('suggestcomment_describe'));

echo $suggestcomment_describe."<br>";
foreach ($commentlist as $sugcomment){
 echo "<input type=\"radio\" name=\"suggestcomment_comment\" value=\"".stripslashes($sugcomment->comment_text)."\" onchange=\"suggestcomment_replace_comment(this.value)\"> ".stripslashes($sugcomment->comment_text."<br>");
}
}

//plugin installation
function suggestcomment_install () {
		global $wpdb;
		$table_name = $wpdb->prefix . "suggestcomment";

   $suggestcomment_db_version = "0.2";
	 $suggestcomment_describe = "Suggested comments<br><small>No own opinion? Choose one of mine ;)</small>";

	 add_option('suggestcomment_db_version', $suggestcomment_db_version,'Database version');
	 add_option('suggestcomment_describe', $wpdb->escape($suggestcomment_describe),'Description');
  
	
   if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
    $sql = "CREATE TABLE " . $table_name . " (
	  		 comment_id mediumint(9) NOT NULL AUTO_INCREMENT,
	  		 comment_text text NOT NULL,
	 			 UNIQUE KEY comment_id (comment_id));";


      dbDelta($sql);

			$welcome_text[0] = "Howdy!";
			$welcome_text[1] = "You made my day!";
			$welcome_text[2] = "Bravissimo!";
			$welcome_text[3] = "Who cares?";
			$welcome_text[4] = "Are you alright?";

			foreach ($welcome_text as $no=>$text){
				suggestcomment_add_comment($text);
			}
     
  }
   }

 //returns all comments	 
function suggestcomment_get_comments(){
 global $wpdb;
 $table_name = $wpdb->prefix . "suggestcomment";
 $results = $wpdb->get_results("SELECT * FROM $table_name");
 return $results;
}

//add comment to database
function suggestcomment_add_comment($text){
 global $wpdb;
 $table_name = $wpdb->prefix . "suggestcomment";
 $sql="INSERT INTO " . $table_name ." (comment_text) " ."VALUES ('" . $wpdb->escape($text) . "');";
 $results = $wpdb->query($sql);
}

//delete comment from database
function suggestcomment_delete_comment($id){
 global $wpdb;
 $table_name = $wpdb->prefix . "suggestcomment";
 $sql = "DELETE FROM $table_name WHERE comment_id='$id'";
 $results = $wpdb->query($sql);
}

//activation
function suggestcomment_activate()
{
 suggestcomment_install();
}

?>