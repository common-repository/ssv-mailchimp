<?php
namespace mp_ssv_mailchimp;
use mp_ssv_general\SSV_General;
use mp_ssv_general\User;

if (!defined('ABSPATH')) {
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'ignore_message') {
    update_option(SSV_MailChimp::OPTION_IGNORE_USERS_LIST_MESSAGE, true);
}

if (SSV_General::isValidPOST(SSV_MailChimp::ADMIN_REFERER_OPTIONS)) {
    if (isset($_POST['reset'])) {
        SSV_MailChimp::resetOptions();
    } elseif (isset($_POST['push_all_members'])) {
        foreach (get_users() as $user) {
            $user = new User($user);
            mp_ssv_mailchimp_update_member($user, get_option(SSV_Mailchimp::OPTION_USERS_LIST));
        }
    } else {
        if ($_POST['users_list'] != -1) {
            update_option(SSV_MailChimp::OPTION_USERS_LIST, SSV_General::sanitize($_POST['users_list'], 'text'));
        } else {
            delete_option(SSV_MailChimp::OPTION_USERS_LIST);
        }
        $showAllKeys = isset($_POST['show_all_meta_keys']) ? SSV_General::sanitize($_POST['show_all_meta_keys'], 'boolean') : false;
        update_option(SSV_MailChimp::OPTION_SHOW_ALL_META_KEYS, $showAllKeys);
        $links = array();
        $i     = 1;
        while (isset($_POST['link_' . $i . '_tag'])) {
            $links[] = json_encode(
                array(
                    'ID'        => $i,
                    'fieldName' => SSV_General::sanitize($_POST['link_' . $i . '_field'], 'text'),
                    'tagName'   => SSV_General::sanitize($_POST['link_' . $i . '_tag'], 'text'),
                )
            );
            $i++;
        }
        update_option(SSV_MailChimp::OPTION_MERGE_TAG_LINKS, $links);
    }
}
$links = get_option(SSV_MailChimp::OPTION_MERGE_TAG_LINKS, array());
?>
<form method="post" action="#">
    <table class="form-table">
        <tr>
            <th scope="row">Users List</th>
            <td>
                <select name="users_list" title="Users List">
                    <option value="-1">Select One</option>
                    <?php $selected = get_option(SSV_MailChimp::OPTION_USERS_LIST, ''); ?>
                    <?php foreach (SSV_MailChimp::getLists() as $listID => $listName): ?>
                        <option value="<?= esc_html($listID) ?>" <?= selected($listID, $selected, false) ?>><?= esc_html($listName) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <?php if (SSV_General::usersPluginActive()): ?>
            <tr valign="top">
                <th scope="row">Show All Meta Keys</th>
                <td>
                    <label>
                        <input type="hidden" name="show_all_meta_keys" value="false"/>
                        <input type="checkbox" name="show_all_meta_keys" value="true" <?= get_option(SSV_MailChimp::OPTION_SHOW_ALL_META_KEYS) ? 'checked' : '' ?> />
                        Show all the meta keys.
                    </label>
                </td>
            </tr>
        <?php endif; ?>
    </table>
    <?php if (!empty(get_option(SSV_MailChimp::OPTION_USERS_LIST, ''))): ?>
        <div style="overflow-x: auto;">
            <table id="custom-tags-placeholder"></table>
            <button type="button" onclick="mp_ssv_add_new_custom_merge_tag()">Add Link</button>
        </div>
        <script>
            i = <?= count($links) + 1 ?>;
            function mp_ssv_add_new_custom_merge_tag() {
                mp_ssv_add_new_merge_tag(i, null, null);
                i++;
            }
            <?php foreach($links as $link): ?>
            <?php $link = json_decode($link, true); ?>
            mp_ssv_add_new_merge_tag('<?= $link['ID'] ?>', '<?= $link['fieldName'] ?>', '<?= $link['tagName'] ?>');
            <?php endforeach; ?>
        </script>
    <?php endif; ?>
    <br/><input type="submit" name="push_all_members" id="push_all_members" class="button button-primary" value="Push all members to MailChimp">
    <?= SSV_General::getFormSecurityFields(SSV_MailChimp::ADMIN_REFERER_OPTIONS); ?>
</form>
