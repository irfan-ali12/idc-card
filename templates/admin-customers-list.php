<?php
use IDC\DB;

if (!defined('ABSPATH')) { exit; }
if (!current_user_can('idc_read')) { wp_die('No access'); }

$db = new DB();
$q = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$list = $db->find_customers($q);
?>
<div class="wrap">
  <h1 class="wp-heading-inline">ID Card Customers</h1>
  <form method="get">
    <input type="hidden" name="page" value="idc-dashboard" />
    <p class="search-box">
      <label class="screen-reader-text" for="search-input">Search Customers:</label>
      <input type="search" id="search-input" name="s" value="<?php echo esc_attr($q); ?>" />
      <input type="submit" class="button" value="Search">
    </p>
  </form>

  <table class="widefat fixed striped">
    <thead>
      <tr>
        <th>ID</th>
        <th>Full Name</th>
        <th>National ID</th>
        <th>DOB</th>
        <th>Country</th>
        <th>Issued</th>
        <th>Passport</th>
        <th>Updated</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="8">No customers found.</td></tr>
      <?php else: foreach ($list as $r): ?>
        <tr>
          <td><?php echo esc_html($r['id']); ?></td>
          <td><?php echo esc_html($r['full_name']); ?></td>
          <td><?php echo esc_html($r['national_id']); ?></td>
          <td><?php echo esc_html($r['dob']); ?></td>
          <td><?php echo esc_html($r['country']); ?></td>
          <td><?php echo esc_html($r['issued_on']); ?></td>
          <td><?php echo esc_html($r['passport_no']); ?></td>
          <td><?php echo esc_html($r['updated_at'] ?: $r['created_at']); ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
