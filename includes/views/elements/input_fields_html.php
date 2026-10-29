<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<?php if($items): ?>
<div class="wpjb_conf_view">
    <div class="entry_info_box">
        <div class="entry_info_header">
            <div class="info_box_header"><?php esc_html_e('Application Details', 'ninja-job-board'); ?></div>
        </div>
        <div class="entry_info_body">
            <?php foreach ($items as $data): ?>
                <?php if(!$data['value'] || (isset($data['type']) && $data['type'] == 'hidden_input')) { continue; } ?>
                <div class="wpjb_each_entry">
                    <div class="wpjb_entry_label">
                        <?php echo esc_html($data['label']); ?>
                    </div>
                    <div class="wpjb_entry_value"><?php echo wp_kses_post($data['value']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if($load_css): ?>
    <style type="text/css">
        .wpjb_conf_view .entry_info_box {
            box-shadow: 0 7px 14px 0 rgba(60, 66, 87, 0.1), 0 3px 6px 0 rgba(0, 0, 0, 0.07);
            border-radius: 4px;
            background-color: white;
            margin-bottom: 30px;
        }

        .wpjb_conf_view .entry_info_box .entry_info_header {
            padding: 10px 20px;
            box-shadow: inset 0 -1px #e3e8ee;
        }

        .wpjb_conf_view .entry_info_box .entry_info_header .info_box_header {
            line-height: 24px;
            font-size: 22px;
            font-weight: bold;
            display: inline-block;
        }

        .wpjb_conf_view .entry_info_box .entry_info_body {
            padding: 16px 20px;
        }

        .wpjb_conf_view .wpjb_entry_details {
            margin-top: -16px;
        }

        .wpjb_conf_view .wpjb_each_entry {
            margin: 0px -20px;
            padding: 12px 20px;
            box-shadow: inset 0 -1px 0 #f3f4f5;
        }

        .wpjb_conf_view .wpjb_each_entry:last-child {
            box-shadow: none;
        }

        .wpjb_conf_view .wpjb_each_entry:hover {
            background-color: #f7fafc;
        }

        .wpjb_conf_view .wpjb_each_entry .wpjb_entry_label {
            font-weight: bold;
            color: #697386;
        }

        .wpjb_conf_view .wpjb_each_entry .wpjb_entry_value {
            margin-top: 0px;
            padding-left: 25px;
            white-space: pre-line;
        }

        .wpjb_conf_view .wpjb_file_list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            white-space: normal;
        }

        .wpjb_conf_view .wpjb_file {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            max-width: 100%;
            padding: 4px 12px 4px 4px;
            border: 1px solid #e1e4ea;
            border-radius: 999px;
            background: #fff;
            color: #0e121b;
            font-size: 14px;
            line-height: 20px;
            text-decoration: none;
        }

        .wpjb_conf_view .wpjb_file:hover,
        .wpjb_conf_view .wpjb_file:focus-visible {
            border-color: #cacfd8;
            background: #f9fafb;
        }

        .wpjb_conf_view .wpjb_file_ext {
            flex: none;
            padding: 2px 6px;
            border-radius: 999px;
            background: #f2f5f8;
            color: #525866;
            font-size: 11px;
            font-weight: 600;
            line-height: 16px;
        }

        .wpjb_conf_view .wpjb_file_thumb {
            flex: none;
            width: 32px;
            height: 32px;
            border-radius: 999px;
            object-fit: cover;
        }

        .wpjb_conf_view .wpjb_file_name {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
<?php endif; ?>

<?php endif; ?>
