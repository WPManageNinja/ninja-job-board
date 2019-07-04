<?php

namespace WPJobBoard\Classes\DefaultValueParser;

use WPPayForm\Classes\Browser;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Entry MetaDat
 * @since 1.0.0
 */
class GlobalMetaData
{
    protected $postId;
    protected $userId;
    protected $queryVars = null;
    protected $post;
    protected $user;

    public function __construct()
    {
        $this->postId = get_the_ID();
        $this->userId = get_current_user_id();
        $this->queryVars = $_REQUEST;
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
            case 'current_user_role':
                $user = $this->getUser();
                if(!$user) {
                    return '';
                }
                if($user->roles) {
                    return implode(', ', $user->roles);
                }
                return '';
            default:
                return '';
                break;
        }
    }

    public function getPostMeta($key)
    {
        $meta = get_post_meta($this->postId, $key, true);
        if (is_array($meta)) {
            return implode(', ', $meta);
        }
        return $meta;
    }

    public function getuserData($key)
    {
        $user = $this->getUser();
        if(!$user) {
            return '';
        }
        if($user->exists($key)) {
            $value = $user->{$key};
            if(is_array($value)) {
                $value = implode(', ', $value);
            }
            return $value;
        }
        return '';
    }

    public function getuserMeta($key)
    {
        $meta = get_user_meta($this->userId, $key, true);
        if (is_array($meta)) {
            return implode(', ', $meta);
        }
        return $meta;
    }

    public function getFromUrlQuery($key)
    {
        if (isset($this->queryVars[$key])) {
            return esc_attr($this->queryVars[$key]);
        }
        return '';
    }

    public function getOtherData($key)
    {
        if ($key == 'date') {
            $dateFormat = get_option('date_format');
            return gmdate($dateFormat, time());
        }

        if ($key == 'time') {
            $dateFormat = get_option('time_format');
            return gmdate($dateFormat, time());
        }
        if ($key == 'user_ip') {
            $browser = new Browser();
            return $browser->getIp();
        }

        if ($key == 'browser_name') {
            $browser = new Browser();
            return $browser->getBrowser();
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
        $user = wp_get_current_user();
        if($user->exists()) {
            $this->user  = $user;
        }
        return $this->user;
    }
}