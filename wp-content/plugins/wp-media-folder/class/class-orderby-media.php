<?php
// Prohibit direct script loading
defined('ABSPATH') || die('No direct script access allowed!');
class Wpmf_Add_Columns_Media {

    function __construct() {
        add_filter('manage_media_columns', array($this, 'wpmf_manage_media_columns'));
        add_filter('manage_upload_sortable_columns', array($this, 'imwidth_column_register_sortable'));
        add_filter('manage_media_custom_column', array($this, 'wpmf_manage_media_custom_column'), 10, 2);
        add_action('pre_get_posts', array($this, 'wpmf_fillter'), 0, 1);
        add_filter('post_mime_types', array($this, 'modify_post_mime_types'));
    }

    /* Add file type to Filetype filter */
    function modify_post_mime_types($post_mime_types) {
        if (empty($post_mime_types['wpmf-pdf'])) {
            $post_mime_types['wpmf-pdf'] = array(__('PDF', 'wpmf'));
        }
        if (empty($post_mime_types['wpmf-zip'])) {
            $post_mime_types['wpmf-zip'] = array(__('Zip & archives', 'wpmf'));
        }

        $post_mime_types['wpmf-other'] = array(__('Other', 'wpmf'));
        return $post_mime_types;
    }
    
    /* Query post by file type */
    function wpmf_fillter($query) {
        if (!isset($query->query_vars['post_type']) || $query->query_vars['post_type'] != 'attachment')
            return;
        global $pagenow, $wpdb;

        $views = get_user_meta(get_current_user_id(), $wpdb->prefix . 'media_library_mode');
        if (!empty($views)) {
            $curent_view = $views[0];
        } else {
            $curent_view = 'grid';
        }

        if ($pagenow == 'upload.php') {
            if ($curent_view == 'list') {
                if (isset($_COOKIE["listwpmf_media_order"]) && empty($_GET['orderby']) && empty($_GET['order'])) {
                    $cook = explode('|', $_COOKIE["listwpmf_media_order"]);
                    $wpmf_allowed = array('name', 'author', 'date', 'title', 'modified', 'uploadedTo', 'id', 'post__in', 'menuOrder');
                    if ($cook) {
                        if ($cook[0] == 'size') {
                            $query->set('meta_key', 'wpmf_size');
                            $query->set('orderby', 'meta_value_num');
                            $query->set('order', $cook[1]);
                        } else if ($cook[0] == 'filetype') {
                            $query->set('meta_key', 'wpmf_filetype');
                            $query->set('orderby', 'meta_value');
                            $query->set('order', $cook[1]);
                        } else if (in_array($cook[0], $wpmf_allowed)) {
                            $query->set('orderby', $cook[0]);
                            $query->set('order', $cook[1]);
                        }
                    }
                } else if (isset($_GET['orderby'])) {
                    $orderby = $_GET['orderby'];

                    if ('size' == $orderby) {
                        $query->set('meta_key', 'wpmf_size');
                        $query->set('orderby', 'meta_value_num');
                    }

                    if ('filetype' == $orderby) {
                        $query->set('meta_key', 'wpmf_filetype');
                        $query->set('orderby', 'meta_value');
                    }
                }
            }
        } else {
            if (isset($_COOKIE["gridwpmf_media_order"]) && empty($_REQUEST['query']['meta_key'])) {
                $g_cook = explode('|', $_COOKIE["gridwpmf_media_order"]);
                if ($g_cook[0] == 'size') {
                    $query->query_vars['meta_key'] = 'wpmf_size';
                    $query->query_vars['orderby'] = 'meta_value_num';
                } else if ($g_cook[0] == 'filetype') {
                    $query->query_vars['meta_key'] = 'wpmf_filetype';
                    $query->query_vars['orderby'] = 'meta_value';
                } else {
                    if (isset($_REQUEST['query']['wpmf_orderby']) && isset($_REQUEST['query']['order'])) {
                        $query->query_vars['orderby'] = $_REQUEST['query']['wpmf_orderby'];
                        $query->query_vars['order'] = $_REQUEST['query']['order'];
                    }
                }
            } else {
                if (isset($_REQUEST['query']['meta_key']) && $_REQUEST['query']['wpmf_orderby']) {
                    $query->query_vars['meta_key'] = $_REQUEST['query']['meta_key'];
                    $query->query_vars['orderby'] = $_REQUEST['query']['wpmf_orderby'];
                }
            }
        }

        if (isset($_GET['attachment-filter'])) {
            $filetype_wpmf = $_GET['attachment-filter'];
        }
        if (isset($_REQUEST['query']['post_mime_type'])) {
            $filetype_wpmf = $_REQUEST['query']['post_mime_type'];
        }

        if (isset($filetype_wpmf)) {
            if ($filetype_wpmf == 'wpmf-pdf' || $filetype_wpmf == 'wpmf-zip' || $filetype_wpmf == 'wpmf-other') {
                $filetypes = explode('-', $filetype_wpmf);
                $filetype = $filetypes[1];
                if ($filetype == 'zip' || $filetype == 'pdf' || $filetype == 'other') {
                    $query->query_vars['post_mime_type'] = '';
                    $query->query_vars['meta_key'] = 'wpmf_filetype';
                    switch ($filetype) {
                        case 'pdf':
                            $query->query_vars['meta_value'] = 'pdf';
                            break;
                        case 'zip':
                            $query->query_vars['meta_value'] = array('zip', 'rar', 'ace', 'arj', 'bz2', 'cab', 'gzip', 'iso', 'jar', 'lzh', 'tar', 'uue', 'xz', 'z', '7-zip');
                            break;
                        case 'other':
                            $exts = array('jpg', 'jpeg', 'jpe', 'gif', 'png', 'bmp', 'tiff', 'tif', 'ico', 'asf', 'asx', 'wmv', 'wmx', 'wm', 'avi', 'divx',
                                'flv', 'mov', 'qt', 'mpeg', 'mpg', 'mpe', 'mp4', 'm4v', 'ogv', 'webm', 'mkv', '3gp', '3gpp', '3g2', '3gp2', 'txt', 'asc', 'c', 'cc', 'h',
                                'srt', 'csv', 'tsv', 'ics', 'rtx', 'css', 'html', 'htm', 'vtt', 'dfxp', 'mp3', 'm4a', 'm4b', 'ra', 'ram', 'wav', 'ogg', 'oga',
                                'mid', 'midi', 'wma', 'wax', 'mka', 'rtf', 'js', 'pdf', 'class', 'tar', 'zip', 'gz', 'gzip', 'rar', '7z', 'psd', 'xcf', 'doc',
                                'pot', 'pps', 'ppt', 'wri', 'xla', 'xls', 'xlt', 'xlw', 'mdp', 'mpp', 'docx', 'docm', 'dotx', 'xlsx', 'xlsm', 'xlsb', 'xltx', 'xltm', 'xlam',
                                'pptx', 'pptm', 'ppsx', 'ppsm', 'potx', 'potm', 'ppam', 'sldx', 'sldm', 'onetoc', 'onetoc2', 'onetmp', 'onepkg', 'oxps', 'xps', 'odt', 'odp', 'ods', 'odg',
                                'odc', 'odb', 'odf', 'wp', 'wpd', 'key', 'numbers', 'pages'
                            );
                            $other = array_diff($exts, array("zip", "rar", "ace", "arj", "bz2", "cab", "gzip", "iso", "jar", "lzh", "tar", "uue", "xz", "z", "7-zip", "pdf", "mp3", "mp4", "jpg", "png", "gif", "bmp", "svg"));
                            if (empty($other)) {
                                $other = 'wpmf_none';
                            }
                            $query->query_vars['meta_value'] = $other;
                            break;
                    }
                }
            }
        }
    }

    /* Add size column and filetype column */
    public static function wpmf_manage_media_columns($columns) {
        $columns['wpmf_size'] = __('Size', 'wpmf');
        $columns['wpmf_filetype'] = __('File type', 'wpmf');
        return $columns;
    }

    function imwidth_column_register_sortable($columns) {
        $columns['wpmf_size'] = 'size';
        $columns['wpmf_filetype'] = 'filetype';
        return $columns;
    }

    /* get size and filetype of attachment */
    public function wpmf_get_size_filetype($pid) {
        $wpmf_size_filetype = array();
        $meta = get_post_meta($pid, '_wp_attached_file');
        $upload_dir = wp_upload_dir();
        // get path file
        $path_attachment = $upload_dir['basedir'] . '/' . $meta[0];
        if (file_exists($path_attachment)) {
            // get size
            $size = filesize($path_attachment);
            // get file type
            $filetype = wp_check_filetype($path_attachment);
            $ext = $filetype['ext'];
        } else {
            $size = 0;
            $ext = '';
        }
        $wpmf_size_filetype['size'] = $size;
        $wpmf_size_filetype['ext'] = $ext;

        return $wpmf_size_filetype;
    }
    
    /* get size and filetype of attachment */
    public function wpmf_manage_media_custom_column($column_name, $id) {
        $wpmf_size_filetype = $this->wpmf_get_size_filetype($id);
        $size = $wpmf_size_filetype['size'];
        $ext = $wpmf_size_filetype['ext'];
        if ($size < 1024 * 1024) {
            $size = round($size / 1024, 1) . ' kB';
        } else if ($size > 1024 * 1024) {
            $size = round($size / (1024 * 1024), 1) . ' MB';
        }

        switch ($column_name) {
            case 'wpmf_size':
                echo $size;
                break;

            case 'wpmf_filetype':
                echo $ext;
                break;
        }
    }

}
