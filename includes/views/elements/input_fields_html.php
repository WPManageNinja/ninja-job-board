<?php if($items): ?>
<div class="wpjb_conf_view">
    <div class="entry_info_box">
        <div class="entry_info_header">
            <div class="info_box_header"><?php _e('Application Details', 'wpjobboard'); ?></div>
        </div>
        <div class="entry_info_body">
            <?php foreach ($items as $data): ?>
                <?php if(!$data['value'] || $data['type'] == 'hidden_input') { continue; } ?>
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
    </style>
<?php endif; ?>

<?php endif; ?>
