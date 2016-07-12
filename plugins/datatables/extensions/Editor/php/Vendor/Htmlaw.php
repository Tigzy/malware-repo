<?php
/**
 * HtmLawed is used here to provide protection against XSS attacks with Editor
 * input - see the `Field->xss()` method. The Vanilla forums wrapper is used
 * to provide sensible defaults and a clean interface for HtmLawed.
 * 
 * Changes:
 * 
 *  * Add `DataTables/Vendor` namespace to this and htmLawed - this is to ensure
 *    that if htmLawed is included by any other aspect of the site it will not
 *    result in a conflict.
 *  * Use the OOP version of htmLawed (required a single updated to call it) to
 *    make the namespacing relatively easy.
 *  * Change the name of the Vanilla class so it don't conflict with the
 *    htmLawed OOP class
 *  * Update all `htmLawed::` references to `\DataTables\Vendor\htmLawed::` in
 *    the htmLawed file (to allow callbacks to operate correctly)
 *  * Updated Vanilla wrapper to operate on PHP 5.3
 * 
 * HtmLawed:
 *   http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/
 *   Copyright: Santosh Patnaik
 *   License: Dual licensed with LGPL 3 and GPL 2+
 *
 * Vanilla wrapper for HtmLawed:
 *   https://github.com/vanilla/htmlawed/
 *   Author: Todd Burry <todd@vanillaforums.com>
 *   Copyright: 2009-2014 Vanilla Forums Inc.
 *   License: LGPL-3.0
 */

namespace DataTables\Vendor;

/**
 * A class wrapper for the htmLawed library.
 */
class Htmlaw {
    /// Methods ///

    public static $defaultConfig = array(
        'anti_link_spam' => array('`.`', ''),
        'comment' => 1,
        'cdata' => 3,
        'css_expression' => 1,
        'deny_attribute' => 'on*',
        'unique_ids' => 0,
        'elements' => '*-applet-form-input-textarea-iframe-script-style-embed-object',
        'keep_bad' => 1,
        'schemes' => 'classid:clsid; href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; style: nil; *:file, http, https', // clsid allowed in class
        'valid_xhtml' => 0,
        'direct_list_nest' => 1,
        'balance' => 1
    );

    public static $defaultSpec = array(
        'object=-classid-type, -codebase',
        'embed=type(oneof=application/x-shockwave-flash)'
    );

    /**
     * Filters a string of html with the htmLawed library.
     *
     * @param string $html The text to filter.
     * @param array|null $config Config settings for the array.
     * @param string|array|null $spec A specification to further limit the allowed attribute values in the html.
     * @return string Returns the filtered html.
     * @see http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/htmLawed_README.htm
     */
    public static function filter($html, array $config = null, $spec = null) {
        require_once __DIR__.'/htmLawed/htmLawed.php';

        if ($config === null) {
            $config = self::$defaultConfig;
        }

        if (isset($config['spec']) && !$spec) {
            $spec = $config['spec'];
        }

        if ($spec === null) {
            $spec = static::$defaultSpec;
        }

        return htmLawed::hl($html, $config, $spec);
    }


    /**
     * Filter a string of html so that it can be put into an rss feed.
     *
     * @param $html The html text to fitlter.
     * @return string Returns the filtered html.
     * @see Htmlawed::filter().
     */
    public static function filterRSS($html) {
        $config = array(
            'anti_link_spam' => array('`.`', ''),
            'comment' => 1,
            'cdata' => 3,
            'css_expression' => 1,
            'deny_attribute' => 'on*,style,class',
            'elements' => '*-applet-form-input-textarea-iframe-script-style-object-embed-comment-link-listing-meta-noscript-plaintext-xmp',
            'keep_bad' => 0,
            'schemes' => 'classid:clsid; href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; style: nil; *:file, http, https', // clsid allowed in class
            'valid_xml' => 2,
            'balance' => 1
        );
        $spec = static::$defaultSpec;

        $result = static::filter($html, $config, $spec);

        return $result;
    }
}
