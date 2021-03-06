<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt New BSD License
 */

namespace Module\User\Controller\Front;

use Pi;
use Pi\Mvc\Controller\ActionController;
use Pi\Paginator\Paginator;

/**
 * Home controller
 *
 * @author Liu Chuang <liuchuang@eefocus.com>
 */
class HomeController extends ActionController
{
    /**
     * Owner home page
     *
     * @return array|void
     */
    public function indexAction()
    {
        $page   = $this->params('page', 1);
        $limit  = Pi::service('module')->config('list_limit', 'user');
        $offset = (int) ($page -1) * $limit;

        $isLogin = Pi::user()->hasIdentity();
        if (!$isLogin) {
            $this->jump(
                array('', array('controller' => 'login', 'action' => 'index')),
                __('Please login'),
                5
            );
            return;
        }

        $uid  = Pi::user()->getId();
        // Get user information
        $user = $this->getUser($uid);

        // Get timeline
        $count    = Pi::api('user', 'timeline')->getCount($uid);
        $timeline = Pi::api('user', 'timeline')->get($uid, $limit, $offset);

        // Get timeline meta list
        $timelineMetaList = Pi::api('user', 'timeline')->getList();

        // Set timeline meta
        foreach ($timeline as &$item) {
            $item['icon']  = $timelineMetaList[$item['timeline']]['icon'];
            $item['title'] = $timelineMetaList[$item['timeline']]['title'];
        }

        // Get activity meta for nav display
        $nav = Pi::api('user', 'nav')->getList('homepage');

        // Get quick link
        $quicklink = $this->getQuicklink();


        // Set paginator
        $paginatorOption = array(
            'count'      => $count,
            'limit'      => $limit,
            'page'       => $page,
            'controller' => 'home',
            'action'     => 'index',
        );
        $paginator = $this->setPaginator($paginatorOption);

        $this->view()->assign(array(
            'uid'          => $uid,
            'user'         => $user,
            'timeline'     => $timeline,
            'paginator'    => $paginator,
            'quicklink'    => $quicklink,
            'is_owner'     => true,
            'nav'          => $nav,
        ));
    }

    /**
     * Other view home page
     */
    public function viewAction()
    {
        $page   = $this->params('page', 1);
        $limit  = Pi::service('module')->config('list_limit', 'user');
        $offset = (int) ($page -1) * $limit;

        $uid = $this->params('uid', '');
        if (!$uid) {
            return $this->jumpTo404(__('Invalid user ID!'));
        }
        // Get user information
        $user = $this->getUser($uid);

        // Get viewer role: public member follower following owner
        $role = Pi::user()->hasIdentity() ? 'member' : 'public';
        $user = Pi::api('user', 'privacy')->filterProfile(
            $uid,
            $role,
            $user,
            'user'
        );

        // Get timeline
        $count    = Pi::api('user', 'timeline')->getCount($uid);
        $timeline = Pi::api('user', 'timeline')->get($uid, $limit, $offset);

        // Get timeline meta list
        $timelineMetaList = Pi::api('user', 'timeline')->getList();

        // Set timeline meta
        foreach ($timeline as &$item) {
            $item['icon']  = $timelineMetaList[$item['timeline']]['icon'];
            $item['title'] = $timelineMetaList[$item['timeline']]['title'];
        }

        // Get activity meta for nav display
        $nav = Pi::api('user', 'nav')->getList('homepage', $uid);

        // Get quick link
        $quicklink = $this->getQuicklink();


        // Set paginator
        $paginatorOption = array(
            'count'      => $count,
            'limit'      => $limit,
            'page'       => $page,
            'controller' => 'home',
            'action'     => 'view',
            'uid'        => $uid,
        );
        $paginator = $this->setPaginator($paginatorOption);

        $this->view()->assign(array(
            'uid'          => $uid,
            'user'         => $user,
            'timeline'     => $timeline,
            'paginator'    => $paginator,
            'quicklink'    => $quicklink,
            'is_owner'     => false,
            'nav'          => $nav,
        ));
    }

    /**
     * Get quicklink
     *
     * @param null $limit
     * @param null $offset
     * @return array
     */
    protected function getQuicklink($limit = null, $offset = null)
    {
        $result = array();
        $model = $this->getModel('quicklink');
        $where = array(
            'active'  => 1,
            'display' => 1,
        );
        $columns = array(
            'id',
            'name',
            'title',
            'module',
            'link',
            'icon',
        );

        $select = $model->select()->where($where);
        if ($limit) {
            $select->limit($limit);
        }
        if ($offset) {
            $select->offset($offset);
        }

        $select->columns($columns);
        $rowset = $model->selectWith($select);

        foreach ($rowset as $row) {
            $result[] = $row->toArray();
        }

        return $result;

    }

    /**
     * Get user information for profile page head display
     *
     * @param $uid
     * @return array user information
     */
    protected function getUser($uid)
    {
        $result = Pi::api('user', 'user')->get(
            $uid,
            array('name', 'gender', 'birthdate'),
            true
        );

        return $result;
    }

    /**
     * Set paginator
     *
     * @param $option
     * @return \Pi\Paginator\Paginator
     */
    protected function setPaginator($option)
    {
        $params = array(
            'module'        => $this->getModule(),
            'controller'    => $option['controller'],
            'action'        => $option['action'],
        );

        if (isset($option['uid'])) {
            $params['uid'] = $option['uid'];
        }

        $paginator = Paginator::factory(intval($option['count']), array(
            'limit' => $option['limit'],
            'page'  => $option['page'],
            'url_options'   => array(
                'params'    => $params
            ),
        ));

        return $paginator;

    }
}
