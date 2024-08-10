<?php
namespace AcmWp;

use AcmWp\Theme\Templates;
use Carbon\Carbon;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles as CssInliner;


if (! defined('ABSPATH')) exit;

Class Email {


    protected $subject;
    protected $content;
    protected $template;
    protected $data;
    protected $to;
    protected $from_name;
    protected $from_email;
    protected $headers = [];
    protected $attachments = [];


    public function __construct($args = []) {

        foreach ($args as $key => $arg) {
            if (property_exists($this, $key)) {
                $this->{$key} = $arg;
            }
        }

        if ($this->template) {
            $this->loadTemplate();
        }
    }


    public function send() {
        if (WP_DEBUG) {
            //$this->to = 'acmoore.tests@gmail.com';
        }

        add_filter('wp_mail_content_type', [$this, 'setHtmlContentType']);
        wp_mail($this->to, $this->subject, $this->content, $this->getHeaders(), $this->attachments);
        remove_filter('wp_mail_content_type', [$this, 'setHtmlContentType']);
    }


    public function setHtmlContentType() {
        return "text/html";
    }


    public function getFromName() {
        if (!$this->from_name) {
            $this->from_name = get_bloginfo('name');
        }

        return $this->from_name;
    }


    public function getFromEmail() {
        if (!$this->from_email) {
            $this->from_email = get_bloginfo('admin_email');
        }

        return $this->from_email;
    }


    public function getHeaders() {
        if (!$this->headers) {
            $this->headers  = "From: {$this->getFromName()} <{$this->getFromEmail()}>\r\n";
            $this->headers .= "Reply-To: {$this->getFromEmail()}\r\n";
            $this->headers .= "Content-Type: 'text/html'; charset=utf-8\r\n";
        }

        return $this->headers;
    }

    private function loadTemplate() {
        global $post;

        $template = acm()->email->getBySlug($this->template);

        if (!$template || $template->post_type !== 'email') {
            throw new \Exception('Email template not found: '.$this->template);
        }

        $post = $template;
        setup_postdata($post);
        $content = get_the_content();
        $content = make_clickable($content);
        $content = wpautop($content);
        $this->content = $content;
        $this->subject = get_the_title();
        wp_reset_postdata();

        $this->content = $this->mergeTokens($this->content, $this->data);
        $this->subject = $this->mergeTokens($this->subject, $this->data);


        $inliner = new CssInliner();
        $css  = file_get_contents(get_stylesheet_directory() .'/css/email.css');
        $html = Templates::loadTemplatePart('email', null, ['content' => $this->content]);

        $this->content = $inliner->convert($html, $css);
    }


    private function mergeTokens($content, $data) {

        $data_array = [];
        foreach ($data as $key2 => $object) {
            if (is_object($object)) {
                $data_array[$key2] = (array) $object;
            } else {
                $data_array[$key2] = $object;
            }
        }

        $tokens = [];
        preg_match_all('/%([a-z0-9\._]+) ?\|? ?([a-z]+)?%/i', $content, $tokens);

        if (count($tokens) < 2) {
            return $content;
        }

        $chunks  = array_get($tokens, 0);
        $formats = array_get($tokens, 2);
        $tokens  = array_get($tokens, 1);

        foreach ($tokens as $i => $field) {
            if (strtolower($field) == 'date') {
                $value = Carbon::now()->format('d/m/Y');
            } else {
                $value = array_get($data_array, $field, '');
            }

            // Do we have a formatter?
            $format = ucfirst(array_get($formats, $i));
            if ($format && method_exists($this, '_mergeFormat'.$format)) {
                $value = $this->{'_mergeFormat'.$format}($value);
            }

            $content = str_ireplace($chunks[$i], $value, $content);
        }


        return $content;
    }

    private function _mergeFormatDate($value) {
        try {
            $value = Carbon::parse($value);
        } catch (Exception $e) {
            return $value;
        }

        return $value->format('d/m/Y');
    }
    private function _mergeFormatDatetime($value) {
        try {
            $value = Carbon::parse($value);
        } catch (Exception $e) {
            return $value;
        }

        return $value->format('d/m/Y H:i');
    }
    private function _mergeFormatTime($value) {
        try {
            $value = Carbon::parse($value);
        } catch (Exception $e) {
            return $value;
        }

        return $value->format('H:i');
    }
    private function _mergeFormatBoolean($value) {
        return ($value ? 'Yes' : 'No');
    }
    private function _mergeFormatCurrency($value) {
        if (!is_numeric($value)) {
            return $value;
        }
        return '£'.number_format($value, 2);
    }

}