<?php
/**
 * Created by Brad Tollett
 * Date: 1/6/2017
 * Time: 12:01 PM
 */

if(!class_exists('WPCF7_ContactForm')) {
    require_once( WPCF7_PLUGIN_URL . '/includes/contact-form.php' );
}

/**
 * Class WP_REST_Contact_Form_7_Controller
 * TODO: The keys for the properties in prepare_item_for_response are not going to match the schema that I declared, this needs to be fixed.
 * TODO: The schema needs to be reworked completely in this. Try and mimick the Post_Controller schema in the default Rest API Endpoints.
 */
class WP_REST_Contact_Form_7_Controller extends WP_REST_Controller {

    /**
     * @var string
     */
    protected $rest_base = "forms";
    /**
     * @var string
     */
    protected $namespace = "wpcf7/v1";
    /**
     * @var string
     */
    protected $post_type = WPCF7_ContactForm::post_type;

    /**
     *
     */
    public function register_routes() {
        register_rest_route( $this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_items' ),
                'args'                => array(),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
            ),
            array(
                'methods'         => WP_REST_Server::CREATABLE,
                'callback'        => array( $this, 'create_item' ),
                'permission_callback' => array( $this, 'create_item_permissions_check' ),
                'args'            => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
            ),
            'schema' => array( $this, 'get_public_item_schema' )
        ) );
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_item' ),
                'args'                => array(),
                'permission_callback' => array( $this, 'get_item_permissions_check' ),
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_item' ),
                'args'                => array(),
                'permission_callback' => array( $this, 'update_item_permissions_check' ),
            ),
            'schema' => array( $this, 'get_public_item_schema' )
        ) );
    }

    /**
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function get_items_permissions_check($request ) {
        return $this->get_item_permissions_check($request);
    }

    /**
     * @param WP_REST_Request $request
     * @return mixed|WP_REST_Response
     */
    public function get_items($request) {
        $args = array();
        $query = WPCF7_ContactForm::find($args);
        $forms = array();
        foreach($query as $form) {
            $data = $this->prepare_item_for_response($form, $request);
            $forms[] = $this->prepare_response_for_collection($data);
        }
        $response = rest_ensure_response($forms);
        $total_forms = WPCF7_ContactForm::count();
        $response->header('X-WP-Total', (int) $total_forms);
        return $response;
    }

    /**
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function get_item_permissions_check($request ) {
        $post_type = get_post_type_object( $this->post_type );
        if (! current_user_can( $post_type->cap->edit_others_posts ) ) {
            return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to view posts of this post type' ), array( 'status' => rest_authorization_required_code() ) );
        }
        return true;
    }

    /**
     * @param WP_REST_Request $request
     * @return mixed|WP_Error|WP_REST_Response
     */
    public function get_item($request) {
        $id = (int) $request['id'];
        $form = WPCF7_ContactForm::get_instance( $id );

        if ( empty( $id ) || empty($form) || empty($form->id())) {
            return new WP_Error( 'rest_post_invalid_id', __( 'Invalid form id.' ), array( 'status' => 404 ) );
        }
        $data = $this->prepare_item_for_response($form, $request);
        $response = rest_ensure_response($data);
        $total_forms = WPCF7_ContactForm::count();
        $response->header('X-WP-Total', (int) $total_forms);
        return $response;
    }

    /**
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function create_item_permissions_check($request) {
        $post_type = get_post_type_object( $this->post_type );
        if (! current_user_can( $post_type->cap->publish_posts ) ) {
            return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to publish posts of this post type' ), array( 'status' => rest_authorization_required_code() ) );
        }
        return true;
    }


    /**
     * @param WP_REST_Request $request
     * @return mixed|WP_REST_Response
     * TODO: This should return a JSON representation of the form instead of just the $id
     */
    public function create_item($request) {
        $request->set_param("id", -1);
        $form = $this->prepare_item_for_database($request);
        $id = $form->save();
        $response = rest_ensure_response($id);
        return $response;
    }

    /**
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function update_item_permissions_check($request) {
        $post_type = get_post_type_object( $this->post_type );
        if (! current_user_can( $post_type->cap->edit_others_posts ) ) {
            return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to edit posts of this post type' ), array( 'status' => rest_authorization_required_code() ) );
        }
        return true;
    }

    /**
     * @param WP_REST_Request $request
     * @return mixed|WP_Error|WP_REST_Response
     * TODO: This should also return a JSON representation of the form instead of just the $id
     */
    public function update_item($request) {
        $id = (int) $request['id'];
        $form = WPCF7_ContactForm::get_instance( $id );

        if ( empty( $id ) || empty($form) || empty($form->id())) {
            return new WP_Error( 'rest_post_invalid_id', __( 'Invalid form id.' ), array( 'status' => 404 ) );
        }

        $request->set_param('id', $id); //TODO: Doubt this is even needed but don't have time to risk it atm
        $form = $this->prepare_item_for_database($request);
        $id = $form->save();
        $response = rest_ensure_response($id);
        return $response;
    }

    /**
     * @param mixed $form
     * @param WP_REST_Request $request
     * @return mixed|WP_REST_Response
     */
    public function prepare_item_for_response($form, $request)
    {
        $data = array();
        $data['id'] = $form->id();
        $data['name'] = $form->name();
        $data['title'] = $form->title();
        $data['locale'] = $form->locale;
        $data['properties'] = $form->get_properties();
        $data = $this->add_additional_fields_to_object($data, $request);
        $response = rest_ensure_response($data);
        $response = apply_filters('rest_api_prepare_wpcf7', $response, $form, $request);
        return $response;
    }


    /**
     * @param WP_REST_Request $request
     * @return mixed|WPCF7_ContactForm
     */
    protected function prepare_item_for_database($request) {

        $id  = $request->get_param('id');
        $prepared_form = WPCF7_ContactForm::get_instance( $id );

        /**
         * update_item ensures the form ID exists before calling this method to prevent non-existant forms from being created accidentally.
         */
        if(empty($prepared_form)) {
            $prepared_form = WPCF7_ContactForm::get_template();
        }

        if ( isset( $request['post-title'] ) ) {
            $prepared_form->set_title( $request['post-title'] );
        }

        if ( isset( $request['locale'] ) ) {
            $locale = trim( $request['locale'] );

            if ( wpcf7_is_valid_locale( $locale ) ) {
                $prepared_form->set_locale($locale);
            }
        }

        $properties = $prepared_form->get_properties();

        if ( isset( $request['form'] ) ) {
            $properties['form'] = trim( $request['form'] );
        }

        $mail = $properties['mail'];

        if ( isset( $request['mail-subject'] ) ) {
            $mail['subject'] = trim( $request['mail-subject'] );
        }

        if ( isset( $request['mail-sender'] ) ) {
            $mail['sender'] = trim( $request['mail-sender'] );
        }

        if ( isset( $request['mail-body'] ) ) {
            $mail['body'] = trim( $request['mail-body'] );
        }

        if ( isset( $request['mail-recipient'] ) ) {
            $mail['recipient'] = trim( $request['mail-recipient'] );
        }

        if ( isset( $request['mail-additional-headers'] ) ) {
            $headers = '';
            $tempheaders = str_replace(
                "\r\n", "\n", $request['mail-additional-headers'] );
            $tempheaders = explode( "\n", $tempheaders );

            foreach ( $tempheaders as $header ) {
                $header = trim( $header );

                if ( '' !== $header ) {
                    $headers .= $header . "\n";
                }
            }

            $mail['additional_headers'] = trim( $headers );
        }

        if ( isset( $request['mail-attachments'] ) ) {
            $mail['attachments'] = trim( $request['mail-attachments'] );
        }

        $mail['use_html'] = ! empty( $request['mail-use-html'] );
        $mail['exclude_blank'] = ! empty( $request['mail-exclude-blank'] );

        $properties['mail'] = $mail;

        $mail_2 = $properties['mail_2'];

        $mail_2['active'] = ! empty( $request['mail-2-active'] );

        if ( isset( $request['mail-2-subject'] ) ) {
            $mail_2['subject'] = trim( $request['mail-2-subject'] );
        }

        if ( isset( $request['mail-2-sender'] ) ) {
            $mail_2['sender'] = trim( $request['mail-2-sender'] );
        }

        if ( isset( $request['mail-2-body'] ) ) {
            $mail_2['body'] = trim( $request['mail-2-body'] );
        }

        if ( isset( $request['mail-2-recipient'] ) ) {
            $mail_2['recipient'] = trim( $request['mail-2-recipient'] );
        }

        if ( isset( $request['mail-2-additional-headers'] ) ) {
            $headers = '';
            $tempheaders = str_replace(
                "\r\n", "\n", $request['mail-2-additional-headers'] );
            $tempheaders = explode( "\n", $tempheaders );

            foreach ( $tempheaders as $header ) {
                $header = trim( $header );

                if ( '' !== $header ) {
                    $headers .= $header . "\n";
                }
            }

            $mail_2['additional_headers'] = trim( $headers );
        }

        if ( isset( $request['mail-2-attachments'] ) ) {
            $mail_2['attachments'] = trim( $request['mail-2-attachments'] );
        }

        $mail_2['use_html'] = ! empty( $request['mail-2-use-html'] );
        $mail_2['exclude_blank'] = ! empty( $request['mail-2-exclude-blank'] );

        $properties['mail_2'] = $mail_2;

        // For setting/updating confirmation/error messages
        foreach ( wpcf7_messages() as $key => $arr ) {
            $field_name = 'message-' . strtr( $key, '_', '-' );

            if ( isset( $request[$field_name] ) ) {
                $properties['messages'][$key] = trim( $request[$field_name] );
            }
        }

        if ( isset( $request['additional-settings'] ) ) {
            $properties['additional_settings'] = trim( $request['additional-settings'] );
        }

        $prepared_form->set_properties( $properties );

        //Preceded original with rest_api_
        do_action( 'rest_api_wpcf7_save_contact_form', $prepared_form );

        if ( wpcf7_validate_configuration() ) {
            $config_validator = new WPCF7_ConfigValidator( $prepared_form );
            $config_validator->validate();
        }

        return $prepared_form;

    }

    /**
     * Get the Form's schema, conforming to JSON Schema
     *
     * @return array
     */
    public function get_item_schema() {
        $schema = array(
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'form',
            'type'       => 'object',
            'properties' => array(
                'id'          => array(
                    'description' => __( 'Unique identifier for the form.' ),
                    'type'        => 'integer',
                    'context'     => array( 'embed', 'view' ),
                    'readonly'    => true,
                ),
                'title'       => array(
                    'description' => __( 'Display name for the form.' ),
                    'type'        => 'string',
                    'context'     => array( 'embed', 'view', 'edit' ),
                    'arg_options' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
                'name'        => array(
                    'description' => __( 'An alphanumeric identifier for the object unique to its type.' ),
                    'type'        => 'string',
                    'context'     => array( 'embed', 'view' ),
                    'arg_options' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
                'locale'      => array(
                    'description' => __( 'The locale setting of the form' ),
                    'type'        => 'string',
                    'context'     => array( 'embed', 'view', 'edit' ),
                    'arg_options' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
                'properties'  => array(
                    'description' => __('The properties of the form'),
                    'type'        => 'object',
                    'context'     => array( 'embed', 'view', 'edit' ),
                    'properties'  => array(
                        'form'  => array(
                            'description' => __('The content for the form'),
                            'type'        => 'string',
                            'context'     => array( 'embed', 'view', 'edit' ),
                        ),
                        'mail'  => array(
                            'type'        => 'object',
                            'context'     => array( 'embed', 'view', 'edit' ),
                            'properties'  => array(
                                'mail-subject' => array(
                                    'description' => __('Subject of the form'),
                                    'type'      => 'string',
                                    'context'   => array( 'embed', 'view', 'edit' ),
                                ),
                                'mail-sender' => array(
                                    'description' => __('Sending email address of the form'),
                                    'type'      => 'string',
                                    'context'   => array( 'embed', 'view', 'edit' ),
                                ),
                                'mail-recipient' => array(
                                    'description' => __('Recipient email address of the form'),
                                    'type'      => 'string',
                                    'context'   => array( 'embed', 'view', 'edit' ),
                                ),
                                'mail-additional-headers' => array(
                                    'description' => __('Additional form headers'),
                                    'type'      => 'string',
                                    'context'   => array( 'embed', 'view', 'edit' ),
                                ),
                                'mail-attachment' => array(
                                    'description' => __('Attachments to include with form'),
                                    'type'      => 'string',
                                    'context'   => array( 'embed', 'view', 'edit' ),
                                ),
                                'mail-use-html' => array(
                                    'description' => __('Send emails using HTML formatting'),
                                    'type'      => 'boolean',
                                    'context'   => array( 'embed', 'view', 'edit' ),
                                ),
                                'mail-exclude-blank' => array(
                                    'description' => __('Exclude lines with blank mail-tags from output'),
                                    'type'      => 'boolean',
                                    'context'   => array( 'embed', 'view', 'edit' ),
                                ),
                            ),
                        ),
                        'mail-2'  => array(
                            'type'        => 'object',
                            'context'     => array( 'embed', 'view', 'edit' ),
                            'properties'  => array(
                                'mail-2-subject' => array(
                                    'description' => __('Subject of the form'),
                                    'type'      => 'string',
                                    'context'   => array( 'embed', 'view', 'edit' ),
                                ),
                                'mail-2-sender' => array(
                                    'description' => __('Sending email address of the form'),
                                    'type'      => 'string',
                                    'context'   => array( 'embed', 'view', 'edit' ),
                                ),
                                'mail-2-recipient' => array(
                                    'description' => __('Recipient email address of the form'),
                                    'type'      => 'string',
                                    'context'   => array( 'embed', 'view', 'edit' ),
                                ),
                                'mail-2-additional-headers' => array(
                                    'description' => __('Additional form headers'),
                                    'type'      => 'string',
                                    'context'   => array( 'embed', 'view', 'edit' ),
                                ),
                                'mail-2-attachment' => array(
                                    'description' => __('Files to attach to the form'),
                                    'type'      => 'string',
                                    'context'   => array( 'embed', 'view', 'edit' ),
                                ),
                                'mail-2-use-html' => array(
                                    'description' => __('Send emails using HTML formatting'),
                                    'type'      => 'boolean',
                                    'context'   => array( 'embed', 'view', 'edit' ),
                                ),
                                'mail-2-exclude-blank' => array(
                                    'description' => __('Exclude lines with blank mail-tags from output'),
                                    'type'      => 'boolean',
                                    'context'   => array( 'embed', 'view', 'edit' ),
                                ),
                            ),
                        ),
                    ),
                ),
            )
        );

        #$schema['properties']['meta'] = $this->meta->get_field_schema();

        return $this->add_additional_fields_schema( $schema );
    }

}