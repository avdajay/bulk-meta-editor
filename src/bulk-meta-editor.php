<?php

namespace BulkMetaEditor;

use BulkMetaEditor\Notices;

class BulkMetaEditor
{   
    public function __construct()
    {
        register_activation_hook(BME_MAIN_FILE, [$this, 'activate']);
        register_deactivation_hook(BME_MAIN_FILE, [$this, 'deactivate']);
        add_action('admin_menu', [$this, 'createAdminMenu']);
        add_action('admin_post_arva_submit', [$this, 'processBulkData']);
        
        if(isset($_GET['message'])) {
            add_action('admin_notices', function() {
                Notices::get();
            });
        }
    }

    public function loadMenuPageView()
    {
        require_once BME_PLUGIN_PATH . 'views/admin-settings.php';
    }

    public function createAdminMenu()
    {
        add_menu_page(
            'Bulk Meta Editor', 
            'Bulk Meta Editor', 
            'manage_options', 
            'bulk-meta-editor',
            [$this, 'loadMenuPageView'],
            'dashicons-edit-large'
        );
    }

    public function isYoastActive()
    {
        $yoast_variants = [
            'Yoast SEO'         => 'wordpress-seo/wp-seo.php', 
            'Yoast SEO Premium' => 'wordpress-seo-premium/wp-seo-premium.php',
        ];

        foreach($yoast_variants as $value) {

            if(!in_array($value, apply_filters('active_plugins', get_option('active_plugins'))) ) {             
                return false;
            }

            return true;
        }
    }

    public function isProVersionActive()
    {
        $pro_version = 'bulk-meta-editor-pro/bulk-meta-editor-pro.php';
        
        if(is_plugin_active($pro_version)) {
            return true;
        }

        return false;
    }

    public function getPostId($url)
    {
        $post_id = url_to_postid($url);

        return $post_id;
    }

    public function sanitizeUrl($url)
    {
        $sanitized_url = esc_url_raw($url);

        return $sanitized_url;
    }

    public function sanitizeText($text)
    {
        $sanitized_text = sanitize_text_field($text);

        return $sanitized_text;
    }

    public function activate()
    {
        $pro_version_active = $this->isProVersionActive();
        
        if($pro_version_active) {
            deactivate_plugins('bulk-meta-editor-pro/bulk-meta-editor-pro.php', true);
        }
    }

    public function deactivate()
    {
        delete_option('arva_bme_notices');
    }

    public function redirect()
    {
        wp_redirect(admin_url('admin.php?page=bulk-meta-editor&message=1'));
        exit;
    }

    public function processBulkData()
    {
        if(current_user_can('manage_options')) {

            if( !empty($_FILES['file_upload']['tmp_name']) ) {
            
                $handle  = fopen($_FILES['file_upload']['tmp_name'], "r");
                $headers = fgetcsv($handle);
                
                while (($data = fgetcsv($handle)) !== FALSE) {

                    $post_id = $this->getPostId($this->sanitizeUrl($data[0]));
                    
                    // Checks if there is a value in the Meta Title colum of the CSV, then updates the key with the new data.
                    if(!empty($data[1])) {
                        update_post_meta($post_id, '_yoast_wpseo_title', $this->sanitizeText($data[1]));
                    }

                    // Checks if there is a value in the Meta Description column of the CSV, then updates the key with the new data.
                    if(!empty($data[2])) {
                        update_post_meta($post_id, '_yoast_wpseo_metadesc', $this->sanitizeText($data[2]));
                    }

                    // Checks if there is a value in the Canonical URL column of the CSV, then updates the key with the new data.
                    if(!empty($data[3])) {
                        update_post_meta($post_id, '_yoast_wpseo_canonical', $this->sanitizeText($data[3]));
                    }

                    // Checks if there is a value in the Noindex column of the CSV, then updates the key with the new data.
                    if(!empty($data[4])) {
                        update_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', $this->sanitizeText($data[4]));
                    }

                    if(!empty($data[5])) {
                        update_post_meta($post_id, '_yoast_wpseo_meta-robots-nofollow', $this->sanitizeText($data[5]));
                    }

                }
    
                fclose($handle);
    
                Notices::set('Metadata Updated', 'notice-success');
                $this->redirect();

            }
    
            Notices::set('No File Attached', 'notice-error');
            $this->redirect();

        } else {

            Notices::set('Action not permitted', 'notice-error', false);
            $this->redirect();

        }
    }
}