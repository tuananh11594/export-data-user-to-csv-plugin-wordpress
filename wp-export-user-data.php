<?php 
/**
 * Plugin Name: Export User Data
 * Plugin URI: https://niteco.se
 * Description: Export Users data and metadata to a csv file.
 * Version: 0.0.2
 * Author: Tuan Anh
 * Author URI: https://niteco.se
 */

if( !class_exists('Export_User_Data' )) {

    class Export_User_Data {

        function __construct() {
            add_action( 'init', array( $this, 'add_shortcode_export_user_data_front_end') );   
            add_action('show_user_profile', array( $this, 'add_button_export_data_user_profile_fields' ));
            add_action('edit_user_profile', array( $this, 'add_button_export_data_user_profile_fields' ));
  
            if (isset($_GET['download_file'])) {
                if (is_admin()) {
                    add_action( 'init', array( $this, 'download_with_user_id') );  
                } else {
                    add_action( 'init', array( $this, 'download_with_current_user') );  
                }
            }

        }
        function add_shortcode_export_user_data_front_end() {
            add_shortcode('ni_shortcode_export_user_data', array($this, 'add_button_export_data_user_profile_front_end'));
        }

        function add_button_export_data_user_profile_front_end() {
            ?>
            <script>
            function downloadFileExportDataFontend(){
                location.href += "?download_file";                 
            }
            </script>
			<button class="button wp-generate-pw hide-if-no-js" type="button" onclick="downloadFileExportDataFontend()">Export data</button>
            <?php
        } 

        function add_button_export_data_user_profile_fields() {
            ?>
            <script>
            function downloadFileExportData(){
                var urlProfile = document.location.origin + "/wp-admin/profile.php";
                if (location.href === urlProfile) {
                    location.href = "?download_file";                  
                } else {
                    location.href += "&download_file";
                }
            }
            </script>
            <table class="form-table">
                <tr>
                    <th>
                        <label for="button_export_user_data"><?php _e('Export user data'); ?></label>
                    </th>
                    <td>
                        <button class="button wp-generate-pw hide-if-no-js" type="button" onclick="downloadFileExportData()">Download export file</button>
                    </td>
                </tr>
                </table>
            <?php
        }

        function download_with_user_id () {
            $user_id = null;
            if (isset($_GET['user_id'])) {
                $user_id = $_GET['user_id'];
            } else {
                $user_id = get_current_user_id();
            }

            $this->download_export_user_data($user_id);
        }

        function download_with_current_user () {
            $user_id = get_current_user_id();
            $this->download_export_user_data($user_id);
        }

        function download_export_user_data($user_id) {
            ob_clean();
            global $wpdb; 

            $user = new WP_User( $user_id );
            if (!$user->exists()) {
                $error = "User does not exists!";
                return $error;
            }

            $user_name = null;            
            $header_row = array();
            $data_rows = array();
            $queryUserInfo = "SELECT user_login, user_nicename, user_email, user_url, user_registered, display_name FROM `wp_users` where `ID` = ".$user_id."";
            $dataUserInfo = $wpdb->get_results( $queryUserInfo, 'ARRAY_A' );
            if ($dataUserInfo == []) {
                $error = "User does not have data!";
                return $error;
            }

            foreach($dataUserInfo[0] as $key => $value) {
                array_push($header_row , $key);
                array_push($data_rows , $value);
                if ($key == 'display_name') {
                    $user_name = $value;
                }     
            }

            $queryUserMeta = "SELECT * FROM `wp_usermeta` where `user_id` = ".$user_id." AND `meta_key` IN ('nickname','first_name','last_name','description','wp_user_avatar', 'googleplus', 'twitter', 'facebook', 'alt_email', 'phone_number', 'last_login_time') ";		            
            $dataUserMeta = $wpdb->get_results( $queryUserMeta, 'ARRAY_A' );
            if ($dataUserMeta == []) {
                $error = "User does not have metadata!";
                return $error;
            }

            foreach($dataUserMeta as $items) {
                foreach($items as $key => $value) {
                    if ($key == 'meta_key') {
                        array_push($header_row , $value);
                    }
                    if ($key == 'meta_value') {
                        array_push($data_rows , $value);
                    }
                }
            }

            $sitename = sanitize_key( get_bloginfo( 'name' ) );
			if ( ! empty( $sitename ) ) {
				$sitename .= '.';
            }

            if ( ! empty( $user_name ) ) {
				$user_name .= '.';
            }
            
            $filename = $sitename . $user_name . date( 'Y-m-d-H-i-s' ) . '.csv';
    
            $fh = @fopen( 'php://output', 'w' );
            fprintf( $fh, chr(0xEF) . chr(0xBB) . chr(0xBF) );
            header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
            header( 'Content-Description: File Transfer' );
            header( 'Content-type: text/csv' );
            header( "Content-Disposition: attachment; filename={$filename}" );
            header( 'Expires: 0' );
            header( 'Pragma: public' );
            fputcsv( $fh, $header_row );
            fputcsv( $fh, $data_rows );
            fclose( $fh );
            ob_end_flush();
            exit;		
        }
    }
}

new Export_User_Data();
?>
