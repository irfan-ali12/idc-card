<?php
if (!defined('ABSPATH')) { exit; }
if (!current_user_can('idc_manage')) { wp_die('You do not have permission.'); }

$opts = get_option('idc_settings', []);
$coords = $opts['coords'] ?? [];
$coords = wp_parse_args($coords, [
  'name'        => ['top'=>34.0,'left'=>9.0],
  'national_id' => ['top'=>60.0,'left'=>8.0],
  'dob'         => ['top'=>68.0,'left'=>8.0],
  'country'     => ['top'=>76.0,'left'=>8.0],
  'issued'      => ['top'=>84.0,'left'=>8.0],
  'passport_no' => ['top'=>92.0,'left'=>8.0],
  'photo'       => ['top'=>10.5,'left'=>14.5,'diameter'=>30.0,'offset_x'=>0,'offset_y'=>0],
  'qr'          => ['size'=>18.0,'bottom'=>30.0,'left'=>20.0],
]);

if (!empty($_POST['idc_save_settings']) && check_admin_referer('idc_settings')) {
  $opts['front_png_id'] = (int)($_POST['front_png_id'] ?? 0);
  $opts['back_png_id']  = (int)($_POST['back_png_id'] ?? 0);
  $opts['garet_font_id']= (int)($_POST['garet_font_id'] ?? 0);

  foreach (['name','national_id','dob','country','issued','passport_no'] as $f) {
    $coords[$f]['top']  = (float)($_POST["c_{$f}_top"]  ?? $coords[$f]['top']);
    $coords[$f]['left'] = (float)($_POST["c_{$f}_left"] ?? $coords[$f]['left']);
  }
  foreach (['top','left','diameter','offset_x','offset_y'] as $k) {
    $coords['photo'][$k] = (float)($_POST["c_photo_{$k}"] ?? $coords['photo'][$k]);
  }
  foreach (['size','bottom','left'] as $k) {
    $coords['qr'][$k] = (float)($_POST["c_qr_{$k}"] ?? $coords['qr'][$k]);
  }

  $opts['coords'] = $coords;
  update_option('idc_settings', $opts);
  echo '<div class="updated"><p>Settings saved.</p></div>';
}

function idc_media_field($name, $val) {
  $url = $val ? wp_get_attachment_url((int)$val) : '';
  ?>
  <div class="idc-media-field">
    <input type="hidden" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($val); ?>" />
    <img class="idc-preview" src="<?php echo esc_url($url); ?>" style="max-width:160px;display:block;margin:.5rem 0;border:1px solid #e5e7eb;border-radius:8px;" />
    <button class="button idc-media-pick">Select</button>
    <button class="button idc-media-clear">Clear</button>
  </div>
  <?php
}
?>
<div class="wrap">
  <h1>ID Cards — Settings</h1>
  <form method="post">
    <?php wp_nonce_field('idc_settings'); ?>

    <h2 class="title">Artwork & Font</h2>
    <table class="form-table">
      <tr>
        <th scope="row">Front PNG (exported from PDF)</th>
        <td><?php idc_media_field('front_png_id', $opts['front_png_id'] ?? 0); ?></td>
      </tr>
      <tr>
        <th scope="row">Back PNG (exported from PDF)</th>
        <td><?php idc_media_field('back_png_id', $opts['back_png_id'] ?? 0); ?></td>
      </tr>
      <tr>
        <th scope="row">Garet Font (WOFF2)</th>
        <td><?php idc_media_field('garet_font_id', $opts['garet_font_id'] ?? 0); ?></td>
      </tr>
    </table>

    <h2 class="title">Field Coordinates (mm)</h2>
    <p>Adjust until your browser preview sits 1:1 on the front PNG. Use the “Show overlay guides” switch in the designer page.</p>
    <table class="form-table">
      <?php
      foreach (['name','national_id','dob','country','issued','passport_no'] as $f) {
        printf(
          '<tr><th>%1$s</th><td>Top <input name="c_%1$s_top" type="number" step="0.1" value="%2$.2f" class="small-text" /> mm &nbsp; Left <input name="c_%1$s_left" type="number" step="0.1" value="%3$.2f" class="small-text" /> mm</td></tr>',
          esc_html($f), $coords[$f]['top'], $coords[$f]['left']
        );
      }
      ?>
      <tr>
        <th>Photo (mm)</th>
        <td>
          Top <input name="c_photo_top" type="number" step="0.1" value="<?php echo esc_attr($coords['photo']['top']); ?>" class="small-text" /> &nbsp;
          Left <input name="c_photo_left" type="number" step="0.1" value="<?php echo esc_attr($coords['photo']['left']); ?>" class="small-text" /> &nbsp;
          Diameter <input name="c_photo_diameter" type="number" step="0.1" value="<?php echo esc_attr($coords['photo']['diameter']); ?>" class="small-text" /> &nbsp;
          Offset X <input name="c_photo_offset_x" type="number" step="0.1" value="<?php echo esc_attr($coords['photo']['offset_x']); ?>" class="small-text" /> &nbsp;
          Offset Y <input name="c_photo_offset_y" type="number" step="0.1" value="<?php echo esc_attr($coords['photo']['offset_y']); ?>" class="small-text" />
        </td>
      </tr>
      <tr>
        <th>QR (mm)</th>
        <td>
          Size <input name="c_qr_size" type="number" step="0.1" value="<?php echo esc_attr($coords['qr']['size']); ?>" class="small-text" /> &nbsp;
          Bottom <input name="c_qr_bottom" type="number" step="0.1" value="<?php echo esc_attr($coords['qr']['bottom']); ?>" class="small-text" /> &nbsp;
          Left <input name="c_qr_left" type="number" step="0.1" value="<?php echo esc_attr($coords['qr']['left']); ?>" class="small-text" />
        </td>
      </tr>
    </table>

    <p><button class="button button-primary" name="idc_save_settings" value="1">Save Settings</button></p>
  </form>
</div>
