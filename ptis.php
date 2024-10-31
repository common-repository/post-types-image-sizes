<?php

/*
Plugin Name: Post Types Image Sizes
Plugin URI: http://parsa.ws
Description: Specify the image size for each type of post
Author: Parsa Kafi
Version: 1.2
Author URI: http://parsa.ws
Text Domain: post-types-image-sizes
Domain Path: languages
*/


class PTIS_Plugin
{
    protected $options_key = "post-types-image-sizes";
    protected $text_domain = "post-types-image-sizes";
    protected $ignore_post_types = array("attachment", "revision", "nav_menu_item");
    protected $default_post_type = 'default_ptis';

    function __construct()
    {
        $string = __('Thumbnail', $this->text_domain) . __('Medium', $this->text_domain) . __('Medium Large', $this->text_domain) . __('Large', $this->text_domain);
        add_action('admin_init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('activated_plugin', array($this, 'activation_redirect'));
        add_filter('intermediate_image_sizes', array($this, 'image_sizes'), 1);
        //add_filter('intermediate_image_sizes_advanced', array($this, 'image_sizes'), 1);
    }

    function activation_redirect($plugin)
    {
        if ($plugin == plugin_basename(__FILE__)) {
            exit(wp_redirect(admin_url('options-general.php?page=post-types-image-sizes')));
        }
    }

    function add_menu()
    {
        load_plugin_textdomain($this->text_domain, false, dirname(plugin_basename(__FILE__)) . '/languages/');
        add_options_page(__('Post Types Image Sizes', $this->text_domain), __('Post Types Image Sizes', $this->text_domain), 'manage_options', 'post-types-image-sizes', array($this, 'settings_page'));
    }

    function init()
    {
        register_post_type($this->default_post_type,
            array(
                'labels' => array(
                    'name' => __('Default')
                ),
                'public' => false,
                'has_archive' => false,
                'supports' => array('thumbnail')
            )
        );
    }

    function clean_name($name)
    {
        return preg_replace("/[^a-z0-9]/", " ", strtolower($name));
    }

    function image_sizes($image_sizes)
    {
        global $post;
        $post_id = isset($post->id) ? $post->id : (isset($_REQUEST['post_id']) ? $_REQUEST['post_id'] : 0);
        $opt = get_option($this->options_key);
        if ($post_id) {
            $post_type = get_post_type($post_id);
            if (!in_array($post_type, $this->ignore_post_types)) {
                if (in_array($post_type, array_keys($opt['ptype'])) && isset($opt['ptype_image'][$post_type])) {
                    return array_keys($opt['ptype_image'][$post_type]);
                }
                return array();
            }
        } else {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ptis-submit']) && is_array($opt['ptype']) && in_array($this->default_post_type, array_keys($opt['ptype'])) && isset($opt['ptype_image'][$this->default_post_type])) {
                return array_keys($opt['ptype_image'][$this->default_post_type]);
            }
        }

        return $image_sizes;
    }

    function settings_page()
    {
        if (isset($_POST['ptis-submit'])) {
            $arr = array('ptype' => $_POST['ptype'], 'ptype_image' => $_POST['ptype_image']);
            update_option($this->options_key, $arr);
        }
        $post_types = get_post_types("", "objects");
        $opt = get_option($this->options_key);
        $image_sizes = $this->get_image_sizes();
        ?>
        <style>
            .ptis_wrap table {
                width: 100%;
                margin: 20px 0;
            }

            .ptis_wrap tr td {
                padding-bottom: 10px;
            }

            .ptis_wrap tr td:first-child {
                width: 200px;
                vertical-align: top;
            }

            .ptis_wrap .image_size {
                margin-left: 25px;
            }

            body.rtl .ptis_wrap .image_size {
                margin-right: 25px;
            }
        </style>
        <script>
            jQuery(function () {
                jQuery(".post_type_chk").change(function () {
                    if (this.checked) {
                        jQuery(this).closest("div").find(".image_size input").removeAttr("disabled");
                    } else {
                        jQuery(this).closest("div").find(".image_size input").attr("disabled", true);
                    }
                });

                jQuery('input[type=checkbox].post_type_chk').each(function () {
                    if (this.checked) {
                        jQuery(this).closest("div").find(".image_size input").removeAttr("disabled");
                    } else {
                        jQuery(this).closest("div").find(".image_size input").attr("disabled", true);
                    }
                });
            });
        </script>
        <div class="wrap ptis_wrap">
            <h1><?php _e("Post Types Image Sizes", $this->text_domain) ?></h1>
            <form action="" method="post">
                <table border="0">
                    <tr>
                        <td><?php _e("Post Types:", $this->text_domain) ?></td>
                        <td>
                            <?php
                            $c = 0;
                            $temp = $post_types['post'];
                            foreach ($post_types as $post_type) {
                                if ($c == 0) {
                                    $temp = $post_type;
                                    $post_type = $post_types[$this->default_post_type];
                                } elseif ($post_type->name == $this->default_post_type)
                                    $post_type = $temp;

                                if (post_type_supports($post_type->name, 'thumbnail') && !in_array($post_type->name, $this->ignore_post_types)) {
                                    echo '<div><label><input type="checkbox" name="ptype[' . $post_type->name . ']" value="1" ' . checked(1, isset($opt['ptype'][$post_type->name]) ? $opt['ptype'][$post_type->name] : 0, false) . ' class="post_type_chk"/> ' . $post_type->label . '</label></br>
								<div class="image_size">';
                                    foreach ($image_sizes as $k => $v) {
                                        echo '<label><input type="checkbox" name="ptype_image[' . $post_type->name . '][' . $k . ']" value="1" ' . checked(1, isset($opt['ptype_image'][$post_type->name][$k]) ? $opt['ptype_image'][$post_type->name][$k] : 0, false) . '/> ' . __(ucwords($this->clean_name($k)), $this->text_domain) . '</label> (' . $v['width'] . '×' . $v['height'] . ($v['crop'] ? __(', Crop', $this->text_domain) : '') . ')</br>';
                                    }
                                    echo '</div>
								</div>';
                                }
                                $c++;
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><input type="submit" name="ptis-submit" class="button button-primary"
                                   value="<?php _e('Save') ?>"></td>
                    </tr>
                </table>
            </form>
        </div>
        <?php
    }

    /** https://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes
     * Get size information for all currently-registered image sizes.
     *
     * @global $_wp_additional_image_sizes
     * @uses   get_intermediate_image_sizes()
     * @return array $sizes Data for all currently-registered image sizes.
     */
    function get_image_sizes()
    {
        global $_wp_additional_image_sizes;

        $sizes = array();

        foreach (get_intermediate_image_sizes() as $_size) {
            if (in_array($_size, array('thumbnail', 'medium', 'medium_large', 'large'))) {
                $sizes[$_size]['width'] = get_option("{$_size}_size_w");
                $sizes[$_size]['height'] = get_option("{$_size}_size_h");
                $sizes[$_size]['crop'] = (bool)get_option("{$_size}_crop");
            } elseif (isset($_wp_additional_image_sizes[$_size])) {
                $sizes[$_size] = array(
                    'width' => $_wp_additional_image_sizes[$_size]['width'],
                    'height' => $_wp_additional_image_sizes[$_size]['height'],
                    'crop' => $_wp_additional_image_sizes[$_size]['crop'],
                );
            }
        }

        return $sizes;
    }
}


$ptts_plugin = new PTIS_Plugin();