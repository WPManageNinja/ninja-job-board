<?php

namespace WPJobBoard\Classes;

class DashboardWidgetModule
{

    public function register()
    {
        add_action('wp_dashboard_setup', array($this, 'addWidget'));
    }

    /**
     *
     */
    public function addWidget()
    {
        if (!AccessControl::hasEndPointPermission('get_sumissions', 'submissions')) {
            return false;
        }
        wp_add_dashboard_widget('wpjb_stat_widget', __('WPJobBoard Latest Applications', 'ninja-job-board'), array($this, 'showStat'), 10, 1);
    }

    public function showStat()
    {
        $stats = wpJobBoardDB()->table('wjb_applications')
            ->select([
                'wjb_applications.id',
                'wjb_applications.form_id',
                'wjb_applications.applicant_name',
                'wjb_applications.application_status',
                'posts.post_title'
            ])
            ->orderBy('wjb_applications.id', 'DESC')
            ->join('posts', 'posts.ID', '=', 'wjb_applications.form_id')
            ->limit(10)
            ->get();

        $allCurrencySettings = [];

        if (!$stats) {
            echo 'You can see Job Applications here';
            return;
        }

        $this->printStats($stats);
        return;
    }

    private function printStats($stats)
    {
        ?>
        <ul class="wpjb_dashboard_stats">
            <?php foreach ($stats as $stat): ?>
                <li>
                    <a title="Form: <?php echo esc_url($stat->post_title); ?>"
                       href="<?php echo admin_url('admin.php?page=wpjobboard.php#/edit-form/' . intval($stat->form_id) . '/entries/' . $stat->id . '/view'); ?>">
                        #<?php echo esc_html($stat->id); ?> - <?php echo esc_html($stat->applicant_name); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if (!defined('NINJA_TABLES_DIR_URL')): ?>
        <div class="wpjb_recommended_plugin">
            Recommended Plugin: <b>Ninja Tables</b> - Best Table Plugin for WP -
            <a href="<?php echo esc_url_raw($this->getInstallUrl('ninja-tables')); ?>">Install</a>
            | <a target="_blank" rel="noopener" href="https://wordpress.org/plugins/ninja-tables/">Learn More</a>
        </div>
    <?php elseif (!defined('ENHANCED_BLOCKS_VERSION')) : ?>
        <div class="wpjb_recommended_plugin">
            Recommended Plugin: <b>Enhanced Blocks – Page Builder Blocks for Gutenberg</b> <br/>
            <a href="<?php echo esc_url_raw($this->getInstallUrl('enhanced-blocks')); ?>">Install</a>
            | <a target="_blank" rel="noopener" href="https://wordpress.org/plugins/enhanced-blocks/">Learn More</a>
        </div>
    <?php endif; ?>
        <style>
            ul.wpjb_dashboard_stats span.wpjb_status {
                border: 1px solid gray;
                border-radius: 3px;
                padding: 0px 7px 2px;
                text-transform: capitalize;
                font-size: 11px;
            }

            ul.wpjb_dashboard_stats span.wpjb_status_paid {
                background: #f0f9eb;
            }

            ul.wpjb_dashboard_stats span.wpjb_status_pending {
                background: #fffaf2;
            }

            ul.wpjb_dashboard_stats span.wpjb_status_failed {
                background: #fdd;
            }

            ul.wpjb_dashboard_stats {
                margin: 0;
                padding: 0;
                list-style: none;
            }

            ul.wpjb_dashboard_stats li {
                padding: 8px 12px;
                border-bottom: 1px solid #eeeeee;
                margin: 0 -12px;
                cursor: pointer;
            }

            ul.wpjb_dashboard_stats li:hover {
                background: #fafafa;
                border-bottom: 1px solid #eeeeee;
            }

            ul.wpjb_dashboard_stats li:hover a {
                color: black;
            }

            ul.wpjb_dashboard_stats li:nth-child(2n+2) {
                background: #f9f9f9;
            }

            ul.wpjb_dashboard_stats li span.wpjb_total {
                float: right;
            }

            ul.wpjb_dashboard_stats li a {
                display: block;
                color: #0073aa;
                font-weight: 500;
                font-size: 105%;
            }

            .wpjb_recommended_plugin {
                padding: 15px 0px 0px;
            }

            .wpjb_recommended_plugin a {
                font-weight: bold;
                font-size: 110%;
            }
        </style>
        <?php
    }

    private function getInstallUrl($plugin)
    {
        return wp_nonce_url(
            self_admin_url('update.php?action=install-plugin&plugin=' . $plugin),
            'install-plugin_' . $plugin
        );
    }
}
