<?php

namespace WPJobBoard\Classes\Entry;


use WPJobBoard\Classes\Models\Forms;

if (!defined('ABSPATH')) {
    exit;
}

class MetaData
{
    protected $entry;
    protected $postId;
    protected $userId;
    protected $queryVars = null;
    protected $post;
    protected $user;

    public function __construct($entry)
    {
        $this->entry = $entry;
        $this->postId = self::resolvePostId($entry->getRawInput('__wpjb_current_page_id'), $entry->form_id);
        $this->userId = $entry->user_id;
    }

    /**
     * The visitor sends the page ID, so accept it only for a public page that embeds this form.
     * Otherwise use the job, so {wp:*} and {post_meta:*} can not read another post.
     */
    public static function resolvePostId($pageId, $formId)
    {
        $pageId = absint($pageId);
        $formId = absint($formId);
        if (!$pageId || $pageId === $formId) {
            return $formId;
        }

        $page = get_post($pageId);
        if (!$page || !is_post_publicly_viewable($page) || post_password_required($page)) {
            return $formId;
        }

        return self::embedsForm($page->post_content, $formId) ? $pageId : $formId;
    }

    private static function embedsForm($content, $formId)
    {
        if (preg_match_all('/' . get_shortcode_regex(array('wp_job_form')) . '/', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $atts = shortcode_parse_atts($match[3]);
                if (is_array($atts) && isset($atts['id']) && absint($atts['id']) === $formId) {
                    return true;
                }
            }
        }

        if (preg_match_all('/<!--\s+wp:ninja-job-board\/application-form\s+(\{.*?\})\s+\/?-->/', $content, $matches)) {
            foreach ($matches[1] as $json) {
                $attrs = json_decode($json, true);
                if (is_array($attrs) && isset($attrs['jobId']) && absint($attrs['jobId']) === $formId) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getWPValues($key)
    {
        switch ($key) {
            case 'post_id':
                return $this->postId;
            case 'post_title':
                return get_the_title($this->postId);
            case 'post_url':
                return get_the_permalink($this->postId);
            case 'post_author':
                $post = $this->getPost();
                if (!$post) {
                    return '';
                }
                return get_the_author_meta('display_name', $post->post_author);
            case 'post_author_email':
                $post = $this->getPost();
                return get_the_author_meta('user_email', $post->post_author);
            case 'user_id':
                return $this->userId;
            case 'user_first_name':
                $user = $this->getUser();
                if (!$user) {
                    return '';
                }
                return $user->user_firstname;
            case 'user_last_name':
                $user = $this->getUser();
                if (!$user) {
                    return '';
                }
                return $user->user_lastname;
            case 'user_display_name':
                $user = $this->getUser();
                if (!$user) {
                    return '';
                }
                return $user->display_name;
            case 'user_email':
                $user = $this->getUser();
                if (!$user) {
                    return '';
                }
                return $user->user_email;
            case 'user_url':
                $user = $this->getUser();
                if (!$user) {
                    return '';
                }
                return $user->user_url;
            case 'site_title':
                return get_bloginfo('name');
            case 'site_url':
                return get_bloginfo('url');
            case 'admin_email':
                return get_bloginfo('admin_email');
            default:
                return '';
                break;
        }
    }

    /**
     * User fields that placeholders can read. Never the password hash or activation key.
     */
    public static function allowedUserFields()
    {
        return apply_filters('wpjobboard/placeholder_user_fields', array(
            'ID', 'user_login', 'user_nicename', 'user_email', 'user_url', 'user_registered',
            'display_name', 'first_name', 'last_name', 'nickname', 'description'
        ));
    }

    /**
     * Placeholders can not read protected ("_" prefixed) or sensitive meta. $type: post|user
     */
    public static function isAllowedMetaKey($key, $type)
    {
        global $wpdb;
        $key = (string)$key;
        if ($key === '' || is_protected_meta($key, $type)) {
            return false;
        }

        $blocked = array('session_tokens', 'user_pass', 'user_activation_key', 'wp_capabilities', 'wp_user_level');
        if (in_array($key, $blocked, true) || strpos($key, $wpdb->prefix) === 0 || stripos($key, 'pass') !== false || stripos($key, 'token') !== false || stripos($key, 'secret') !== false) {
            return false;
        }

        return (bool)apply_filters('wpjobboard/placeholder_meta_key_allowed', true, $key, $type);
    }

    public function getPostMeta($key)
    {
        if (!self::isAllowedMetaKey($key, 'post')) {
            return '';
        }
        $meta = get_post_meta($this->postId, $key, true);
        if(is_array($meta)) {
            return implode(', ', $meta);
        }
        return $meta;
    }

    public function getuserMeta($key)
    {
        if (!self::isAllowedMetaKey($key, 'user')) {
            return '';
        }
        $meta = get_user_meta($this->userId, $key, true);
        if(is_array($meta)) {
            return implode(', ', $meta);
        }
        return $meta;
    }

    public function getFromUrlQuery($key)
    {
        if($this->queryVars == null) {
            $this->queryVars = array();
            $submissionUrl = $this->entry->getRawInput('__wpjb_current_url');
            $parts = wp_parse_url((string)$submissionUrl);
            if (isset($parts['query'])) {
                parse_str($parts['query'], $query);
                $this->queryVars = $query;
            }
        }

        if (isset($this->queryVars[$key]) && is_string($this->queryVars[$key])) {
            return sanitize_text_field($this->queryVars[$key]);
        }
        return '';
    }

    public function getOtherData($key)
    {
        if($key == 'date') {
            $dateFormat = get_option('date_format');
            return wp_date($dateFormat);
        }

        if($key == 'time') {
            $dateFormat = get_option('time_format');
            return wp_date($dateFormat);
        }
        if($key == 'user_ip') {
            return $this->entry->ip_address;
        }

        return '';
    }

    protected function getPost()
    {
        if ($this->post) {
            return $this->post;
        }
        $this->post = get_post($this->postId);
        return $this->post;
    }

    protected function getUser()
    {
        if ($this->user) {
            return $this->user;
        }
        $this->user = get_user_by('ID', $this->userId);
        return $this->user;
    }
}