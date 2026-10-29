<?php

namespace NinjaJobBoard\Tests\Integration;

use NinjaJobBoard\Tests\IntegrationTestCase;

/**
 * Who can call which admin REST route.
 *
 * - Anonymous: 401 on every route.
 * - Logged in without plugin permissions: 403 on every route.
 * - "Own forms" users: only their own forms and the applications of their own forms.
 */
class RestPermissionMatrixTest extends IntegrationTestCase
{
    /**
     * Every route of the namespace. A new route must be added here (and to the permission map),
     * otherwise test_every_registered_route_is_in_the_matrix fails.
     * {form} = a form ID, {app} = an application ID, {campaign} = a campaign ID
     */
    private const ROUTES = array(
        array('GET', '/forms'),
        array('POST', '/forms'),
        array('GET', '/forms/available'),
        array('POST', '/forms/import'),
        array('GET', '/forms/{form}'),
        array('POST', '/forms/{form}'),
        array('DELETE', '/forms/{form}'),
        array('POST', '/forms/{form}/duplicate'),
        array('POST', '/forms/{form}/restore'),
        array('GET', '/forms/{form}/export'),
        array('GET', '/forms/{form}/builder'),
        array('POST', '/forms/{form}/builder'),
        array('GET', '/forms/{form}/confirmation'),
        array('POST', '/forms/{form}/confirmation'),
        array('GET', '/forms/{form}/design'),
        array('POST', '/forms/{form}/design'),
        array('GET', '/forms/{form}/scheduling'),
        array('POST', '/forms/{form}/scheduling'),
        array('GET', '/forms/{form}/notifications'),
        array('POST', '/forms/{form}/notifications'),
        array('GET', '/applications'),
        array('GET', '/applications/export'),
        array('GET', '/applications/report'),
        array('GET', '/applications/{app}'),
        array('DELETE', '/applications/{app}'),
        array('GET', '/applications/{app}/adjacent'),
        array('POST', '/applications/{app}/notes'),
        array('POST', '/applications/{app}/status'),
        array('GET', '/forms/{form}/campaigns'),
        array('POST', '/forms/{form}/campaigns'),
        array('GET', '/forms/{form}/campaigns/count'),
        array('GET', '/campaigns/{campaign}'),
        array('POST', '/campaigns/{campaign}/send'),
        array('GET', '/campaigns/{campaign}/emails'),
        array('GET', '/settings'),
        array('POST', '/settings'),
        array('POST', '/settings/image'),
    );

    public function test_every_registered_route_is_in_the_matrix()
    {
        $registered = array();
        foreach (rest_get_server()->get_routes() as $route => $handlers) {
            if (strpos($route, '/ninja-job-board/v1/') !== 0) {
                continue;
            }
            $path = preg_replace(
                array('#\(\?P<id>\\\\d\+\)#'),
                array('{id}'),
                substr($route, strlen('/ninja-job-board/v1'))
            );
            foreach ($handlers as $handler) {
                foreach (array_keys($handler['methods']) as $method) {
                    $registered[] = $method . ' ' . $path;
                }
            }
        }

        $expected = array_map(function ($route) {
            return $route[0] . ' ' . str_replace(array('{form}', '{app}', '{campaign}'), '{id}', $route[1]);
        }, self::ROUTES);

        sort($registered);
        sort($expected);
        $this->assertSame($expected, $registered, 'The REST routes changed: update ROUTES in this test and the permission map');
    }

    public function test_anonymous_gets_401_on_every_route()
    {
        $ids = $this->fixtureIds();
        wp_set_current_user(0);

        foreach (self::ROUTES as $route) {
            $response = $this->rest($route[0], $this->fill($route[1], $ids));
            $this->assertSame(401, $response->get_status(), $route[0] . ' ' . $route[1] . ' must reject anonymous users');
        }
    }

    public function test_logged_in_user_without_plugin_permissions_gets_403_on_every_route()
    {
        $ids = $this->fixtureIds();

        foreach (array('subscriber', 'author', 'editor') as $role) {
            $this->actAs($this->makeUser($role));
            foreach (self::ROUTES as $route) {
                $response = $this->rest($route[0], $this->fill($route[1], $ids));
                $this->assertSame(403, $response->get_status(), $route[0] . ' ' . $route[1] . ' must reject a(n) ' . $role);
            }
        }
    }

    public function test_administrator_passes_the_permission_check_on_every_read_route()
    {
        $ids = $this->fixtureIds();
        $this->actAsAdmin();

        foreach (self::ROUTES as $route) {
            // Downloads stream a file and exit; they are covered by their own tests
            if ($route[0] !== 'GET' || in_array($route[1], array('/forms/{form}/export', '/applications/export'), true)) {
                continue;
            }
            $response = $this->rest($route[0], $this->fill($route[1], $ids));
            $this->assertNotContains($response->get_status(), array(401, 403), $route[0] . ' ' . $route[1] . ' must allow an administrator');
        }
    }

    public function test_own_forms_user_reaches_only_their_own_forms()
    {
        $owner = $this->makeUser('subscriber', array('wpjb_can_edit_own_form', 'wpjb_can_view_entries_of_own_created_forms'));
        $ownForm = $this->makeJob('Own job', $owner);
        $otherForm = $this->makeJob('Other job');

        $this->actAs($owner);

        $this->assertSame(200, $this->rest('GET', '/forms/' . $ownForm . '/builder')->get_status());
        $this->assertSame(403, $this->rest('GET', '/forms/' . $otherForm . '/builder')->get_status());
        $this->assertSame(403, $this->rest('POST', '/forms/' . $otherForm, array('post_title' => 'Taken over'))->get_status());
        $this->assertSame('Other job', get_post($otherForm)->post_title);
    }

    public function test_own_forms_user_reaches_only_applications_of_their_own_forms()
    {
        $owner = $this->makeUser('subscriber', array('wpjb_can_view_entries_of_own_created_forms'));
        $ownForm = $this->makeJob('Own job', $owner);
        $otherForm = $this->makeJob('Other job');
        $ownApplication = $this->makeApplication($ownForm);
        $otherApplication = $this->makeApplication($otherForm, array('applicant_name' => 'Secret Person'));

        $this->actAs($owner);

        $this->assertSame(200, $this->rest('GET', '/applications/' . $ownApplication)->get_status());
        $this->assertSame(403, $this->rest('GET', '/applications/' . $otherApplication)->get_status());
        $this->assertSame(403, $this->rest('GET', '/applications', array('form_id' => $otherForm))->get_status());
        // Without a form, an "own" user could list every application
        $this->assertSame(403, $this->rest('GET', '/applications')->get_status());
    }

    public function test_adjacent_does_not_leak_an_application_of_another_form()
    {
        $owner = $this->makeUser('subscriber', array('wpjb_can_view_entries_of_own_created_forms'));
        $ownForm = $this->makeJob('Own job', $owner);
        $otherForm = $this->makeJob('Other job');
        $ownApplication = $this->makeApplication($ownForm);
        // Newer than the own application, so "prev" (newer) would land on it without a form filter
        $this->makeApplication($otherForm, array('applicant_name' => 'Secret Person'));

        $this->actAs($owner);
        $response = $this->rest('GET', '/applications/' . $ownApplication . '/adjacent', array('direction' => 'prev'));

        $this->assertSame(404, $response->get_status());
        $this->assertStringNotContainsString('Secret Person', wp_json_encode($response->get_data()));
    }

    public function test_add_form_permission_does_not_allow_editing_forms_of_other_users()
    {
        $creator = $this->makeUser('subscriber', array('wpjb_can_add_form'));
        $otherForm = $this->makeJob('Other job');

        $this->actAs($creator);
        $this->assertSame(200, $this->rest('POST', '/forms', array('post_title' => 'My new job'))->get_status());
        $this->assertSame(403, $this->rest('POST', '/forms/' . $otherForm . '/builder', array('builder_settings' => array()))->get_status());
    }

    private function fixtureIds()
    {
        $formId = $this->makeJob();
        $applicationId = $this->makeApplication($formId);
        $this->wpdb->insert($this->wpdb->prefix . 'wjb_email_campaigns', array(
            'form_id'    => $formId,
            'title'      => 'Campaign',
            'subject'    => 'Subject',
            'body'       => 'Body',
            'status'     => 'ready',
            'created_at' => gmdate('Y-m-d H:i:s'),
            'updated_at' => gmdate('Y-m-d H:i:s')
        ));
        return array('form' => $formId, 'app' => $applicationId, 'campaign' => (int)$this->wpdb->insert_id);
    }

    private function fill($path, $ids)
    {
        return str_replace(array('{form}', '{app}', '{campaign}'), array($ids['form'], $ids['app'], $ids['campaign']), $path);
    }
}
