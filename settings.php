<?php 
    $upload_dir = (object)wp_get_upload_dir();
?>

<div class="xfr_settings">
    <h1 class="xfr_title"><?php esc_html_e('RitziExport', 'xfr'); ?></h1>

    <?php settings_errors(); ?>

    <div class="xfr_content">
        <div class="xfr_content__info">
            <?php if (file_exists($upload_dir->basedir . "/" . self::XML_FILE)):?>
                <h2><?php echo esc_html_e('Feed created', 'xfr') . ": " . self::XML_FILE; ?></h2>
            <?php else:?>
                <h2><?php echo esc_html_e('Feed not created', 'xfr'); ?></h2>
            <?php endif;?>

            <?php if (file_exists($upload_dir->basedir . "/" . self::XML_FILE)):?>
                <div class="info-block">
                    <p><span class="xfr-bold"><?php echo esc_html_e('Your feed here', 'xfr'); ?>:</span> <a target="_blank" href="<?php echo $upload_dir->baseurl . "/" . self::XML_FILE ?>"><?php echo $upload_dir->baseurl . "/" . self::XML_FILE ?></a></p>
                </div>
            <?php endif;?>

            <p class="submit">
                <form method="post" action="<?php echo admin_url('admin.php?page=xfr-settings&action=export') ?>">
                    <input type="submit" name="submit" id="submit-export" class="button button-primary" value="<?php echo esc_html_e('Export products', 'xfr'); ?>"/>
                </form>
            </p>
        </div>
        <div class="xfr_content__form">
            <form method="post" action="options.php">
                <?php
                    settings_fields('xfr_settings');
                    do_settings_sections('xfr-settings');
                    submit_button();
                ?>
            </form>
        </div>
    </div>
</div>