<?php
/*
 * Created: 2014-07-15
 * Last Revised: 2014-07-15
 *
 * CHANGELOG:
 * 2014-07-15
 *      - Initial Class Creation
 */

class RROptions {

    var $options_name;

    var $defaults;

    /**
     *
     * @var MIXED STRING/BOOL
     */
    var $updated = FALSE;

    /**
     *
     * @var RichReviews
     */
    var $core;

    var $old_pt_slug;
    var $new_pt_slug;

    /**
     * List of supported option keys.
     * 
     * @var array
     */
    const OPTION_KEYS = [
        'rr-update-options',
        'rr-update-support',
        'rr-update-support-prompt',
    ];

    /**
     *
     * @param RichReviews $core
     */
    public function __construct( $core, $update_options_key = null ) {
        $this->core = $core;
        $this->options_name = $core->options_name;
        $this->defaults = array(
            'version' => '1.7.3',
            'star_color' => '#ffaf00',
            'snippet_stars' => FALSE,
            'reviews_order' => 'asc',
            'approve_authority' => 'manage_options',
            'require_approval' => 'checked',
            'show_form_post_title' => FALSE,
            'display_full_width' => FALSE,
            'credit_permission'=> FALSE,
            'show_date' => FALSE,
            'rich_itemReviewed_fallback' => 'Service',
            'rich_itemReviewed_fallback_case' => 'both_missing',
            'rich_author_fallback' => 'Anonymous',
            'rich_include_url' => FALSE,
            'rich_url_value' => '',
            'form-name-label' => 'Name',
            'form-name-display' => 'checked',
            'form-name-require' => 'checked',
            'form-name-use-usernames' => FALSE,
            'form-email-use-useremails' => FALSE,
            'form-name-use-avatar' => FALSE,
            'form-name-use-blank-avatar' => FALSE,
            'unregistered-allow-avatar-upload' => FALSE,
            'form-reviewer-image-label' => 'Reviewer Image',
            'form-reviewer-image-require' => FALSE,
            'form-email-label' => 'Email',
            'form-email-display' => 'checked',
            'form-email-require' => FALSE,
            'form-title-label' => 'Review Title',
            'form-title-display' => 'checked',
            'form-title-require' => 'checked',
            'form-rating-label' => 'Rating',
            'form-content-label' => 'Review Content',
            'form-content-display' => 'checked',
            'form-content-require' => 'checked',
            'form-submit-text' => 'Submit',
            'integrate-user-info' => FALSE,
            'return-to-form' => FALSE,
            'send-email-notifications' => FALSE,
            'admin-email' => '',
            'login-url' => '',
            'add-shopper-approved' => 'checked',
            'require-login' => FALSE,
            'read-more-text' => 'Read More',
            'show-less-text' => 'Show Less',
            'excerpt-length' => 150,
            'schema_type' =>  'Product',
            'submit-form-redirect' => FALSE
        );
        
        if ( in_array( $update_options_key, self::OPTION_KEYS, true ) ) {
            $this->updated = $update_options_key;
        }

        if ($this->get_option() == FALSE) {
            $this->set_to_defaults();
        }
        $this->update_options();
    }

    public function set_to_defaults() {
        delete_option($this->options_name);
        foreach ($this->defaults as $key=>$value) {
            $this->update_option($key, $value);
        }
    }

    /**
     * Get the sanitized settings posted in the admin.
     *
     * @return array
     */
    protected function get_posted_options() {
        $posted = array();
            
        foreach ( $this->defaults as $option_key => $option_default_value ) {
            $option_posted_value = filter_input( INPUT_POST, $option_key, FILTER_SANITIZE_STRING );

            if ( null !== $option_posted_value ) {
                $posted[ $option_key ] = sanitize_text_field( $option_posted_value );
            } else {
                $posted[ $option_key ] = null;
            }
        }

        return $posted;
    }

    public function update_options( $init = null ) {
        $posted = $this->get_posted_options();
        $current_settings = array_merge( $this->defaults, $this->get_option() );

        if($init == true ) {
            foreach($this->defaults as $key => $val) {
                if(!$this->get_option($key)) {
                  $this->update_option($key, $val);
                }
            }
        }

        if ( $this->updated === 'rr-update-options' ) {
            if( ! $posted['integrate-user-info'] ) {
                $posted['form-name-use-usernames'] = false;
                $posted['require-login'] = false;
                $posted['form-name-use-avatar'] = false;
                $posted['unregistered-allow-avatar-upload'] = false;
            } elseif ( $posted['require-login'] == 'checked' ) {
                $posted['unregistered-allow-avatar-upload'] = false;
            }

            if ( $posted['form-name-use-avatar'] == false ) {
                $posted['form-name-use-blank-avatar'] = false;
            }

            $this->update_option( array_merge( $current_settings, $posted ) );
        } elseif ( $this->updated === 'rr-update-support' || $this->updated === 'rr-update-support-prompt' ) {
            $this->update_option( array_merge( $current_settings, $posted ) );
        }
    }

    // From metabox v1.0.6

    /**
    * Gets an option for an array'd wp_options,
    * accounting for if the wp_option itself does not exist,
    * or if the option within the option
    * (cue Inception's 'BWAAAAAAAH' here) exists.
    * @since  Version 1.0.0
    * @param  string $opt_name
    * @return mixed (or FALSE on fail)
    */
    public function get_option($opt_name = '') {
       $options = get_option($this->options_name);

       // maybe return the whole options array?
       if ($opt_name == '') {
           return $options;
       }

       // are the options already set at all?
       if ($options == FALSE) {
           return $options;
       }

       // the options are set, let's see if the specific one exists
       if (! isset($options[$opt_name])) {
           return FALSE;
       }

       // the options are set, that specific option exists. return it
       return $options[$opt_name];
    }

    /**
    * Wrapper to update wp_options. allows for function overriding
    * (using an array instead of 'key, value') and allows for
    * multiple options to be stored in one name option array without
    * overriding previous options.
    * @since  Version 1.0.0
    * @param  string $opt_name
    * @param  mixed $opt_val
    */
    public function update_option( $opt_name, $opt_val = '' ) {
       // ----- allow a function override where we just use a key/val array
       if ( is_array( $opt_name ) && $opt_val == '' ) {
           foreach ( $opt_name as $real_opt_name => $real_opt_value) {
               $this->update_option( $real_opt_name, $real_opt_value );
           }
       } else {
           $current_options = $this->get_option(); // get all the stored options

           // ----- make sure we at least start with blank options
           if ( ! is_array( $current_options ) ) {
               $current_options = array();
           }

           $current_options[ $opt_name ] = sanitize_text_field( $opt_val );

           // ----- now save using the wordpress function
           update_option( $this->options_name, $current_options );
       }
    }

    /**
    * Given an option that is an array, either update or add
    * a value (or data) to that option and save it
    * @since  Version 1.0.0
    * @param  string $opt_name
    * @param  mixed $key_or_val
    * @param  mixed $value
    */
    public function append_to_option($opt_name, $key_or_val, $value = NULL, $merge_values = TRUE) {
       $key = '';
       $val = '';
       $results = $this->get_option($opt_name);

       // ----- always use at least an empty array!
       if (! $results) {
           $results = array();
       }

       // ----- allow function override, to use automatic array indexing
       if ($value === NULL) {
           $val = $key_or_val;

           // if value is not in array, then add it.
           if (! in_array($val, $results)) {
               $results[] = $val;
           }
       }
       else {
           $key = $key_or_val;
           $val = $value;

           // ----- should we append the array value to an existing array?
           if ($merge_values && isset($results[$key]) && is_array($results[$key]) && is_array($val)) {
                   $results[$key] = array_merge($results[$key], $val);
           }
           else {
                   // ----- don't care if key'd value exists. we override it anyway
                   $results[$key] = $val;
           }
       }

       // use our internal function to update the option data!
       $this->update_option($opt_name, $results);
    }

    public function update_messages() {
        if ($this->updated == 'rr-update-options') {
            echo '<div class="updated">The options have been successfully updated.</div>';
            $this->updated = FALSE;
        }
        else if ($this->updated == 'rr-update-support') {
             echo '<div class="updated">Thank you for supporting the development team! We really appreciate how awesome you are.</div>';
            $this->updated = FALSE;
        }
    }

  public function force_update() {
      $current_settings = $this->get_option();
      $this->defaults = array_merge($this->defaults, $current_settings);
      $update = array_merge($this->defaults, $_POST);
      $data = array();
      foreach ($update as $key=>$value) {
          if ($key != 'update' && $key != NULL) {
              $data[$key] = $value;
          }
      }
      $this->update_option($data);
      $this->updated = 'rr-update-support';
  }
}
