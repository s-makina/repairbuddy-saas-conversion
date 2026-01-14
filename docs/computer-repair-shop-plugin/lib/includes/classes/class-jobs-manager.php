<?php
/**
 * Plugin Name: WCRB Jobs Manager
 */

class WCRB_JOBS_MANAGER {
    private static $instance = null;
    private $table_name;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wc_cr_jobs';
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action( 'wp_insert_post', [$this, 'handle_post_creation'], 10, 3 );
        add_action( 'before_delete_post', [$this, 'handle_post_deletion'] );
    }

    /**
     * Create a job entry in our custom table
     * $job_id = $jobs_manager->create_job(123); // 123 is post_id
     */
    public function create_job( $post_id ) {
        global $wpdb;
        
        // Verify post exists and is correct type
        $post = get_post( $post_id );
        if ( ! $post || $post->post_type !== 'rep_jobs' ) {
            return new WP_Error( 'invalid_post', 'Invalid job post' );
        }
        
        // Check if job already exists for this post
        $existing_job = $this->get_job_by_post_id( $post_id );
        if ( $existing_job ) {
            return $existing_job->job_id;
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            [
                'post_id' => $post_id,
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s']
        );
        
        if ($result === false) {
            return new WP_Error( 'db_error', 'Failed to create job entry' );
        }
        
        $job_id = $wpdb->insert_id;
        
        // Update post meta for easy searching
        update_post_meta( $post_id, '_wcrb_job_id', $job_id );
        
        $args = array(
                        "job_id" 		=> $post_id, 
                        "name" 			=> esc_html__( "Job ID assigned", "computer-repair-shop" ), 
                        "type" 			=> 'private', 
                        "field" 		=> '_wcrb_job_id', 
                        "change_detail" => $job_id
                    );

        $WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
        $WCRB_JOB_HISTORY_LOGS->wc_record_job_history( $args );
        
        return $job_id;
    }

    /**
     * Get job by post ID
     */
    public function get_job_by_post_id($post_id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE post_id = %d",
                $post_id
            )
        );
    }

    /**
     * Get job by job ID
     */
    public function get_job_by_job_id($job_id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT j.*, p.post_title, p.post_status, p.post_author 
                 FROM {$this->table_name} j 
                 LEFT JOIN {$wpdb->posts} p ON j.post_id = p.ID 
                 WHERE j.job_id = %d",
                $job_id
            )
        );
    }

    /**
     * Get post ID by job ID
     */
    public function get_post_id_by_job_id( $job_id ) {
        global $wpdb;
        
        // Remove leading zeros from the input
        $clean_job_id = ltrim($job_id, '0');
        
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$this->table_name} WHERE job_id = %d",
                $clean_job_id
            )
        );
    }

    /**
     * Get formatted job number (with leading zeros)
     */
    public function get_formatted_job_number($job_id, $digits = 4) {
        return str_pad($job_id, $digits, '0', STR_PAD_LEFT);
    }

    /**
     * Get job display data with formatted number
     * $job_data = $jobs_manager->get_job_display_data(123);
     */
    public function get_job_display_data( $post_id ) {
        $job = $this->get_job_by_post_id($post_id);
        
        if (!$job) {
            // Auto-create if doesn't exist
            $job_id = $this->create_job($post_id);
            if (is_wp_error($job_id)) {
                return false;
            }
            $job = $this->get_job_by_post_id($post_id);
        }
        
        $post = get_post( $post_id );
        
        $_return_job_number = ( defined( 'WCRB_OLD_JOB_NUMBERS' ) ) ? 'YES' : 'NO';

        return [
            'job_id' => $job->job_id,
            'formatted_job_number' => ( ( $_return_job_number == 'YES' ) ? $post_id : $this->get_formatted_job_number( $job->job_id ) ),
            'post_id' => $post_id,
            'post_title' => $post->post_title,
            'post_status' => $post->post_status,
            'created_at' => $job->created_at,
            'author_id' => $post->post_author
        ];
    }

    /**
     * Delete job entry
     */
    public function delete_job($post_id) {
        global $wpdb;
        
        // Delete post meta
        delete_post_meta( $post_id, '_wcrb_job_id' );
        
        // Add deletion note to history
        $job = $this->get_job_by_post_id($post_id);
        if ($job) {
            $args = array(
                        "job_id" 		=> $post_id, 
                        "name" 			=> esc_html__( "Job deleted - Was ID:", "computer-repair-shop" ), 
                        "type" 			=> 'private', 
                        "field" 		=> '_wcrb_job_id', 
                        "change_detail" => $job->job_id
                    );

            $WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
            $WCRB_JOB_HISTORY_LOGS->wc_record_job_history( $args );
        }
        
        return $wpdb->delete(
            $this->table_name,
            ['post_id' => $post_id],
            ['%d']
        );
    }

    /**
     * Get recent jobs
     */
    public function get_recent_jobs($limit = 10) {
        global $wpdb;
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT j.*, p.post_title, p.post_status, p.post_author 
                 FROM {$this->table_name} j 
                 LEFT JOIN {$wpdb->posts} p ON j.post_id = p.ID 
                 WHERE p.post_status != 'trash' 
                 ORDER BY j.job_id DESC 
                 LIMIT %d",
                $limit
            )
        );
    }

    /**
     * Handle post creation
     */
    public function handle_post_creation($post_id, $post, $update) {
        // Only handle rep_jobs post type
        if ( $post->post_type !== 'rep_jobs' ) {
            return;
        }
       
        // Only create job entry for new posts, not updates
        if ( $update ) {
            return;
        }
        
        // Create job entry
        $this->create_job( $post_id );
    }

    /**
     * Handle post deletion
     */
    public function handle_post_deletion($post_id) {
        $post = get_post($post_id);
        
        if ($post && $post->post_type === 'rep_jobs') {
            $this->delete_job($post_id);
        }
    }

    /**
     * Get job statistics
     */
    public function get_job_stats() {
        global $wpdb;
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_jobs,
                MAX(job_id) as last_job_id,
                MIN(job_id) as first_job_id
            FROM {$this->table_name}
        ");
        
        return $stats;
    }
}

// Initialize the jobs manager
WCRB_JOBS_MANAGER::getInstance();