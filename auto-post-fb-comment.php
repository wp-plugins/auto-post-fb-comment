<?php

/*
 * Plugin Name: Auto Post FB Comment
 * Plugin URI: http://baicadicungnamthang.net
 * Description: This plugin auto post a Facebook comment to Wordpress when a user comment in your page
 * Version: 1.0.0
 * Author: Nguyen Trong Bang
 * Author URI: http://baicadicungnamthang.net
 * License: GPL2
 *
 * Copyright 2015  Nguyen Trong Bang ( email : nguyentrongbang9x@gmail.com )
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 */

if (!class_exists("Auto_Post_FB_Comment")) {

    require_once( plugin_dir_path(__FILE__) . 'admin.php' );

    class Auto_Post_FB_Comment {

        private $app_id;
        private $approved;
        private $debug;
        private $prefix;
        private $width;
        private $numposts;
        private $colorscheme;

        public function __construct() {
            add_action("wp_ajax_auto_post_fb_comment", array(&$this, "auto_post_fb_comment"));
            add_action("wp_ajax_nopriv_auto_post_fb_comment", array(&$this, "auto_post_fb_comment"));
            add_filter('comments_array', array(&$this, "filter_comment"));
            $this->prefix = "apfc";
            $this->app_id = get_option($this->prefix . "_app_id");
            $this->approved = get_option($this->prefix . "_approved", 1);
            $this->debug = get_option($this->prefix . "_debug", 0);
            $this->width = get_option($this->prefix . "_width", 550);
            $this->numposts = get_option($this->prefix . "_numposts", 5);
            $this->colorscheme = get_option($this->prefix . "_colorscheme", "light");
        }

        public function auto_post_fb_comment() {
            session_start();
            if (isset($_POST["post_id"])) {
                $post_id = intval(sanitize_text_field($_POST["post_id"]));
                if (!get_post_status($post_id)) {
                    if ($this->debug) {
                        die("Post $post_id does not exist");
                    }
                }
                $token_name = $this->getTokenName($post_id);
                if (isset($_POST[$token_name]) && isset($_SESSION[$token_name]) && $_POST[$token_name] == $_SESSION[$token_name] && isset($_POST["comment_author"]) && isset($_POST["comment_content"])) {
                    $comment_author = sanitize_text_field($_POST["comment_author"]);
                    $comment_content = sanitize_text_field($_POST["comment_content"]);
                    $comment_author_email = isset($_POST["comment_author_email"]) ? sanitize_email(($_POST["comment_author_email"])) : "";
                    $comment_author_url = isset($_POST["comment_author_url"]) ? esc_url_raw($_POST["comment_author_url"]) : "";

                    $comment_id = $this->post_fb_comment($post_id, $comment_author, $comment_content, $comment_author_email, $comment_author_url);

                    if ($this->debug) {
                        if ($comment_id) {
                            echo "Post comment success. Comment ID : $comment_id";
                        } else {
                            echo "Post comment failed";
                        }
                    }
                } else {
                    if ($this->debug) {
                        die("Token missed match or missed POST variable");
                    }
                }
            } else {
                if ($this->debug) {
                    die("Missing post_id");
                }
            }
        }

        public function post_fb_comment($comment_post_ID, $comment_author, $comment_content, $comment_author_email, $comment_author_url) {
            $time = current_time('mysql');
            $comment = array(
                'comment_post_ID' => $comment_post_ID,
                'comment_author' => $comment_author,
                'comment_author_email' => $comment_author_email,
                'comment_author_url' => $comment_author_url,
                'comment_author_IP' => sanitize_text_field($_SERVER['REMOTE_ADDR']),
                'comment_date' => $time,
                'comment_content' => $comment_content,
                'comment_karma' => '',
                'comment_approved' => $this->approved,
                'comment_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT']),
                'comment_type' => 'comment',
                'user_id' => 0
            );

            $comment_id = wp_insert_comment($comment);

            return $comment_id;
        }

        public function filter_comment($comments = '') {
            global $post;
            $post_id = $post->ID;

            if (!comments_open()) {
                return $comments;
            }

            if ('publish' != get_post_status($post_id)) {
                return $comments;
            }

            $this->loadJS();

            $this->loadHTML();

            return $comments;
        }

        public function loadJS() {
            global $post;
            $post_id = $post->ID;
            $debug = ($this->debug) ? 'console.log(response);' : '';
            echo '<script>
                        window.fbAsyncInit = function () {
                            FB.init({
                                appId: ' . $this->app_id . ',
                                cookie: true, // enable cookies to allow the server to access 
                                // the session
                                xfbml: true, // parse social plugins on this page
                                version: \'v2.2\' // use version 2.2
                            });

                            FB.Event.subscribe(\'comment.create\', comment_callback);
                        };

                        var comment_callback = function (response) {
                            FB.api(response.commentID, function (comment) {
                                FB.api(comment.from.id, function (user) {
                                    var data = {
                                        "action": "auto_post_fb_comment",
                                        "post_id" : ' . $post_id . ',
                                        "' . $this->getTokenName($post_id) . '" : "' . $this->getTokenValue($post_id) . '",
                                        "comment_author": comment.from.name,
                                        "comment_content": comment.message,
                                        "comment_author_email": user.email,
                                        "comment_author_url": user.link
                                    };
                                    $.post(\'/wp-admin/admin-ajax.php\', data, function (response) {'
                                        . $debug . '
                                    });
                                });
                            });
                        }
                    </script>';
        }

        private function getTokenName($post_id) {
            $token_name = "apfc_token_" . $post_id;

            return $token_name;
        }

        private function getTokenValue($post_id) {
            session_start();
            $token = md5("apfc" . time() . uniqid() . rand(100000, 999999));
            $token_name = $this->getTokenName($post_id);
            $_SESSION[$token_name] = $token;

            return $token;
        }

        public function loadHTML() {
            global $post;
            echo '<div
                        class="fb-comments"
                        data-href="' . get_permalink($post->ID) . '"
                        data-width="' . $this->width . '"
                        data-numposts="' . $this->numposts . '"
                        data-colorscheme="' . $this->colorscheme . '">
                    </div>';
        }

    }

    new Auto_Post_FB_Comment();
}

