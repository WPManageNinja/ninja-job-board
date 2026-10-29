<?php
/**
 * Email Footer
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$wpjobboard_footerText = apply_filters('wpjobboard/email_template_footer_text','&copy; '.get_bloginfo( 'name', 'display' ).'.', $submission, $notification);;
$wpjobboard_poweredBy = apply_filters('wpjobboard/email_poweredby', 'NinjaJobBoard plugin made by <a href="https://wpmanageninja.com">wpmanageninja.com</a>');
?>
</div></td></tr></table></td></tr></table></td></tr></table>
<table border="0" cellpadding="10" cellspacing="0" width="600" id="template_footer"><tr><td valign="top"><table border="0" cellpadding="10" cellspacing="0" width="100%"><tr><td class="fluent_credit" colspan="2" valign="middle" id="credit">
<span><?php echo wp_kses_post($wpjobboard_footerText); ?></span>
<span><?php echo wp_kses_post($wpjobboard_poweredBy); ?></span>
<?php do_action( 'wpjobboard/email_template_after_footer', $submission, $notification );?>
</td></tr></table></td></tr></table></td></tr></table></div></body></html>
