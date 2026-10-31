<?php

namespace NinjaJobBoard\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use WPJobBoard\Classes\File\FileHandler;
use WPJobBoard\Classes\Models\Submission;

// Every test runs in a rolled-back transaction; see integration-bootstrap.php for the safety model.
abstract class IntegrationTestCase extends BaseTestCase
{
    /** @var \wpdb */
    protected $wpdb;

    /** @var int Mail and HTTP calls that were blocked during the test */
    protected $blockedMail = 0;
    protected $blockedHttp = 0;

    /** @var callable|null Answers outbound HTTP calls in a test; null blocks them */
    protected $httpResponder = null;

    /** @var string[] Files a test created on disk; removed in tearDown() */
    private $createdFiles = array();

    /** @var int */
    private $witnessCountBefore;

    protected function setUp(): void
    {
        parent::setUp();

        global $wpdb;
        $this->wpdb = $wpdb;
        $this->wpdb->suppress_errors(false);
        $this->wpdb->show_errors(false);

        $this->blockOutboundSideEffects();

        $this->witnessCountBefore = $this->countApplications();
        $this->wpdb->query('START TRANSACTION');

        wp_set_current_user(0);
    }

    protected function tearDown(): void
    {
        $this->wpdb->query('ROLLBACK');
        wp_cache_flush();
        wp_set_current_user(0);

        foreach ($this->createdFiles as $file) {
            if (is_file($file)) {
                wp_delete_file($file);
            }
        }
        $this->createdFiles = array();

        remove_all_filters('pre_wp_mail');
        remove_all_filters('pre_http_request');

        // The rollback guarantee: the tests left no rows behind
        $this->assertSame($this->witnessCountBefore, $this->countApplications(), 'Rows survived the rollback: the transaction guarantee is broken (DDL or COMMIT in a test?)');

        parent::tearDown();
    }

    private function countApplications()
    {
        return (int)$this->wpdb->get_var('SELECT COUNT(*) FROM ' . $this->wpdb->prefix . 'wjb_applications');
    }

    private function blockOutboundSideEffects()
    {
        add_filter('pre_wp_mail', function () {
            $this->blockedMail++;
            return true;
        });

        add_filter('pre_http_request', function ($pre, $args, $url) {
            if ($this->httpResponder) {
                return call_user_func($this->httpResponder, $args, $url);
            }
            $this->blockedHttp++;
            return new \WP_Error('njb_tests_blocked', 'Outbound HTTP is blocked in tests: ' . $url);
        }, 10, 3);
    }

    protected function makeUser($role, array $caps = array())
    {
        $userId = wp_insert_user(array(
            'user_login' => 'njb_' . $role . '_' . wp_generate_password(8, false),
            'user_pass'  => wp_generate_password(20),
            'user_email' => wp_generate_password(8, false) . '@example.com',
            'role'       => $role
        ));
        $this->assertIsInt($userId, 'Could not create the test user');

        $user = new \WP_User($userId);
        foreach ($caps as $cap) {
            $user->add_cap($cap);
        }
        return $userId;
    }

    protected function actAs($userId)
    {
        wp_set_current_user($userId);
    }

    protected function actAsAdmin()
    {
        $this->actAs($this->makeUser('administrator'));
    }

    /**
     * @return \WP_REST_Response
     */
    protected function rest($method, $path, array $params = array())
    {
        $request = new \WP_REST_Request($method, '/ninja-job-board/v1' . $path);
        foreach ($params as $key => $value) {
            $request->set_param($key, $value);
        }
        return rest_do_request($request);
    }

    /**
     * A published job with the default application form (name, email, CV upload).
     * @return int Job (form) ID
     */
    protected function makeJob($title = 'Test job', $authorId = null)
    {
        $previous = get_current_user_id();
        $this->actAs($this->makeUser('administrator'));

        $response = $this->rest('POST', '/forms', array('post_title' => $title));
        $this->assertSame(200, $response->get_status(), 'Could not create the job: ' . wp_json_encode($response->get_data()));
        $formId = (int)$response->get_data()['form_id'];
        $update = array('ID' => $formId, 'post_status' => 'publish');
        if ($authorId) {
            // The owner of the job, for "own forms" permission tests
            $update['post_author'] = $authorId;
        }
        wp_update_post($update);

        $this->actAs($previous);
        return $formId;
    }

    /**
     * @return int Application ID
     */
    protected function makeApplication($formId, array $formatted = array(), array $columns = array())
    {
        $formatted = array_merge(array(
            'applicant_name'  => 'Ada Applicant',
            'applicant_email' => 'ada@example.com'
        ), $formatted);

        $row = array_merge(array(
            'form_id'             => $formId,
            'user_id'             => 0,
            'applicant_name'      => $formatted['applicant_name'],
            'applicant_email'     => $formatted['applicant_email'],
            'form_data_raw'       => maybe_serialize(array()),
            'form_data_formatted' => maybe_serialize($formatted),
            'application_status'  => 'applied',
            'submission_hash'     => 'wpjb_' . bin2hex(random_bytes(16)),
            'status'              => 'new',
            'created_at'          => gmdate('Y-m-d H:i:s'),
            'updated_at'          => gmdate('Y-m-d H:i:s')
        ), $columns);

        $id = (new Submission())->create($row);
        $this->assertNotEmpty($id, 'Could not insert the application');
        return (int)$id;
    }

    /**
     * @return array{name: string, path: string, url: string}
     */
    protected function makeUploadedFile($originalName = 'cv.pdf', $contents = "%PDF-1.4\n%%EOF\n")
    {
        $dir = FileHandler::getUploadDir();
        FileHandler::ensureProtectedDir($dir['path']);
        $name = 'wpf-' . bin2hex(random_bytes(16)) . '-wpf-' . $originalName;
        $path = $dir['path'] . '/' . $name;
        file_put_contents($path, $contents); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- test fixture
        $this->createdFiles[] = $path;
        return array('name' => $name, 'path' => $path, 'url' => $dir['url'] . '/' . $name);
    }

    /**
     * The required CV field gets a valid upload unless $fields sets '__file_upload_input_files[]'.
     * @return array{status: int, body: mixed, raw: string, row: array|null} row = the stored application
     */
    protected function submitApplication($formId, array $fields)
    {
        $fields = array_merge(array(
            '__wpjb_form_id'    => $formId,
            'applicant_name'    => 'Ada Applicant',
            'applicant_email'   => 'ada@example.com',
            'file_upload_input' => '__file_upload_input_files'
        ), $fields);

        if (!isset($fields['__file_upload_input_files[]'])) {
            $file = $this->makeUploadedFile();
            set_transient(FileHandler::uploadRecordKey($file['name']), $formId . ':file_upload_input', 60);
            $fields['__file_upload_input_files[]'] = $file['name'];
        }

        wp_set_current_user(0);
        $_REQUEST = array(
            'action'     => 'wpjb_submit_form',
            'form_id'    => $formId,
            'wpjb_nonce' => wp_create_nonce('wpjobboard_public_nonce'),
            'form_data'  => http_build_query($fields)
        );

        $result = $this->captureAjax(function () {
            (new \WPJobBoard\Classes\SubmissionHandler())->handeSubmission();
        });
        $_REQUEST = array();

        $result['row'] = null;
        if (!empty($result['body']['success'])) {
            $result['row'] = (array)(new Submission())->getSubmission($result['body']['data']['submission_id']);
        }
        return $result;
    }

    /**
     * Run code that ends in wp_send_json()/wp_die() as an AJAX request and capture the result
     * @return array{status: int, body: mixed, raw: string}
     */
    protected function captureAjax(callable $callback)
    {
        $throw = function () {
            return function ($message = '', $title = '', $args = array()) {
                throw new WpDieException(is_string($message) ? $message : '', is_array($args) && isset($args['response']) ? (int)$args['response'] : 0);
            };
        };

        add_filter('wp_doing_ajax', '__return_true');
        add_filter('wp_die_ajax_handler', $throw, 1);
        add_filter('wp_die_handler', $throw, 1);

        $status = 200;
        add_filter('status_header', function ($header, $code) use (&$status) {
            $status = (int)$code;
            return $header;
        }, 10, 2);

        ob_start();
        try {
            $callback();
            $this->fail('Expected the handler to end with wp_send_json()/wp_die()');
        } catch (WpDieException $e) {
            // Expected end of the request
        } finally {
            $raw = ob_get_clean();
            remove_filter('wp_doing_ajax', '__return_true');
            remove_all_filters('wp_die_ajax_handler');
            remove_all_filters('wp_die_handler');
            remove_all_filters('status_header');
        }

        return array('status' => $status, 'body' => json_decode($raw, true), 'raw' => $raw);
    }
}
