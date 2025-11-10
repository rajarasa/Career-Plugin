<?php
/*
Plugin Name: Career Jobs Plugin
Description: Custom career jobs listing with popup details.
Version: 1.1
Author: Raja
*/

// Register Career Job Post Type
function cjp_register_career_jobs() {
    $labels = array(
        'name'               => 'Career Jobs',
        'singular_name'      => 'Career Job',
        'add_new'            => 'Add New Job',
        'add_new_item'       => 'Add New Career Job',
        'edit_item'          => 'Edit Career Job',
        'new_item'           => 'New Career Job',
        'all_items'          => 'All Career Jobs',
        'menu_name'          => 'Career Jobs',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'supports'           => array('title', 'editor', 'thumbnail'),
        'menu_icon'          => 'dashicons-businessman',
    );

    register_post_type('career_job', $args);
}
add_action('init', 'cjp_register_career_jobs');

// Shortcode to display jobs
function cjp_display_jobs() {
    ob_start();
    $jobs = new WP_Query(array(
        'post_type' => 'career_job',
        'posts_per_page' => -1
    ));
?>

<style>
.cjp-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 30px;
  margin: 20px 0;
}
.cjp-box {
  width: 30%;
  padding:  20px;
  background: #f5f5f5;
  border-radius: 10px;
  cursor: pointer;
  position: relative;
  transition: 0.3s;
  box-shadow: 0 6px 18px rgba(0,0,0,0.06);
}
@media (max-width: 1024px) {
  .cjp-box { width: 48%; }
}
@media (max-width: 640px) {
  .cjp-box { width: 100%; }
}
.cjp-box:hover {
  background: #eee;
}
.cjp-thumb img {
    width: 100%;
    
    object-fit: cover;
    border-radius: 12px;
    margin-bottom: 15px;
}
.cjp-number {
  color: #8b8b8b;
  font-size: 14px;
  margin-bottom: 8px;
}
.cjp-title {
  font-size: 20px;
  font-weight: 700;
}
.cjp-arrow {
  position: absolute;
  bottom: 20px;
  right: 20px;
  background: #222;
  width: 40px;
  height: 40px;
  color: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
}
#cjp-popup {
  display: none;
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: rgba(0,0,0,0.6);
  justify-content: center; align-items: center;
  z-index: 99999;
}
.cjp-popup-content {
  background: #fff;
  width: 700px;
  padding: 30px 20px;
  border-radius: 10px;
  max-height: 80vh;
  overflow-y: auto;
  box-shadow: 0 8px 30px rgba(0,0,0,0.2);
  position: relative;
}

/* top-right close X button */
.cjp-close-x {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 32px;
  height: 32px;
  background: #000;
  color: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  cursor: pointer;
  z-index: 100000;
}

.cjp-close-x:hover {
  background: #333;
}

.cjp-job-form {
    margin-top: 25px;
    padding: 15px;
    background: #f6f6f6;
    border-radius: 10px;
}
</style>

<div class="cjp-grid">
<?php
$count = 1;
while ($jobs->have_posts()) : $jobs->the_post();
?>
  <div class="cjp-box" data-id="<?php echo get_the_ID(); ?>">

      <?php if(has_post_thumbnail()) : ?>
          <div class="cjp-thumb">
              <?php the_post_thumbnail('medium'); ?>
          </div>
      <?php endif; ?>

      <div class="cjp-number"><?php echo sprintf('%02d', $count); ?>.</div>
      <div class="cjp-title"><?php the_title(); ?></div>
      <div class="cjp-arrow">→</div>
  </div>
<?php
$count++;
endwhile; wp_reset_postdata();
?>
</div>

<div id="cjp-popup">
    <div class="cjp-popup-content">
        <div class="cjp-close-x" id="cjp-close">✕</div>
        <div id="cjp-popup-data"></div>
    </div>
</div>

<script>
jQuery(function($){
    $(".cjp-box").click(function(){
        let jobId = $(this).data("id");

        $.post("<?php echo admin_url('admin-ajax.php'); ?>", {
            action: "cjp_job_details",
            job_id: jobId
        }, function(response){
            $("#cjp-popup-data").html(response);
            $("#cjp-popup").css("display", "flex");
        });
    });

    $("#cjp-close, #cjp-popup").on("click", function(e){
        if(e.target === this) {
            $("#cjp-popup").hide();
        }
    });

    $(".cjp-popup-content").on("click", function(e){
        e.stopPropagation();
    });
});
</script>

<?php
    return ob_get_clean();
}
add_shortcode('career_jobs', 'cjp_display_jobs');

// AJAX details
function cjp_job_details() {
    $job_id = intval($_POST['job_id']);

    if(! $job_id) {
        echo 'Invalid job.';
        wp_die();
    }

    // Title
    echo '<h2>'.esc_html(get_the_title($job_id)).'</h2>';

    // Description
    echo '<div>'.apply_filters("the_content", get_post_field("post_content", $job_id)).'</div>';

    // Contact Form 7 (dummy shortcode)
    echo '<div class="cjp-job-form">';
    echo do_shortcode('[contact-form-7 id="4746685" title="Career"]');
    echo '</div>';

    wp_die();
}
add_action('wp_ajax_cjp_job_details', 'cjp_job_details');
add_action('wp_ajax_nopriv_cjp_job_details', 'cjp_job_details');
